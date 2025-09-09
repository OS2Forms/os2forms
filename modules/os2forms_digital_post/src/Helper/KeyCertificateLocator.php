<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\key\KeyInterface;
use Drupal\os2web_key\KeyHelper;
use ItkDev\Serviceplatformen\Certificate\AbstractCertificateLocator;
use ItkDev\Serviceplatformen\Certificate\Exception\CertificateLocatorException;

/**
 * Key certificate locator.
 */
class KeyCertificateLocator extends AbstractCertificateLocator {

  /**
   * The parsed certificates.
   *
   * @var array<string, string>
   */
  private array $certificates;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly KeyInterface $key,
    private readonly KeyHelper $keyHelper,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, string>
   */
  public function getCertificates(): array {
    if (!isset($this->certificates)) {
      $this->certificates = $this->keyHelper->getCertificates($this->key);
    }

    return $this->certificates;
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificate(): string {
    return $this->key->getKeyValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getAbsolutePathToCertificate(): string {
    throw new CertificateLocatorException(__METHOD__ . ' should not be used.');
  }

}
