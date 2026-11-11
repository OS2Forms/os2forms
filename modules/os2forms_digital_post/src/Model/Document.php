<?php

namespace Drupal\os2forms_digital_post\Model;

/**
 * The Document class.
 */
class Document {
  public const LANGUAGE_DEFAULT = 'da';

  public const MIME_TYPE_PDF = 'application/pdf';

  /**
   * Allowed attachment types, extension => MIME type.
   *
   * @see https://www.digitaliser.dk/digital-post/vejledninger/tilladte-filtyper-og-navne
   */
  public const ATTACHMENT_MIME_TYPES = [
    'bmp' => 'image/bmp',
    'csv' => 'text/csv',
    'ddd' => 'application/vnd.fujixerox.ddd',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'dta' => 'application/x-stata-dta',
    'gif' => 'image/gif',
    'heic' => 'image/heic',
    'heif' => 'image/heic',
    'htm' => 'text/html',
    'html' => 'text/html',
    'ical' => 'text/calendar',
    'ics' => 'text/calendar',
    'jfif' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'kml' => 'application/vnd.google-earth.kml+xml',
    'mov' => 'video/quicktime',
    'mp3' => 'audio/mpeg',
    'mp4' => 'video/mp4',
    'odp' => 'application/vnd.oasis.opendocument.presentation',
    'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    'odt' => 'application/vnd.oasis.opendocument.text',
    'pdf' => self::MIME_TYPE_PDF,
    'png' => 'image/png',
    'rtf' => 'application/rtf',
    'sav' => 'application/x-spss-sav',
    'tif' => 'image/tiff',
    'tiff' => 'image/tiff',
    'txt' => 'text/plain',
    'wav' => 'audio/wav',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xml' => 'application/xml',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    readonly public string $content,
    readonly public string $mimeType,
    readonly public string $filename,
    readonly public string $language = self::LANGUAGE_DEFAULT,
  ) {
  }

  /**
   * Check if this document is a PDF.
   */
  public function isPdf(): bool {
    return Document::MIME_TYPE_PDF === $this->mimeType;
  }

  /**
   * Get the Digital Post MIME type for a filename, NULL if not allowed.
   */
  public static function getAttachmentMimeType(string $filename): ?string {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    return self::ATTACHMENT_MIME_TYPES[$extension] ?? NULL;
  }

}
