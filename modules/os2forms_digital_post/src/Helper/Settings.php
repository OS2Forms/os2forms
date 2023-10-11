<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\os2forms_digital_post\Exception\InvalidSettingException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General settings for os2forms_digital_post.
 */
final class Settings {
  public const SENDER_IDENTIFIER_TYPE = 'sender_identifier_type';
  public const SENDER_IDENTIFIER = 'sender_identifier';
  public const FORSENDELSES_TYPE_IDENTIFIKATOR = 'forsendelses_type_identifikator';

  /**
   * The store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private KeyValueStoreInterface $store;

  /**
   * The key prefix.
   *
   * @var string
   */
  private $collection = 'os2forms_digital_post.';

  /**
   * Constructor.
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory) {
    $this->store = $keyValueFactory->get($this->collection);
  }

  /**
   * Get test mode.
   */
  public function getTestMode(): bool {
    return (bool) $this->get('test_mode', TRUE);
  }

  /**
   * Get sender.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getSender(): array {
    $value = $this->get('sender');
    return is_array($value) ? $value : [];
  }

  /**
   * Get certificate.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getCertificate(): array {
    $value = $this->get('certificate');
    return is_array($value) ? $value : [];
  }

  /**
   * Get processing.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getProcessing(): array {
    $value = $this->get('processing');
    return is_array($value) ? $value : [];
  }

  /**
   * Get a setting value.
   *
   * @param string $key
   *   The key.
   * @param mixed|null $default
   *   The default value.
   *
   * @return mixed
   *   The setting value.
   */
  private function get(string $key, $default = NULL) {
    $resolver = $this->getSettingsResolver();
    if (!$resolver->isDefined($key)) {
      throw new InvalidSettingException(sprintf('Setting %s is not defined', $key));
    }

    return $this->store->get($key, $default);
  }

  /**
   * Set settings.
   *
   * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
   *
   * @phpstan-param array<string, mixed> $settings
   */
  public function setSettings(array $settings): self {
    $settings = $this->getSettingsResolver()->resolve($settings);
    foreach ($settings as $key => $value) {
      $this->store->set($key, $value);
    }

    return $this;
  }

  /**
   * Get settings resolver.
   */
  private function getSettingsResolver(): OptionsResolver {
    return (new OptionsResolver())
      ->setDefaults([
        'test_mode' => TRUE,
        'sender' => [],
        'certificate' => [],
        'processing' => [],
      ]);
  }

}
