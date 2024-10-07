<?php

namespace Drupal\os2forms_digital_post\Helper;

use DigitalPost\MeMo\Message;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_digital_post\Exception\RuntimeException;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\os2web_datalookup\Plugin\DataLookupManager;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCompany;
use Drupal\os2web_datalookup\Plugin\os2web\DataLookup\DataLookupInterfaceCpr;
use Drupal\webform\WebformSubmissionInterface;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use Oio\Fjernprint\ForsendelseI;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Webform helper.
 */
final class DigitalPostHelper implements LoggerInterface {
  use LoggerTrait;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly CertificateLocatorHelper $certificateLocatorHelper,
    private readonly DataLookupManager $dataLookupManager,
    private readonly MeMoHelper $meMoHelper,
    private readonly ForsendelseHelper $forsendelseHelper,
    private readonly BeskedfordelerHelper $beskedfordelerHelper,
    private readonly LoggerChannelInterface $logger,
    private readonly LoggerChannelInterface $submissionLogger,
  ) {
  }

  /**
   * Send digital post.
   *
   * @param string $type
   *   The digital post type.
   * @param \DigitalPost\MeMo\Message $message
   *   The MeMo message.
   * @param \Oio\Fjernprint\ForsendelseI|null $forsendelse
   *   The forsendelse if any.
   * @param null|\Drupal\webform\WebformSubmissionInterface $submission
   *   A submission used for hooking up with Beskedfordeler.
   *
   * @return array
   *   [The response, The kombi post message].
   *
   * @phpstan-return array<int, mixed>
   */
  public function sendDigitalPost(string $type, Message $message, ?ForsendelseI $forsendelse, ?WebformSubmissionInterface $submission = NULL): array {
    $senderSettings = $this->settings->getSender();
    $options = [
      'test_mode' => (bool) $this->settings->getTestMode(),
      'authority_cvr' => $senderSettings[Settings::SENDER_IDENTIFIER],
      'certificate_locator' => $this->certificateLocatorHelper->getCertificateLocator(),
    ];
    $service = new SF1601($options);
    $transactionId = Serializer::createUuid();
    $response = $service->kombiPostAfsend($transactionId, $type, $message, $forsendelse);

    $content = (string) $response->getContent();
    if (NULL !== $submission) {
      $this->beskedfordelerHelper->createMessage($submission->id(), $message, $content);
    }

    return [$response, $service->getLastKombiMeMoMessage()];
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $level
   *   The level.
   * @param string $message
   *   The message.
   * @param array $context
   *   The context.
   */
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
  }

  /**
   * Look up CPR.
   */
  public function lookupCpr(string $cpr): CprLookupResult {
    $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cpr_lookup');
    if (!($instance instanceof DataLookupInterfaceCpr)) {
      throw new RuntimeException('Cannot get CPR data lookup instance');
    }
    $lookupResult = $instance->lookup($cpr);
    if (!$lookupResult->isSuccessful()) {
      throw new RuntimeException('Cannot look up CPR');
    }

    return $lookupResult;
  }

  /**
   * Look up CVR.
   */
  public function lookupCvr(string $cvr): CompanyLookupResult {
    $instance = $this->dataLookupManager->createDefaultInstanceByGroup('cvr_lookup');
    if (!($instance instanceof DataLookupInterfaceCompany)) {
      throw new RuntimeException('Cannot get CVR data lookup instance');
    }
    $lookupResult = $instance->lookup($cvr);
    if (!$lookupResult->isSuccessful()) {
      throw new RuntimeException('Cannot look up CVR');
    }

    return $lookupResult;
  }

  /**
   * Look up recipient.
   */
  public function lookupRecipient(string $recipient): CprLookupResult|CompanyLookupResult {
    try {
      return preg_match('/^\d{8}$/', $recipient)
        ? $this->lookupCvr($recipient)
        : $this->lookupCpr($recipient);
    }
    catch (\Exception) {
      throw new RuntimeException('Cannot lookup recipient');
    }
  }

  /**
   * Get MeMeHelper.
   */
  public function getMeMoHelper(): MeMoHelper {
    return $this->meMoHelper;
  }

  /**
   * Get ForsendelseHelper.
   */
  public function getForsendelseHelper(): ForsendelseHelper {
    return $this->forsendelseHelper;
  }

}
