<?php

namespace Drupal\os2forms_digital_post\Model;

/**
 * The Document class.
 */
class Document {
  public const LANGUAGE_DEFAULT = 'da';

  public const MIME_TYPE_PDF = 'application/pdf';

  /**
   * Constructor.
   */
  public function __construct(
    readonly public string $content,
    readonly public string $mimeType,
    readonly public string $filename,
    readonly public string $language = self::LANGUAGE_DEFAULT
  ) {
  }

  /**
   * Check if this document is a PDF.
   */
  public function isPdf(): bool {
    return Document::MIME_TYPE_PDF === $this->mimeType;
  }

}
