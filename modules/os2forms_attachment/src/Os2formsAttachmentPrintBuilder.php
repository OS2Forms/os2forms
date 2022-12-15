<?php

namespace Drupal\os2forms_attachment;

use Drupal\Core\Entity\EntityInterface;
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
   * {@inheritdoc}
   */
  protected function prepareRenderer(array $entities, PrintEngineInterface $print_engine, $use_default_css) {
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

    $print_engine->addPage($renderer->generateHtml($entities, $render, $use_default_css, TRUE));

    return $renderer;
  }

}
