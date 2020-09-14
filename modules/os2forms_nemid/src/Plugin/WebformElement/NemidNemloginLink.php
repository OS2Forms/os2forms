<?php

namespace Drupal\os2forms_nemid\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;

/**
 * Provides a 'Nemlogin link' element.
 *
 * @WebformElement(
 *   id = "os2forms_nemid_nemlogin_link",
 *   label = @Translation("NemID Nemlogin link"),
 *   description = @Translation("Provides an NemID nemlogin link for initialing NemID authentification."),
 *   category = @Translation("NemID"),
 * )
 */
class NemidNemloginLink extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'attributes' => [],
      'nemlogin_link_login_text' => 'Login with Nemlogin',
      'nemlogin_link_logout_text' => 'Logout from Nemlogin',
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), [
      'nemlogin_link_login_text',
      'nemlogin_link_logout_text',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['#title'] = $this->t('OS2Forms Nemlogin Link settings');
    $form['markup']['nemlogin_link_login_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nemlogin link login text'),
      '#required' => TRUE,
    ];
    $form['markup']['nemlogin_link_logout_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nemlogin link logout text'),
      '#required' => TRUE,
    ];

    return $form;
  }

}
