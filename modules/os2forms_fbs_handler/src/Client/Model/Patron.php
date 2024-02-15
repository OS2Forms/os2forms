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
    // Allow these properties below to be updatable.
    public ?string $emailAddress = NULL,
    public ?bool $receiveEmail = NULL,
    public ?string $preferredPickupBranch = NULL,
    public ?string $cpr = NULL,
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
      'receiveEmail' => $this->receiveEmail,
      'receiveSms' => $this->receiveSms,
      'receivePostalMail' => $this->receivePostalMail,
      'emailAddress' => $this->emailAddress,
      'notificationProtocols' => $this->notificationProtocols,
      'phoneNumber' => $this->phoneNumber,
      'preferredPickupBranch' => $this->preferredPickupBranch,
      'onHold' => $this->onHold,
      'preferredLanguage' => $this->preferredLanguage,
      'guardianVisibility' => $this->guardianVisibility,
    ];
  }

}
