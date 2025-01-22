<?php

namespace Drupal\os2forms_fasit\Drush\Commands;

use Drupal\os2forms_fasit\Helper\FasitHelper;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class FasitTestCommand extends DrushCommands {

  /**
   * Constructs a FasitTestCommand object.
   */
  public function __construct(
    private readonly FasitHelper $helper,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(FasitHelper::class),
    );
  }

  /**
   * Test API access.
   *
   * @command os2forms-fasit:test:api
   * @usage os2forms-fasit:test:api --help
   */
  public function testApi(): void {
    try {
      $this->helper->pingApi();
      $this->io()->success('Successfully connected to Fasit API');
    }
    catch (\Throwable $t) {
      $this->io()->error($t->getMessage());
    }

  }

}
