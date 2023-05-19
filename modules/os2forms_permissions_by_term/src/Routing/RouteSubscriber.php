<?php

namespace Drupal\os2forms_permissions_by_term\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Disallow access to webform submission collection page showing submissions
    // from all webforms.
    // Submissions should only be accessible in relation to a specific webform.
    $route = $collection->get('entity.webform_submission.collection');
    if ($route) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
