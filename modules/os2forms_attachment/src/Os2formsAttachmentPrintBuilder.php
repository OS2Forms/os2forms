<?php

namespace Drupal\os2forms_attachment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\File\FileExists;
use Drupal\entity_print\Event\PreSendPrintEvent;
use Drupal\entity_print\Event\PrintEvents;
use Drupal\entity_print\Plugin\PrintEngineInterface;
use Drupal\entity_print\PrintBuilder;

/**
 * The OS2Forms attachment print builder service.
 */
class Os2formsAttachmentPrintBuilder extends PrintBuilder {

  /**
   * {@inheritdoc}
   */
  public function printHtml(EntityInterface $entity, $use_default_css = TRUE, $optimize_css = TRUE) {
    $renderer = $this->rendererFactory->create([$entity]);
    $content[] = $renderer->render([$entity]);

    $render = [
      '#theme' => 'entity_print__' . $entity->getEntityTypeId() . '__' . $entity->bundle(),
      '#title' => $entity->label(),
      '#content' => $content,
      '#attached' => [],
    ];
    return $renderer->generateHtml([$entity], $render, $use_default_css, $optimize_css);
  }

  /**
   * Modified version of the original savePrintable() function.
   *
   * The only difference is modified call to prepareRenderer with digitalPost
   * flag TRUE.
   *
   * @see PrintBuilder::savePrintable()
   *
   * @return string
   *   FALSE or the URI to the file. E.g. public://my-file.pdf.
   */
  public function savePrintableDigitalSignature(array $entities, PrintEngineInterface $print_engine, $scheme = 'public', $filename = FALSE, $use_default_css = TRUE) {
    $renderer = $this->prepareRenderer($entities, $print_engine, $use_default_css, TRUE);

    // Allow other modules to alter the generated Print object.
    $this->dispatcher->dispatch(new PreSendPrintEvent($print_engine, $entities), PrintEvents::PRE_SEND);

    // If we didn't have a URI passed in the generate one.
    if (!$filename) {
      $filename = $renderer->getFilename($entities) . '.' . $print_engine->getExportType()->getFileExtension();
    }

    $uri = "$scheme://$filename";

    // Save the file.
    return \Drupal::service('file_system')->saveData($print_engine->getBlob(), $uri, FileExists::Replace);
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Override prepareRenderer() the print engine with the passed entities.
   *
   * @param array $entities
   *   An array of entities.
   * @param \Drupal\entity_print\Plugin\PrintEngineInterface $print_engine
   *   The print engine.
   * @param bool $use_default_css
   *   TRUE if we want the default CSS included.
   * @param bool $digitalSignature
   *   If the digital signature message needs to be added.
   *
   * @return \Drupal\entity_print\Renderer\RendererInterface
   *   A print renderer.
   *
   * @see PrintBuilder::prepareRenderer
   */
  protected function prepareRenderer(array $entities, PrintEngineInterface $print_engine, $use_default_css, $digitalSignature = FALSE) {
    if (empty($entities)) {
      throw new \InvalidArgumentException('You must pass at least 1 entity');
    }

    $renderer = $this->rendererFactory->create($entities);
    $content = $renderer->render($entities);

    $first_entity = reset($entities);
    $render = [
      '#theme' => 'entity_print__' . $first_entity->getEntityTypeId() . '__' . $first_entity->bundle(),
      '#title' => $first_entity->label(),
      '#content' => $content,
      '#attached' => [],
    ];

    // Adding hardcoded negative margin to avoid margins in <fieldset> <legend>
    // structure. That margin is automatically added in PDF and PDF only.
    $generatedHtml = (string) $renderer->generateHtml($entities, $render, $use_default_css, TRUE);
    $generatedHtml .= "<style>fieldset legend {margin-left: -12px;}</style>";
    if ($digitalSignature) {
      $generatedHtml .= $this->t('You can validate the signature on this PDF file via validering.nemlog-in.dk.');
    }

    $print_engine->addPage($generatedHtml);

    return $renderer;
  }

}
