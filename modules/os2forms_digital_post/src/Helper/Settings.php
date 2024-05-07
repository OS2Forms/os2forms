<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;

/**
 * General settings for os2forms_digital_post.
 */
final class Settings {
  public const CONFIG_NAME = 'os2forms_digital_post.settings';

  public const TEST_MODE = 'test_mode';

  public const SENDER = 'sender';
  public const SENDER_IDENTIFIER_TYPE = 'sender_identifier_type';
  public const SENDER_IDENTIFIER = 'sender_identifier';
  public const FORSENDELSES_TYPE_IDENTIFIKATOR = 'forsendelses_type_identifikator';

  public const CERTIFICATE = 'certificate';
  public const KEY = 'key';

  public const PROCESSING = 'processing';
  public const QUEUE = 'queue';

  /**
   * The runtime (immutable) config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $runtimeConfig;

  /**
   * The (mutable) config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private Config $editableConfig;

  /**
   * The constructor.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    private readonly KeyRepositoryInterface $keyRepository,
  ) {
    $this->runtimeConfig = $configFactory->get(self::CONFIG_NAME);
    $this->editableConfig = $configFactory->getEditable(self::CONFIG_NAME);
  }

  /**
   * Get test mode.
   */
  public function getTestMode(): bool {
    return (bool) $this->get(self::TEST_MODE, TRUE);
  }

  /**
   * Get sender.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getSender(): array {
    $value = $this->get(self::SENDER);

    return is_array($value) ? $value : [];
  }

  /**
   * Get key.
   */
  public function getKey(): ?string {
    return $this->get([self::CERTIFICATE, self::KEY]);
  }

  /**
   * Get certificate.
   */
  public function getCertificateKey(): ?KeyInterface {
    return $this->keyRepository->getKey(
      $this->getKey(),
    );
  }

  /**
   * Get processing.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getProcessing(): array {
    $value = $this->get(self::PROCESSING);

    return is_array($value) ? $value : [];
  }

  /**
   * Get editable value.
   *
   * @param string|array<string> $key
   *   The key.
   *
   * @return mixed
   *   The editable value.
   */
  public function getEditableValue(string|array $key): mixed {
    if (is_array($key)) {
      $key = implode('.', $key);
    }
    return $this->editableConfig->get($key);
  }

  /**
   * Get runtime value override if any.
   *
   * @param string|array<string> $key
   *   The key.
   *
   * @return array<string, mixed>|null
   *   - 'runtime': the runtime value
   *   - 'editable': the editable (raw) value
   */
  public function getOverride(string|array $key): ?array {
    $runtimeValue = $this->getRuntimeValue($key);
    $editableValue = $this->getEditableValue($key);

    // Note: We deliberately use "Equal" (==) rather than "Identical" (===)
    // to compare values (cf. https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison).
    if ($runtimeValue == $editableValue) {
      return NULL;
    }

    return [
      'runtime' => $runtimeValue,
      'editable' => $editableValue,
    ];
  }

  /**
   * Get a setting value.
   *
   * @param string|array<string> $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The setting value.
   */
  private function get(string|array $key, mixed $default = NULL) {
    return $this->getRuntimeValue($key) ?? $default;
  }

  /**
   * Get runtime value with any overrides applied.
   *
   * @param string|array<string> $key
   *   The key.
   *
   * @return mixed
   *   The runtime value.
   */
  public function getRuntimeValue(string|array $key): mixed {
    if (is_array($key)) {
      $key = implode('.', $key);
    }
    return $this->runtimeConfig->get($key);
  }

}
