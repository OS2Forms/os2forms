<?php

namespace Drupal\os2forms_audit\Service;

/**
 * Class LokiClient.
 *
 * This is based/inspired by https://github.com/itspire/monolog-loki.
 */
class LokiClient {

  /**
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
   * Default constructor
   * .
   * @param array $apiConfig
   *   Configuration for the loki connection.
   */
  public function __construct(
    array $apiConfig,
  ) {
    $this->entrypoint = $this->getEntrypoint($apiConfig['entrypoint']);
    $this->customCurlOptions = $apiConfig['curl_options'] ?? [];

    if (isset($apiConfig['auth']['basic'])) {
      $this->basicAuth = (2 === count($apiConfig['auth']['basic'])) ? $apiConfig['auth']['basic'] : [];
    }
  }

  /**
   * Send a log message to Loki ingestion endpoint.
   *
   * Message format sendt to loki (https://grafana.com/docs/loki/latest/reference/api/#ingest-logs)
   * in the following json format.
   * {
   *   "Streams": [
   *     {
   *       "stream": {
   *         "label": "value"
   *       },
   *       "values": [
   *           [ "<unix epoch in nanoseconds>", "<log line>", <structured metadata> ]
   *       ]
   *     }
   *   ]
   * }
   *
   * @param string $label
   *   Loki global label to use.
   * @param int $epoch
   *   Unix epoch in nanoseconds
   * @param string $line
   *   The log line to send.
   * @param array $metadata
   *   Structured metadata.
   *
   * @return void
   *
   * @throws \JsonException
   */
  public function send(string $label, int $epoch, string $line, array $metadata = []): void {
    $this->sendPacket([
      'streams' => [
        'stream' => [
          'label' => $label,
        ],
        'values' => [
          [$epoch, $line, $metadata],
        ],
      ],
    ]);
  }

  /**
   * Ensure the URL to entry point is correct
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
   * @return void
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
      curl_exec($this->connection);
    }
  }

}
