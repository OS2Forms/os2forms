<?php

namespace Drupal\os2forms_fbs_handler\Client\Model;

/**
 * Wrapper class to represent and patron.
 */
final class Patron {

  /**
   * Default constructor.
   */
  public function __construct(
    public readonly ?string $patronId = NULL,
    public readonly ?bool $receiveSms = FALSE,
    public readonly ?bool $receivePostalMail = FALSE,
    public readonly ?array $notificationProtocols = NULL,
    public readonly ?string $phoneNumber = NULL,
    public readonly ?array $onHold = NULL,
    public readonly ?string $preferredLanguage = NULL,
    public readonly ?bool $guardianVisibility = NULL,
    public readonly ?int $defaultInterestPeriod = NULL,
    public readonly ?bool $resident = NULL,
    // Allow these properties below to be updatable.
    public ?array $emailAddresses = NULL,
    public ?bool $receiveEmail = NULL,
    public ?string $preferredPickupBranch = NULL,
    public ?string $personId = NULL,
    public ?string $pincode = NULL,
  ) {
  }

  /**
   * Convert object to array with fields required in FBS.
   *
   * @return array
   *   Array with field required by FBS calls.
   */
  public function toArray(): array {
    return [
      'patronId' => $this->patronId,
      'receiveEmail' => $this->receiveEmail,
      'receiveSms' => $this->receiveSms,
      'receivePostalMail' => $this->receivePostalMail,
      'emailAddresses' => $this->emailAddresses,
      'notificationProtocols' => $this->notificationProtocols,
      'phoneNumber' => $this->phoneNumber,
      'preferredPickupBranch' => $this->preferredPickupBranch,
      'onHold' => $this->onHold,
      'preferredLanguage' => $this->preferredLanguage,
      'guardianVisibility' => $this->guardianVisibility,
      'defaultInterestPeriod' => $this->defaultInterestPeriod,
      'resident' => $this->resident,
    ];
  }

}
