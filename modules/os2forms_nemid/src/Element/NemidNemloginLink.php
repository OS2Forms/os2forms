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

    $link = $authProviderService->generateLink($nemlogin_link_login_text, $nemlogin_link_logout_text);
    if ($link instanceof CoreLink) {
      $element['#title'] = $link->getText();
      $element['#url'] = $link->getUrl();
    }

    return parent::preRenderLink($element);
  }

}
