<?php

namespace Drupal\os2forms_nemid\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'OS2Forms Nemid Login/out link' block.
 *
 * @Block(
 *   id = "os2forms_nemid_login_logout_link",
 *   admin_label = @Translation("OS2Forms Nemid Login/out link")
 * )
 */
class NemidLoginLogoutLink extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_login_text' => $this->t('Login'),
      'link_logout_text' => $this->t('Logout'),
      'hide_login_button' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hide_login_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide login button'),
      '#default_value' => $this->configuration['hide_login_button'],
    ];
    $form['link_login_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login text'),
      '#description' => $this->t('A text on login link.'),
      '#default_value' => $this->configuration['link_login_text'],
      '#states' => [
        'visible' => [
          'input[name="settings[hide_login_button]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['link_logout_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logout text'),
      '#description' => $this->t('A text on logout link.'),
      '#default_value' => $this->configuration['link_logout_text'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hide_login_button'] = $form_state->getValue('hide_login_button');
    $this->configuration['link_login_text'] = $form_state->getValue('link_login_text');
    $this->configuration['link_logout_text'] = $form_state->getValue('link_logout_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');
    $plugin = $authProviderService->getActivePlugin();

    // Do nothing if there is no auth plugin.
    if (empty($plugin)) {
      return [];
    }

    if (!$plugin->isAuthenticated() && $this->configuration['hide_login_button']) {
      return [];
    }

    $link = $authProviderService->generateLink($this->configuration['link_login_text'], $this->configuration['link_logout_text']);
    $element['#title'] = $link->getText();
    $element['#url'] = $link->getUrl();
    $build['login_logout_link'] = [
      '#title' => $link->getText(),
      '#type' => 'link',
      '#url' => $link->getUrl(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
