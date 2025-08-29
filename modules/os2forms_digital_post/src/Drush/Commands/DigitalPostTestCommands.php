<?php

namespace Drupal\os2forms_digital_post\Drush\Commands;

use DigitalPost\MeMo\Action;
use DigitalPost\MeMo\EntryPoint;
use DigitalPost\MeMo\Reservation;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Utility\Token;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\os2forms_digital_post\Helper\DigitalPostHelper;
use Drupal\os2forms_digital_post\Helper\Settings;
use Drupal\os2forms_digital_post\Model\Document;
use Drush\Commands\DrushCommands;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Test commands for digital post.
 */
class DigitalPostTestCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructor.
   */
  public function __construct(
    #[Autowire(service: DigitalPostHelper::class)]
    private readonly DigitalPostHelper $digitalPostHelper,
    private readonly Token $token,
    #[Autowire(service: 'plugin.manager.entity_print.print_engine')]
    private readonly EntityPrintPluginManagerInterface $entityPrintPluginManager,
    #[Autowire(service: Settings::class)]
    private readonly Settings $digitalPostSettings,
  ) {
  }

  /**
   * Send digital post.
   *
   * @param array $recipients
   *   The recipients (CPR or CVR).
   * @param array $options
   *   The options.
   *
   * @option subject
   *   The subject. Can contain HTML.
   * @option message
   *   The message to send. Can contain HTML.
   * @option digital-post-type
   *   The digital post type to use.
   * @option dump-digital-post-settings
   *   Dump digital post settings.
   * @option memo-version
   *   MeMo version (1.1 or 1.2). If not set, a proper default will be used.
   * @option action
   *   MeMo actions, e.g. 'action=INFORMATION&label=Vigtig%20information&entrypoint=https://example.com'
   * @option filename
   *   The main document filename (used to test invalid filenames (cf. https://digitaliser.dk/digital-post/nyhedsarkiv/2024/nov/oeget-validering-i-digital-post))
   *
   * @phpstan-param array<string> $recipients
   * @phpstan-param array<string, mixed> $options
   *
   * @command os2forms-digital-post:test:send
   * @usage os2forms-digital-post:test:send --help
   */
  public function send(
    array $recipients,
    array $options = [
      'subject' => 'os2forms_digital_post',
      'message' => 'This is a test message from os2forms_digital_post sent on [current-date:html_datetime].',
      'digital-post-type' => SF1601::TYPE_AUTOMATISK_VALG,
      'dump-digital-post-settings' => FALSE,
      'memo-version' => NULL,
      'action' => [],
      'filename' => 'os2forms_digital_post',
    ],
  ): void {
    $io = new SymfonyStyle($this->input(), $this->output());

    if ($options['dump-digital-post-settings']) {
      $this->dumpDigitalPostSettings($io);
    }

    $subject = $this->token->replace($options['subject']);
    $message = $this->token->replace($options['message']);
    $senderLabel = $this->token->replace('os2forms_digital_post');
    $messageLabel = $this->token->replace('os2forms_digital_post [current-date:html_datetime]');

    $html = sprintf('<h1>%s</h1>%s', $subject, $message);

    /** @var \Drupal\entity_print\Plugin\EntityPrint\PrintEngine\PdfEngineBase $printer */
    $printer = $this->entityPrintPluginManager->createInstance('dompdf');
    $printer->addPage($html);
    $content = $printer->getBlob();

    $document = new Document(
      $content,
      Document::MIME_TYPE_PDF,
      $options['filename'] . '.pdf',
    );

    $type = $options['digital-post-type'];
    if (!in_array($type, SF1601::TYPES, TRUE)) {
      $quote = static fn ($value) => var_export($value, TRUE);
      throw new InvalidArgumentException(sprintf('Invalid type: %s. Must be one of %s.', $quote($type), implode(', ', array_map($quote, SF1601::TYPES))));
    }

    $meMoVersion = $options['memo-version'];
    if ($meMoVersion) {
      $meMoVersion = (float) $meMoVersion;
      $allowedValues = [SF1601::MEMO_1_1, SF1601::MEMO_1_2];
      if (!in_array($meMoVersion, $allowedValues, TRUE)) {
        $quote = static fn($value) => var_export($value, TRUE);
        throw new InvalidArgumentException(sprintf(
          'Invalid MeMo version: %s. Must be one of %s.',
          $quote($meMoVersion),
          implode(', ', array_map($quote, $allowedValues))
        ));
      }
    }

    $io->section('Digital post');
    $io->definitionList(
      ['Type' => $type],
      ['Subject' => $subject],
      ['Message' => $message],
      ['Document' => sprintf('%s (%s)', $document->filename, $document->mimeType)],
      ['MeMo version' => $meMoVersion ?? '–'],
    );

    $actions = array_map($this->buildAction(...), $options['action']);

    foreach ($recipients as $recipient) {
      try {
        $recipientLookupResult = $this->digitalPostHelper->lookupRecipient($recipient);

        $meMoMessage = $this->digitalPostHelper->getMeMoHelper()->buildMessage($recipientLookupResult, $senderLabel,
          $messageLabel, $document, $actions);
        // If a valid memo-version option has been provided, set that version on
        // the message.
        if ($meMoVersion) {
          $meMoMessage->setMemoVersion($meMoVersion);
        }
        $forsendelse = $this->digitalPostHelper->getForsendelseHelper()->buildForsendelse($recipientLookupResult,
          $messageLabel, $document);

        $this->digitalPostHelper->sendDigitalPost(
          $type,
          $meMoMessage,
          $forsendelse
        );

        $io->definitionList(
          ['Recipient' => $recipient],
          ['Document' => sprintf('%s (%s)', $document->filename, $document->mimeType)],
          ['MeMo version' => $meMoMessage->getMemoVersion()],
        );

        $io->success(sprintf('Digital post sent to %s (MeMo %s)', $recipient, $meMoMessage->getMemoVersion()));
      }
      catch (\Throwable $throwable) {
        $io->error(sprintf('Error sending digital post to %s:', $recipient));
        $io->writeln($throwable->getMessage());

        if ($throwable instanceof ClientExceptionInterface) {
          $response = $throwable->getResponse();
          $io->section('Response');
          $io->writeln(
            Yaml::encode([
              'headers' => $response->getHeaders(FALSE),
              'content' => $response->getContent(FALSE),
            ]),
          );
        }
      }
    }
  }

  /**
   * Dump digital post settings.
   */
  private function dumpDigitalPostSettings(SymfonyStyle $io): void {
    $io->section('Digital post settings');
    $io->writeln([
      Yaml::encode([
        'testMode' => $this->digitalPostSettings->getTestMode(),
        'sender' => $this->digitalPostSettings->getSender(),
        'certificate' => $this->digitalPostSettings->getCertificate(),
        'processing' => $this->digitalPostSettings->getProcessing(),
      ]),
      '',
    ]);
  }

  /**
   * Build MeMo action.
   *
   * Lifted from KombiPostAfsendCommand::buildAction().
   *
   * @see KombiPostAfsendCommand::buildAction()
   */
  private function buildAction(string $spec): Action {
    parse_str($spec, $options);
    $resolver = $this->getActionOptionsResolver();
    try {
      $options = $resolver->resolve($options);
    }
    catch (ExceptionInterface $exception) {
      throw new InvalidOptionException(sprintf(
        'Invalid action %s: %s',
        json_encode($spec),
        $exception->getMessage()
      ));
    }

    $action = (new Action())
      ->setActionCode($options['action'])
      ->setLabel($options['label']);
    if (SF1601::ACTION_AFTALE === $options['action']) {
      $reservation = (new Reservation())
        ->setStartDateTime(new \DateTime('+2 days'))
        ->setEndDateTime(new \DateTime('+2 days 1 hour'))
        ->setLocation('Meeting room 1')
        ->setAbstract('Abstract')
        ->setDescription('Description')
        ->setOrganizerName('Organizer')
        ->setOrganizerMail('organizer@example.com')
        ->setReservationUUID(Serializer::createUuid());
      $action->setReservation($reservation);
    }
    elseif ($options['entrypoint']) {
      $action->setEntryPoint(
        (new EntryPoint())
          ->setUrl($options['entrypoint'])
          );
    }

    if ($options['endDateTime']) {
      $action->setEndDateTime(new \DateTime($options['endDateTime']));
    }

    return $action;
  }

  /**
   * Get actions options resolver.
   *
   * @see KombiPostAfsendCommand::getActionOptionsResolver()
   */
  private function getActionOptionsResolver(): OptionsResolver {
    $resolver = new OptionsResolver();
    $resolver
      ->setRequired([
        'action',
        'label',
      ])
      ->setDefaults([
        'endDateTime' => NULL,
        'entrypoint' => NULL,
      ])
      ->setInfo('action', sprintf('The action name (one of %s)', implode(', ', SF1601::ACTIONS)))
      ->setInfo('label', 'The action label')
      ->setInfo('endDateTime', 'The end time e.g. "2022-12-02" or "14 days"')
      ->setInfo('entrypoint', 'The entry point (an URL)')
      ->setAllowedValues('action', static function ($value) {
        return in_array($value, SF1601::ACTIONS, TRUE);
      })
      ->setNormalizer('entrypoint', static function (Options $options, $value) {
        if (NULL === $value && SF1601::ACTION_AFTALE !== $options['action']) {
          throw new InvalidOptionsException(sprintf(
            'Action entrypoint is required for all actions but %s',
            SF1601::ACTION_AFTALE
          ));
        }

        return $value;
      });

    return $resolver;
  }

}
