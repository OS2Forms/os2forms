<?php

namespace Drupal\os2forms_digital_post\Plugin\AdvancedQueue\JobType;

use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os2forms_digital_post\Helper\WebformHelperSF1601;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send digital post.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_digital_post\Plugin\AdvancedQueue\JobType\SendDigitalPostSF1601",
 *   label = @Translation("Send digital post (sf1601)"),
 *   max_retries = 5,
 *   retry_delay = 60,
 * )
 */
final class SendDigitalPostSF1601 extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The webform helper.
   *
   * @var \Drupal\os2forms_digital_post\Helper\WebformHelperSF1601
   */
  private WebformHelperSF1601 $helper;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(WebformHelperSF1601::class)
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    WebformHelperSF1601 $helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    return $this->helper->processJob($job);
  }

}
