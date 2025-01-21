<?php

namespace Drupal\os2forms_fasit\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\key\KeyRepositoryInterface;
use Drupal\os2forms_fasit\Form\SettingsForm;

/**
 * General settings for os2forms_fasit.
 */
final class Settings {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private ImmutableConfig $config;

  /**
   * The constructor.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    private readonly KeyRepositoryInterface $keyRepository,
  ) {
    $this->config = $configFactory->get(SettingsForm::CONFIG_NAME);
  }

  /**
   * Get fasit api base url.
   */
  public function getFasitApiBaseUrl(): ?string {
    return $this->get(SettingsForm::FASIT_API_BASE_URL);
  }

  /**
   * Get fasit api tenant.
   */
  public function getFasitApiTenant(): ?string {
    return $this->get(SettingsForm::FASIT_API_TENANT);
  }

  /**
   * Get fasit api version.
   */
  public function getFasitApiVersion(): ?string {
    return $this->get(SettingsForm::FASIT_API_VERSION);
  }

  /**
   * Get Fasit configuration selector
   */
  public function getFasitCertificateConfig(): ?array {
    return $this->get(SettingsForm::CERTIFICATE);
  }

  /**
   * Get Fasit certificate provider.
   */
  public function getFasitCertificateProvider(): string {
    $config = $this->getFasitCertificateConfig();

    return $config[SettingsForm::CERTIFICATE_PROVIDER] ?? SettingsForm::PROVIDER_TYPE_FORM;
  }

  /**
   * Get Fasit certificate locator.
   */
  public function getFasitCertificateLocator(): string {
    $config = $this->getFasitCertificateConfig();

    return $config[CertificateLocatorHelper::LOCATOR_TYPE] ?? CertificateLocatorHelper::LOCATOR_TYPE_FILE_SYSTEM;
  }

  /**
   * Get Fasit key certificate configuration.
   */
  public function getFasitCertificateKey(): ?string {
    return $this->get(SettingsForm::KEY);
  }

  /**
   * Get certificate.
   */
  public function getKeyValue(): ?string {
    $key = $this->keyRepository->getKey(
      $this->getFasitCertificateKey(),
    );

    return $key?->getKeyValue();
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
  private function get(string $key, $default = NULL): mixed {
    return $this->config->get($key) ?? $default;
  }

}
