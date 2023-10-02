<?php

namespace Drupal\os2forms_digital_post\Helper;

/**
 * Helper for SF1461.
 *
 * @see https://digitaliseringskataloget.dk/integration/sf1461
 */
class SF1461Helper {

  /**
   * Build response document.
   *
   * See "BeskedFåTilsendt" on
   * https://digitaliseringskataloget.dk/integration/sf1461.
   */
  public function buildResponseDocument(int $statusCode, string $errorMessage = NULL): \DOMDocument {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<ns2:ModtagBeskedOutputType xmlns="urn:oio:sag-dok:3.0.0" xmlns:ns2="urn:oio:sts:1.0.0">
 <StandardRetur>
  <StatusKode/>
  <FejlbeskedTekst/>
 </StandardRetur>
</ns2:ModtagBeskedOutputType>
XML;

    $document = new \DOMDocument();
    $document->loadXML($xml);
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('default', 'urn:oio:sag-dok:3.0.0');

    $xpath->query('//default:StatusKode')->item(0)->nodeValue = $statusCode;
    $xpath->query('//default:FejlbeskedTekst')->item(0)->nodeValue = $errorMessage;

    return $document;
  }

}
