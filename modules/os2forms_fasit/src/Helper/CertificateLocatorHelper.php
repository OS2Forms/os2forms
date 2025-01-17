<?php

namespace Drupal\os2forms_fasit\Helper;

use Drupal\os2forms_fasit\Exception\CertificateLocatorException;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Http\Factory\Guzzle\RequestFactory;
use ItkDev\AzureKeyVault\Authorisation\VaultToken;
use ItkDev\AzureKeyVault\KeyVault\VaultSecret;
use ItkDev\Serviceplatformen\Certificate\AzureKeyVaultCertificateLocator;
use ItkDev\Serviceplatformen\Certificate\CertificateLocatorInterface;
use ItkDev\Serviceplatformen\Certificate\FilesystemCertificateLocator;

/**
 * Certificate locator helper.
 */
class CertificateLocatorHelper {
  public const LOCATOR_TYPE = 'locator_type';
  public const LOCATOR_TYPE_AZURE_KEY_VAULT = 'azure_key_vault';
  public const LOCATOR_TYPE_HASHICORP_KEY_VAULT = 'hashicorp_key_vault';
  public const LOCATOR_TYPE_FILE_SYSTEM = 'file_system';
  public const LOCATOR_PASSPHRASE = 'passphrase';
  public const LOCATOR_AZURE_KEY_VAULT_TENANT_ID = 'tenant_id';
  public const LOCATOR_AZURE_KEY_VAULT_APPLICATION_ID = 'application_id';
  public const LOCATOR_AZURE_KEY_VAULT_CLIENT_SECRET = 'client_secret';
  public const LOCATOR_AZURE_KEY_VAULT_NAME = 'name';
  public const LOCATOR_AZURE_KEY_VAULT_SECRET = 'secret';
  public const LOCATOR_AZURE_KEY_VAULT_VERSION = 'version';
  public const LOCATOR_FILE_SYSTEM_PATH = 'path';

  /**
   * Constructor.
   */
  public function __construct(private readonly Settings $settings) {
  }

  /**
   * Get certificate locator.
   */
  public function getCertificateLocator(): CertificateLocatorInterface {
    $certificateSettings = $this->settings->getCertificate();

    $locatorType = $certificateSettings[self::LOCATOR_TYPE];
    $options = $certificateSettings[$locatorType];
    $options += [
      self::LOCATOR_PASSPHRASE => $certificateSettings[self::LOCATOR_PASSPHRASE] ?: '',
    ];

    if (self::LOCATOR_TYPE_AZURE_KEY_VAULT === $locatorType) {
      $httpClient = new GuzzleAdapter(new Client());
      $requestFactory = new RequestFactory();

      $vaultToken = new VaultToken($httpClient, $requestFactory);

      $token = $vaultToken->getToken(
        $options[self::LOCATOR_AZURE_KEY_VAULT_TENANT_ID],
        $options[self::LOCATOR_AZURE_KEY_VAULT_APPLICATION_ID],
        $options[self::LOCATOR_AZURE_KEY_VAULT_CLIENT_SECRET],
      );

      $vault = new VaultSecret(
        $httpClient,
        $requestFactory,
        $options[self::LOCATOR_AZURE_KEY_VAULT_NAME],
        $token->getAccessToken()
      );

      return new AzureKeyVaultCertificateLocator(
        $vault,
        $options[self::LOCATOR_AZURE_KEY_VAULT_SECRET],
        $options[self::LOCATOR_AZURE_KEY_VAULT_VERSION],
        $options[self::LOCATOR_PASSPHRASE],
      );
    }
    elseif (self::LOCATOR_TYPE_FILE_SYSTEM === $locatorType) {
      $certificatepath = realpath($options[self::LOCATOR_FILE_SYSTEM_PATH]) ?: NULL;
      if (NULL === $certificatepath) {
        throw new CertificateLocatorException(sprintf('Invalid certificate path %s', $options[self::LOCATOR_FILE_SYSTEM_PATH]));
      }
      return new FilesystemCertificateLocator($certificatepath, $options[self::LOCATOR_PASSPHRASE]);
    }

    throw new CertificateLocatorException(sprintf('Invalid certificate locator type: %s', $locatorType));
  }

}
