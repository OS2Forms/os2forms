<?php

namespace Drupal\os2forms_audit\Service;

/**
 * Class LokiClient.
 *
 * This is based/inspired by https://github.com/itspire/monolog-loki.
 */
class LokiClient implements LokiClientInterface {

  /**
   * Location of the loki entry point.
   *
   * @var string|null
   */
  protected ?string $entrypoint;

  /**
   * Basic authentication username and password.
   *
   * @var array<string>
   */
  protected array $basicAuth = [];

  /**
   * Custom options for CURL command.
   *
   * @var array<string, string>
   */
  protected array $customCurlOptions = [];

  /**
   * Curl handler.
   *
   * @var \CurlHandle|null
   */
  private ?\CurlHandle $connection = NULL;

  /**
   * Default constructor.
   *
   * @param array $apiConfig
   *   Configuration for the loki connection.
   */
  public function __construct(
    array $apiConfig,
  ) {
    $this->entrypoint = $this->getEntrypoint($apiConfig['entrypoint']);
    $this->customCurlOptions = $apiConfig['curl_options'] ?? [];

    if (isset($apiConfig['auth']) && !empty($apiConfig['auth']['username']) && !empty($apiConfig['auth']['password'])) {
      $this->basicAuth = $apiConfig['auth'];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \JsonException
   */
  public function send(string $label, int $epoch, string $line, array $metadata = []): void {
    $packet = [
      'streams' => [
        [
          'stream' => [
            'app' => 'os2forms',
            'type' => $label,
          ],
          'values' => [
            [(string) $epoch, $line],
          ],
        ],
      ],
    ];

    if (!empty($metadata)) {
      $packet['streams'][0]['stream'] += $metadata;
    }

    $this->sendPacket($packet);
  }

  /**
   * Ensure the URL to entry point is correct.
   *
   * @param string $entrypoint
   *   Entry point URL.
   *
   * @return string
   *   The entry point URL formatted without a slash in the ending.
   */
  private function getEntrypoint(string $entrypoint): string {
    if (!str_ends_with($entrypoint, '/')) {
      return $entrypoint;
    }

    return substr($entrypoint, 0, -1);
  }

  /**
   * Send a packet to the Loki ingestion endpoint.
   *
   * @param array $packet
   *   The packet to send.
   *
   * @throws \JsonException
   *    If unable to encode the packet to JSON.
   * @throws \LogicException
   *   If unable to connect to the Loki endpoint.
   */
  private function sendPacket(array $packet): void {
    $payload = json_encode($packet, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $url = sprintf('%s/loki/api/v1/push', $this->entrypoint);

    if (NULL === $this->connection) {
      $this->connection = curl_init($url);

      if (FALSE === $this->connection) {
        throw new \LogicException('Unable to connect to ' . $url);
      }
    }

    if (FALSE !== $this->connection) {
      $curlOptions = array_replace(
        [
          CURLOPT_CONNECTTIMEOUT_MS => 500,
          CURLOPT_TIMEOUT_MS => 200,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_POSTFIELDS => $payload,
          CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
          ],
        ],
        $this->customCurlOptions
      );

      if (!empty($this->basicAuth)) {
        $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $curlOptions[CURLOPT_USERPWD] = implode(':', $this->basicAuth);
      }

      curl_setopt_array($this->connection, $curlOptions);
      $result = curl_exec($this->connection);

      if (FALSE === $result) {
        throw new \RuntimeException('Error sending packet to Loki');
      }

      if (curl_errno($this->connection)) {
        echo 'Curl error: ' . curl_error($this->connection);
      }
      else {
        echo 'Curl result: ' . $result;
      }
    }
  }

}
