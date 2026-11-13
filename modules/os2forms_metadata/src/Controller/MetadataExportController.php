<?php

namespace Drupal\os2forms_metadata\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads accessible Webforms and their metadata as semicolon-separated CSV.
 *
 * @phpstan-consistent-constructor
 */
class MetadataExportController extends ControllerBase {

  /**
   * Constructs the metadata export controller.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected RendererInterface $renderer,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * Exports all accessible forms independently of overview filters and paging.
   */
  public function download(): StreamedResponse {
    $response = new StreamedResponse([$this, 'streamCsv']);
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="webforms-metadata.csv"');
    $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
    return $response;
  }

  /**
   * Writes the CSV content to the response output stream.
   */
  public function streamCsv(): void {
    /** @var \Drupal\os2forms_metadata\WebformMetadataListBuilder $builder */
    $builder = $this->entityTypeManager->getListBuilder('webform');
    $webforms = $builder->loadForExport();
    $header = $builder->buildHeader();

    // Operation links are not useful in the CSV.
    unset($header['operations']);

    $output = fopen('php://output', 'wb');
    if ($output === FALSE) {
      throw new \RuntimeException('Unable to open the CSV output stream.');
    }

    // A UTF-8 BOM lets Excel recognize Danish characters when opening the file.
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, array_map([$this, 'cellToText'], $header), ';', '"', '');

    foreach ($webforms as $webform) {
      $row = $builder->buildRow($webform);
      if (!$row) {
        continue;
      }
      $values = [];
      foreach (array_keys($header) as $key) {
        $values[] = $this->cellToText($row[$key]);
      }
      fputcsv($output, $values, ';', '"', '');
    }
    fclose($output);
  }

  /**
   * Converts an overview cell to plain text suitable for a spreadsheet.
   */
  protected function cellToText(mixed $cell): string {
    if (is_array($cell) && array_key_exists('data', $cell)) {
      $cell = $cell['data'];
    }
    if ($cell instanceof RenderableInterface) {
      $cell = $cell->toRenderable();
    }
    if (is_array($cell)) {
      $cell = $this->renderer->renderInIsolation($cell);
    }
    $value = trim(Html::decodeEntities(strip_tags((string) $cell)));

    // CSV quoting alone does not stop spreadsheet applications executing
    // formulas.
    if (preg_match('/^[\s]*[=+\-@]/u', $value)) {
      $value = "'" . $value;
    }

    return $value;
  }

}
