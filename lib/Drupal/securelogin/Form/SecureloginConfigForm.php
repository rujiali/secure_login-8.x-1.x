<?php
/**
 * @file
 * Contains \Drupal\securelogin\Form\secureloginConfigForm.
 */
namespace Drupal\securelogin\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\system\Form;
use Drupal\Component\Utility\UrlHelper;
/**
 * Implements a ChosenConfig form.
 */
class secureloginConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'securelogin_config_form';
  }

  /**
   * securelogin configuration form.
   *
   * @return
   *   the form array
   */
  public function buildForm(array $form, array &$form_state) {
    global $base_secure_url;
    global $is_https;
    if ($is_https) {
      drupal_set_message(t('Secure Login module expects the Drupal <code>$conf[\'https\']</code> setting to be at its default value: <code>FALSE</code>. Because it is currently enabled, secure logins cannot be fully implemented because Drupal sets insecure session cookies during login to the secure site.'), 'warning');
    }

    // securelogin settings
    $securelogin_conf = \Drupal::config('securelogin.settings');
    $securelogin_base_url = $securelogin_conf->get('base_url');
    $securelogin_secure_forms = $securelogin_conf->get('secure_forms');
    $securelogin_all_forms = $securelogin_conf->get('all_forms');
    $user_email_verification = $securelogin_conf->get('user_email_verification');
    $securelogin_other_forms = $securelogin_conf->get('other_forms');

    $form['base_url'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Secure base URL'),
      '#default_value' => $securelogin_base_url,
      '#description'   => t('The base URL for secure pages. Leave blank to allow Drupal to determine it automatically. It is not allowed to have a trailing slash; Drupal will add it for you. For example: %base_secure_url%. Note that in order for cookies to work, the hostnames in the secure base URL and the insecure base URL must be in the same domain as per the appropriate setting in <code>settings.php</code>, which you may need to modify.', array('%base_secure_url%' => $base_secure_url)),
    );
    $form['secure_forms'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Redirect form pages to secure URL'),
      '#default_value' => $securelogin_secure_forms,
      '#description'   => t('If enabled, any pages containing the forms enabled below will be redirected to the secure URL. Users can be assured that they are entering their private data on a secure URL, the contents of which have not been tampered with.'),
    );
    $form['all_forms'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Submit all forms to secure URL'),
      '#default_value' => $securelogin_all_forms,
      '#description'   => t('If enabled, all forms will be submitted to the secure URL.'),
    );
    $form['required'] = array(
      '#type'          => 'fieldset',
      '#title'         => t('Required forms'),
      '#description'   => t('If enabled, the following forms will be submitted to the secure URL. These forms must be secured in order to implement basic secure login functionality.'),
    );
    $form['optional'] = array(
      '#type'          => 'fieldset',
      '#title'         => t('Optional forms'),
      '#description'   => t('Other forms accessible to anonymous users may optionally be secured. If enabled, the following forms will be submitted to the secure URL.'),
    );
    $forms['user_login_form'] = array('group' => 'required', 'title' => t('User login form'));
    $forms['user_login_block'] = array('group' => 'required', 'title' => t('User login block form'));
    $forms['user_pass_reset'] = array('group' => 'required', 'title' => t('User password reset form'));
    $forms['user_profile_form'] = array('group' => 'required', 'title' => t('User edit form'));
    // Registration form is also a login form if e-mail verification is disabled.
    $register = $user_email_verification ? 'optional' : 'required';
    $forms['user_register_form'] = array('group' => $register, 'title' => t('User registration form'));
    $forms['user_pass'] = array('group' => 'optional', 'title' => t('User password request form'));
    $forms['node_form'] = array('group' => 'optional', 'title' => t('Node form'));
    \Drupal::moduleHandler()->alter('securelogin', $forms);
    foreach ($forms as $id => $item) {
      $form[$item['group']]['form_' . $id] = array(
        '#type'          => 'checkbox',
        '#title'         => $item['title'],
        '#default_value' => $securelogin_conf->get('form_' . $id),
      );
    }
    $form['other_forms'] = array(
      '#type' => 'textfield',
      '#title' => t('Other forms to secure'),
      '#default_value' => $securelogin_other_forms,
      '#description' => t('List the form IDs of any other forms that you want secured, separated by a space. If the form has a base form ID, you must list the base form ID rather than the form ID.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('submit'),
    );

    return $form;
  }

  /**
   * Securelogin configuration form submit handler.
   */
  public function submitForm(array &$form, array &$form_state) {

    \Drupal::config('securelogin.settings')
      ->set('base_url', $form_state['values']['base_url'])
      ->set('secure_forms', $form_state['values']['secure_forms'])
      ->set('all_forms', $form_state['values']['all_forms'])
      ->set('user_email_verification', $form_state['values']['user_email_verification'])
      ->set('other_forms', $form_state['values']['other_forms'])
      ->set('form_user_login_form', $form_state['values']['form_user_login_form'])
      ->set('form_user_login_block', $form_state['values']['form_user_login_block'])
      ->set('form_user_pass_reset', $form_state['values']['form_user_pass_reset'])
      ->set('form_user_profile_form', $form_state['values']['form_user_profile_form'])
      ->set('form_user_register_form', $form_state['values']['form_user_register_form'])
      ->set('form_user_pass', $form_state['values']['form_user_pass'])
      ->set('form_node_form', $form_state['values']['form_node_form'])
      ->save();

    drupal_flush_all_caches();
  }

  /**
   * Securelogin condiguration form validation.
   */
  public function validateForm(array &$form, array &$form_state) {
    if (empty($form_state['values']['base_url'])) {
      $form_state['values']['base_url'] = NULL;
    }
    elseif (!UrlHelper::isValid($form_state['values']['base_url'], TRUE)) {
      $this->setFormError('base_url', $form_state, t('The secure base URL must be a valid URL.'));
    }
    elseif (strtolower(parse_url($form_state['values']['base_url'], PHP_URL_SCHEME)) !== 'https') {
      $this->setFormError('base_url', $form_state, t('The secure base URL must start with <em>https://</em>.'));
    }
  }
}