<?php

namespace Drupal\os2forms_fbs_handler\Client\Model;

/**
 * Wrapper class to represent and guardian.
 */
final class Guardian {

  /**
   * Default constructor.
   */
  public function __construct(
    protected readonly string $cpr,
    protected readonly string $name,
    protected readonly string $email,
  ) {
  }

  /**
   * Convert object to array with fields required in FBS.
   *
   * @return array
   *   Array with field required by FBS calls.
   *
   * @phpstan-return array<string, string>
   */
  public function toArray(): array {
    return [
      'personIdentifier' => $this->cpr,
      'name' => $this->name,
      'email' => $this->email,
    ];
  }

}
