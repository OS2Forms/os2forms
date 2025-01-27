<?php

namespace Drupal\os2forms_digital_post\Helper;

use DataGovDk\Model\Core\Address;
use DigitalPost\MeMo\Action;
use DigitalPost\MeMo\AttentionData;
use DigitalPost\MeMo\AttentionPerson;
use DigitalPost\MeMo\EntryPoint;
use DigitalPost\MeMo\File;
use DigitalPost\MeMo\MainDocument;
use DigitalPost\MeMo\Message;
use DigitalPost\MeMo\MessageBody;
use DigitalPost\MeMo\MessageHeader;
use DigitalPost\MeMo\Recipient;
use DigitalPost\MeMo\Sender;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_digital_post\Model\Document;
use Drupal\os2forms_digital_post\Plugin\WebformHandler\WebformHandlerSF1601;
use Drupal\os2web_datalookup\LookupResult\CompanyLookupResult;
use Drupal\os2web_datalookup\LookupResult\CprLookupResult;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use ItkDev\Serviceplatformen\Service\SF1601\SF1601;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MeMo helper.
 */
class MeMoHelper extends AbstractMessageHelper {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    Settings $settings,
    #[Autowire(service: 'plugin.manager.element_info')]
    ElementInfoManager $elementInfoManager,
    #[Autowire(service: 'webform.token_manager')]
    WebformTokenManagerInterface $webformTokenManager,
  ) {
    parent::__construct($settings, $elementInfoManager, $webformTokenManager);
  }

  /**
   * Build message.
   *
   * @phpstan-param array<int, mixed> $actions
   */
  public function buildMessage(CprLookupResult|CompanyLookupResult $recipientData, string $senderLabel, string $messageLabel, Document $document, array $actions): Message {
    $messageUUID = Serializer::createUuid();
    $messageID = Serializer::createUuid();

    $message = new Message();

    $senderOptions = $this->settings->getSender();
    $sender = (new Sender())
      ->setIdType($senderOptions[Settings::SENDER_IDENTIFIER_TYPE])
      ->setSenderID($senderOptions[Settings::SENDER_IDENTIFIER])
      ->setLabel($senderLabel);

    [$recipientIdType, $recipientID] = $recipientData instanceof CompanyLookupResult
      ? ['CVR', $recipientData->getCvr()]
      : ['CPR', $recipientData->getCpr()];

    $recipient = (new Recipient())
      ->setIdType($recipientIdType)
      ->setRecipientID($recipientID);

    $this->enrichRecipient($recipient, $recipientData);

    $messageHeader = (new MessageHeader())
      ->setMessageType(SF1601::MESSAGE_TYPE_DIGITAL_POST)
      ->setMessageUUID($messageUUID)
      ->setMessageID($messageID)
      ->setLabel($messageLabel)
      ->setMandatory(FALSE)
      ->setLegalNotification(FALSE)
      ->setSender($sender)
      ->setRecipient($recipient);

    $message->setMessageHeader($messageHeader);

    $body = (new MessageBody())
      ->setCreatedDateTime(new \DateTime());

    $mainDocument = (new MainDocument())
      ->setFile([
        (new File())
          ->setEncodingFormat($document->mimeType)
          ->setLanguage($document->language)
          ->setFilename($document->filename)
          ->setContent($document->content),
      ]);

    foreach ($actions as $action) {
      $mainDocument->addToAction($action);
    }

    $body->setMainDocument($mainDocument);

    $message->setMessageBody($body);

    return $message;
  }

  /**
   * Build MeMo message from a webform submission.
   *
   * @phpstan-param array<string, mixed> $options
   * @phpstan-param array<string, mixed> $handlerSettings
   */
  public function buildWebformSubmissionMessage(WebformSubmissionInterface $submission, array $options, array $handlerSettings, CprLookupResult|CompanyLookupResult|null $recipientData = NULL): Message {
    $senderLabel = $this->replaceTokens($options[WebformHandlerSF1601::SENDER_LABEL], $submission);
    $messageLabel = $this->replaceTokens($options[WebformHandlerSF1601::MESSAGE_HEADER_LABEL], $submission);
    $document = $this->getMainDocument($submission, $handlerSettings);
    $actions = [];
    if (isset($handlerSettings[WebformHandlerSF1601::MEMO_ACTIONS]['actions'])) {
      foreach ($handlerSettings[WebformHandlerSF1601::MEMO_ACTIONS]['actions'] as $spec) {
        $actions[] = $this->buildAction($spec, $submission);
      }
    }

    return $this->buildMessage($recipientData, $senderLabel, $messageLabel, $document, $actions);
  }

  /**
   * Enrich recipient with additional data from a lookup.
   */
  private function enrichRecipient(Recipient $recipient, CprLookupResult|CompanyLookupResult|null $recipientData): Recipient {
    if ($recipientData instanceof CprLookupResult) {
      $name = $recipientData->getName();
      $recipient->setLabel($name);
      $address = (new Address())
        ->setCo('')
        ->setAddressLabel($recipientData->getStreet() ?: '')
        ->setHouseNumber($recipientData->getHouseNr() ?: '')
        ->setFloor($recipientData->getFloor() ?: '')
        ->setDoor($recipientData->getApartmentNr() ?: '')
        ->setZipCode($recipientData->getPostalCode() ?: '')
        ->setCity($recipientData->getCity() ?: '')
        ->setCountry('DA');
      $attentionData = (new AttentionData())
        ->setAttentionPerson((new AttentionPerson())
          ->setLabel($recipient->getLabel())
          ->setPersonID($recipient->getRecipientID())
        )
        ->setAddress($address);

      $recipient->setAttentionData($attentionData);
    }
    elseif ($recipientData instanceof CompanyLookupResult) {
      $name = $recipientData->getName();

      $recipient->setLabel($name);
      $address = (new Address())
        ->setCo('')
        ->setAddressLabel($recipientData->getStreet() ?: '')
        ->setHouseNumber($recipientData->getHouseNr() ?: '')
        ->setFloor($recipientData->getFloor() ?: '')
        ->setDoor($recipientData->getApartmentNr() ?: '')
        ->setZipCode($recipientData->getPostalCode() ?: '')
        ->setCity($recipientData->getCity() ?: '')
        ->setCountry('DA');
      $attentionData = (new AttentionData())
        ->setAttentionPerson((new AttentionPerson())
          ->setLabel($recipient->getLabel())
          ->setPersonID($recipient->getRecipientID())
        )
        ->setAddress($address);

      $recipient->setAttentionData($attentionData);
    }

    return $recipient;
  }

  /**
   * Build action.
   *
   * @phpstan-param array<string, mixed> $options
   */
  private function buildAction(array $options, WebformSubmissionInterface $submission): Action {
    $label = $this->replaceTokens($options['label'], $submission);
    $action = (new Action())
      ->setActionCode($options['action'])
      ->setLabel($label);
    if (SF1601::ACTION_AFTALE === $options['action']) {
      throw new \RuntimeException(sprintf('Cannot handle action %s', $options['action']));
    }
    elseif ($options['url']) {
      $url = $this->replaceTokens($options['url'], $submission);
      $action->setEntryPoint(
        (new EntryPoint())
          ->setUrl($url)
          );
    }

    return $action;
  }

}
