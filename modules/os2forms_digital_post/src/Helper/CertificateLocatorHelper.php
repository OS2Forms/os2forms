<?php

namespace Drupal\os2forms_digital_post\Helper;

use Drupal\os2forms_digital_post\Exception\CertificateLocatorException;
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
   * {@inheritdoc}
   */
  public function __construct(
    private readonly Settings $settings,
  ) {
  }

  /**
   * Get certificate locator.
   */
  public function getCertificateLocator(): CertificateLocatorInterface {
    $certificateSettings = $this->settings->getEditableValue(Settings::CERTIFICATE);

    $locatorType = $certificateSettings['locator_type'];
    $options = $certificateSettings[$locatorType];
    $options += [
      'passphrase' => $certificateSettings['passphrase'] ?: '',
    ];

    if (self::LOCATOR_TYPE_AZURE_KEY_VAULT === $locatorType) {
      $httpClient = new GuzzleAdapter(new Client());
      $requestFactory = new RequestFactory();

      $vaultToken = new VaultToken($httpClient, $requestFactory);

      $token = $vaultToken->getToken(
        $options['tenant_id'],
        $options['application_id'],
        $options['client_secret'],
      );

      $vault = new VaultSecret(
        $httpClient,
        $requestFactory,
        $options['name'],
        $token->getAccessToken()
      );

      return new AzureKeyVaultCertificateLocator(
        $vault,
        $options['secret'],
        $options['version'],
        $options['passphrase'],
      );
    }
    elseif (self::LOCATOR_TYPE_FILE_SYSTEM === $locatorType) {
      $certificatepath = realpath($options['path']) ?: NULL;
      if (NULL === $certificatepath) {
        throw new CertificateLocatorException(sprintf('Invalid certificate path %s', $options['path']));
      }
      return new FilesystemCertificateLocator($certificatepath, $options['passphrase']);
    }

    throw new CertificateLocatorException(sprintf('Invalid certificate locator type: %s', $locatorType));
  }

}
