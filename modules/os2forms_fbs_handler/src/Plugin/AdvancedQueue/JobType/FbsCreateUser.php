<?php

namespace Drupal\os2forms_fbs_handler\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2forms_fbs_handler\Client\FBS;
use Drupal\os2forms_fbs_handler\Client\Model\Guardian;
use Drupal\os2forms_fbs_handler\Client\Model\Patron;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Archive document job.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_fbs_handler\Plugin\AdvancedQueue\JobType\FbsCreateUser",
 *   label = @Translation("Create a user in fbs."),
 * )
 */
final class FbsCreateUser extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The submission logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $submissionLogger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $loggerFactory,
    protected readonly Client $client,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->submissionLogger = $loggerFactory->get('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    try {
      $payload = $job->getPayload();

      /** @var \Drupal\webform\WebformSubmissionInterface $webformSubmission */
      $webformSubmission = WebformSubmission::load($payload['submissionId']);
      $logger_context = [
        'handler_id' => 'os2forms_fbs',
        'channel' => 'webform_submission',
        'webform_submission' => $webformSubmission,
        'operation' => 'response from queue',
      ];
      $config = $payload['configuration'];

      try {
        $fbs = new FBS($this->client, $config['endpoint_url'], $config['agency_id'], $config['username'], $config['password']);

        // Log into FBS and obtain session.
        $fbs->login();

        $data = $webformSubmission->getData();

        // Checker child patron exists.
        $patron = $fbs->doUserExists($data['barn_cpr']);

        // Create Guardian.
        $guardian = new Guardian(
          $data['cpr'],
          $data['navn'],
          $data['email']
        );

        // If "yes" update the child patron and create the guardian (the
        // guardian is not another patron user).
        if (!is_null($patron)) {
          // Create Patron object with updated values.
          $patron->preferredPickupBranch = $data['afhentningssted'];
          $patron->emailAddress = $data['barn_mail'];
          $patron->receiveEmail = TRUE;
          $patron->cpr = $data['barn_cpr'];
          $patron->pincode = $data['pinkode'];

          $fbs->updatePatron($patron);
          $fbs->createGuardian($patron, $guardian);
        }
        else {
          // If "no" create child patron and guardian.
          $patron = new Patron();
          $patron->preferredPickupBranch = $data['afhentningssted'];
          $patron->emailAddress = $data['barn_mail'];
          $patron->receiveEmail = TRUE;
          $patron->cpr = $data['barn_cpr'];
          $patron->pincode = $data['pinkode'];

          $fbs->createPatronWithGuardian($patron, $guardian);
        }

        $this->submissionLogger->notice($this->t('The submission #@serial was successfully delivered', ['@serial' => $webformSubmission->serial()]), $logger_context);

        return JobResult::success();
      }
      catch (\Exception | GuzzleException $e) {
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
