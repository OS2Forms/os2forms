<?php

namespace Drupal\os2forms_fasit\Helper;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\os2forms_fasit\Exception\InvalidSettingException;
use Drupal\os2forms_fasit\Form\SettingsForm;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General settings for os2forms_fasit.
 */
final class Settings {
  /**
   * The store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private KeyValueStoreInterface $store;

  /**
   * The key value collection name.
   *
   * @var string
   */
  private $collection = 'os2forms_fasit';

  /**
   * The constructor.
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory) {
    $this->store = $keyValueFactory->get($this->collection);
  }

  /**
   * Get fasit api base url.
   */
  public function getFasitApiBaseUrl(): string {
    return $this->get(SettingsForm::FASIT_API_BASE_URL, '');
  }

  /**
   * Get fasit api base url.
   */
  public function getFasitApiTenant(): string {
    return $this->get(SettingsForm::FASIT_API_TENANT, '');
  }

  /**
   * Get fasit api base url.
   */
  public function getFasitApiVersion(): string {
    return $this->get(SettingsForm::FASIT_API_VERSION, '');
  }

  /**
   * Get certificate.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getCertificate(): array {
    $value = $this->get(SettingsForm::CERTIFICATE);
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
        SettingsForm::FASIT_API_BASE_URL => '',
        SettingsForm::FASIT_API_TENANT => '',
        SettingsForm::FASIT_API_VERSION => '',
        SettingsForm::CERTIFICATE => [],
      ]);
  }

}
