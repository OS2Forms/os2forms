<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Link as CoreLink;
use Drupal\Core\Render\Element\Link;
use Drupal\webform\Entity\Webform;

/**
 * Provides a render element for more.
 *
 * @FormElement("os2forms_nemid_nemlogin_link")
 */
class NemidNemloginLink extends Link {

  /**
   * {@inheritdoc}
   */
  public static function preRenderLink($element) {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    $route_name = \Drupal::routeMatch()->getRouteName();

    $nemlogin_link_login_text = NULL;
    if (isset($element['#nemlogin_link_login_text'])) {
      $nemlogin_link_login_text = $element['#nemlogin_link_login_text'];
    }

    $nemlogin_link_logout_text = NULL;
    if (isset($element['#nemlogin_link_logout_text'])) {
      $nemlogin_link_logout_text = $element['#nemlogin_link_logout_text'];
    }

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = NULL;

    $webformShareRoutes = [
      'entity.webform.share_page',
      'entity.webform.share_page.javascript',
    ];

    if ($route_name === 'entity.webform.canonical' || in_array($route_name, $webformShareRoutes) ) {
      $webform = \Drupal::request()->attributes->get('webform');
    }
    elseif ($route_name == 'entity.node.canonical') {
      $node = \Drupal::request()->attributes->get('node');
      $nodeType = $node->getType();

      // Search if this node type is related with field of type 'webform'.
      $webformFieldMap = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('webform');
      if (isset($webformFieldMap['node'])) {
        foreach ($webformFieldMap['node'] as $field_name => $field_meta) {
          // We found field of type 'webform' in this node, let's try fetching
          // the webform.
          if (in_array($nodeType, $field_meta['bundles'])) {
            if ($webformId = $node->get($field_name)->target_id) {
              $webform = Webform::load($webformId);
              break;
            }
          }
        }
      }
    }

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    if ($webform) {
      $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');
      if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
        $authPluginId = $webformNemidSettings['session_type'];
      }
    }

    // Checking if we have a share webform route, if yes open link in a new
    // tab.
    $options = [];
    if (in_array($route_name, $webformShareRoutes)) {
      $element['#attributes']['target'] = '_blank';

      // Replacing return URL, as we are opening in a new window we want full
      // page webform, not embed form.
      if ($webform) {
        $options['query']['destination'] = $webform->toUrl()->toString();
      }
    }

    $link = $authProviderService->generateLink($nemlogin_link_login_text, $nemlogin_link_logout_text, $options, $authPluginId);
    if ($link instanceof CoreLink) {
      $element['#title'] = $link->getText();
      $element['#url'] = $link->getUrl();
      $element['#attributes']['class'][] = 'nemlogin-button-link';
    }

    return parent::preRenderLink($element);
  }

}
