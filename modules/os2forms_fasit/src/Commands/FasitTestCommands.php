<?php

namespace Drupal\os2forms_fasit\Commands;

use Drupal\os2forms_fasit\Helper\FasitHelper;
use Drush\Commands\DrushCommands;

/**
 * Test commands for fasit.
 */
class FasitTestCommands extends DrushCommands {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly FasitHelper $helper,
  ) {
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
