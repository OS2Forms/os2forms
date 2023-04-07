<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Link as CoreLink;
use Drupal\Core\Render\Element\Link;

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

    $nemlogin_link_login_text = NULL;
    if (isset($element['#nemlogin_link_login_text'])) {
      $nemlogin_link_login_text = $element['#nemlogin_link_login_text'];
    }

    $nemlogin_link_logout_text = NULL;
    if (isset($element['#nemlogin_link_logout_text'])) {
      $nemlogin_link_logout_text = $element['#nemlogin_link_logout_text'];
    }

    // Getting auth plugin ID override.
    $authPluginId = NULL;
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = \Drupal::request()->attributes->get('webform');
    $webformNemidSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');
    if (isset($webformNemidSettings['session_type']) && !empty($webformNemidSettings['session_type'])) {
      $authPluginId = $webformNemidSettings['session_type'];
    }

    // Checking if we have a share webform route, if yes open link in a new
    // tab.
    $webformShareRoutes = [
      'entity.webform.share_page',
      'entity.webform.share_page.javascript',
    ];
    $route_name = \Drupal::routeMatch()->getRouteName();

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
