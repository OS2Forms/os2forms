<?php

namespace Drupal\os2forms_digital_post\Commands;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Utility\Token;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;
use Drupal\os2forms_digital_post\Helper\DigitalPostHelper;
use Drupal\os2forms_digital_post\Helper\Settings;
use Drupal\os2forms_digital_post\Model\Document;
use Drush\Commands\DrushCommands;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * Test commands for digital post.
 */
class DigitalPostTestCommands extends DrushCommands {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly DigitalPostHelper $digitalPostHelper,
    private readonly Token $token,
    private readonly EntityPrintPluginManagerInterface $entityPrintPluginManager,
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
   * @option string subject
   *     The subject. Can contain HTML.
   * @option string message
   *    The message to send. Can contain HTML.
   * @option string digital-post-type
   *      The digital post type to use.
   * @option bool dump-digital-post-settings
   *     Dump digital post settings.
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
      'os2forms_digital_post.pdf'
    );

    $type = $options['digital-post-type'];
    if (!in_array($type, SF1601::TYPES, TRUE)) {
      $quote = static fn ($value) => var_export($value, TRUE);
      throw new InvalidArgumentException(sprintf('Invalid type: %s. Must be one of %s.', $quote($type), implode(', ', array_map($quote, SF1601::TYPES))));
    }

    $io->section('Digital post');
    $io->definitionList(
      ['Type' => $type],
      ['Subject' => $subject],
      ['Message' => $message]
    );

    foreach ($recipients as $recipient) {
      try {
        $io->writeln(sprintf('Recipient: %s', $recipient));
        $recipientLookupResult = $this->digitalPostHelper->lookupRecipient($recipient);
        $actions = [];

        $meMoMessage = $this->digitalPostHelper->getMeMoHelper()->buildMessage($recipientLookupResult, $senderLabel,
          $messageLabel, $document, $actions);
        $forsendelse = $this->digitalPostHelper->getForsendelseHelper()->buildForsendelse($recipientLookupResult,
          $messageLabel, $document);

        $this->digitalPostHelper->sendDigitalPost(
          $type,
          $meMoMessage,
          $forsendelse
        );

        $io->success(sprintf('Digital post sent to %s', $recipient));
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
        'certificate' => [
          'key' => $this->digitalPostSettings->getKey(),
        ],
        'processing' => $this->digitalPostSettings->getProcessing(),
      ]),
      '',
    ]);
  }

}
