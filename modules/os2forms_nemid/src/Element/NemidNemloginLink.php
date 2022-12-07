<?php

namespace Drupal\os2forms_nemid\Element;

use Drupal\Core\Render\Element\Link;
use Drupal\Core\Link as CoreLink;

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

    $options = [];

    // Checking if we have a share webform route, if yes open link in a new
    // tab.
    $webformShareRoutes = ['entity.webform.share_page', 'entity.webform.share_page.javascript'];
    $route_name = \Drupal::routeMatch()->getRouteName();

    if (in_array($route_name, $webformShareRoutes)) {
      $element['#attributes']['target'] = '_blank';

      // Replacing return URL, as we are opening in a new window we want full
      // page webform, not embed form.
      /** @var \Drupal\webform\Entity\Webform $webform */
      $webform = \Drupal::request()->attributes->get('webform');
      if ($webform) {
        $options['query']['destination'] = $webform->toUrl()->toString();
      }
    }

    $link = $authProviderService->generateLink($nemlogin_link_login_text, $nemlogin_link_logout_text, $options);
    if ($link instanceof CoreLink) {
      $element['#title'] = $link->getText();
      $element['#url'] = $link->getUrl();
      $element['#attributes']['class'][] = 'nemlogin-button-link';
    }

    return parent::preRenderLink($element);
  }

}
