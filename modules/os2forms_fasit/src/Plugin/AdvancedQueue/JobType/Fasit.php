<?php

namespace Drupal\os2forms_fasit\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2forms_fasit\Helper\FasitHelper;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Fasit job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_fasit\Plugin\AdvancedQueue\JobType\Fasit",
 *   label = @Translation("Forward to Fasit"),
 * )
 */
class Fasit extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The Fasit helper.
   *
   * @var \Drupal\os2forms_fasit\Helper\FasitHelper
   */
  private FasitHelper $helper;

  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

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
      $container->get(FasitHelper::class),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FasitHelper $helper, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * Processes the Fasit job.
   */
  public function process(Job $job): JobResult {
    try {
      $payload = $job->getPayload();

      /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
      $webformSubmission = WebformSubmission::load($payload['submissionId']);
      $logger_context = [
        'handler_id' => 'os2forms_fasit',
        'channel' => 'webform_submission',
        'webform_submission' => $webformSubmission,
        'operation' => 'response from queue',
      ];

      try {
        $this->helper->process($payload['submissionId'], $payload['handlerConfiguration']);
        $this->submissionLogger->notice($this->t('The submission #@serial was successfully delivered', ['@serial' => $webformSubmission->serial()]), $logger_context);

        return JobResult::success();
      }
      catch (\Exception $e) {
        $this->submissionLogger->error($this->t('The submission #@serial failed (@message)', [
          '@serial' => $webformSubmission->serial(),
          '@message' => $e->getMessage(),
        ]), $logger_context);

        return JobResult::failure($e->getMessage());
      }
    }
    catch (\Exception $e) {
      return JobResult::failure($e->getMessage());
    }
  }

}
