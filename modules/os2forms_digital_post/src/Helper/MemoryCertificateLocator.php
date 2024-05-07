<?php

namespace Drupal\os2forms_digital_post\Helper;

use ItkDev\Serviceplatformen\Certificate\AbstractCertificateLocator;
use ItkDev\Serviceplatformen\Certificate\Exception\CertificateLocatorException;

/**
 * Memory certificate locator.
 */
class MemoryCertificateLocator extends AbstractCertificateLocator {

  public function __construct(
    // The passwordless certificate.
    private readonly string $certificate,
  ) {
    parent::__construct('');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, string>
   */
  public function getCertificates(): array {
    $certificates = [];
    $this->passphrase = 'P5bISuw?s:u4';
    if (!openssl_pkcs12_read($this->certificate, $certificates, $this->passphrase)) {
      throw new CertificateLocatorException(sprintf('Could not read certificate: %s', openssl_error_string() ?: ''));
    }

    return $certificates;
  }

  /**
   * {@inheritdoc}
   */
  public function getCertificate(): string {
    return $this->certificate;
  }

  /**
   * {@inheritdoc}
   */
  public function getAbsolutePathToCertificate(): string {
    throw new CertificateLocatorException(__METHOD__ . ' should not be used.');
  }

}
