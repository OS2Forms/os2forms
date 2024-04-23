<?php

namespace Drupal\os2forms_audit\Service;

/**
 * Interface for sending log messages to Loki ingestion endpoint.
 */
interface LokiClientInterface {

  /**
   * Send a log message to Loki ingestion endpoint.
   *
   * Message format sent to loki in the following json format.
   * {
   *  "Streams": [
   *    {
   *      "stream": {
   *        "label": "value"
   *      },
   *      "values": [
   *      [ "<unix epoch in nanoseconds>", "<log line>"]
   *      ]
   *    }
   *  ]
   * }
   *
   * @param string $label
   *   Loki global label to use.
   * @param int $epoch
   *   Unix epoch in nanoseconds.
   * @param string $line
   *   The log line to send.
   * @param array $metadata
   *   Extra labels/metadata to filter on.
   *
   * @see https://grafana.com/docs/loki/latest/reference/api/#ingest-logs
   */
  public function send(string $label, int $epoch, string $line, array $metadata = []): void;

}
