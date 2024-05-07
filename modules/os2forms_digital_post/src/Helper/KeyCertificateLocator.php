<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\key\KeyInterface;
use Drupal\os2web_key\CertificateHelper;
use ItkDev\Serviceplatformen\Certificate\AbstractCertificateLocator;
use ItkDev\Serviceplatformen\Certificate\Exception\CertificateLocatorException;

/**
 * Key certificate locator.
 */
class KeyCertificateLocator extends AbstractCertificateLocator {

  /**
   * The parsed certificates.
   *
   * @var array
   */
  private readonly array $certificates;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly KeyInterface $key,
    private readonly CertificateHelper $certificateHelper,
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
      $this->certificates = $this->certificateHelper->getCertificates($this->key);
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
