<?php

namespace Drupal\os2forms_forloeb\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_forloeb\MaestroHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send Maestro notification.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_forloeb\Plugin\AdvancedQueue\JobType\SendMeastroNotification",
 *   label = @Translation("Send Meastro notification"),
 *   max_retries = 5,
 *   retry_delay = 60,
 * )
 */
final class SendMeastroNotification extends JobTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(MaestroHelper::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    readonly private MaestroHelper $helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    return $this->helper->processJob($job);
  }

}
