<?php

/**
 * @file
 * Contains Drupal\securelogin\secureloginAPI.
 */

namespace Drupal\securelogin;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines the securelogin API service.
 */
class secureloginAPI {

  protected $response;
  protected $request;

  public function __construct(Request $request, RedirectResponse $response) {
    $this->request  = $request;
    $this->response = $response;
  }
  /**
   * Secures a form by altering its action to use the secure base URL.
   */
  public function securelogin_secure_form(&$form) {
    global $base_path, $base_secure_url, $is_https;
    // Flag form as secure for theming purposes.
    $form['#https'] = TRUE;
    if (!$is_https) {
      // Redirect to secure page, if enabled.
      if (\Drupal::config('securelogin.settings')->get('secure_forms')) {
        $this->securelogin_secure_redirect();
      }
      // Set the form action to use secure base URL in place of base path.
      if (strpos($form['#action'], $base_path) === 0) {
        $base_url = \Drupal::config('securelogin')->get('base_url');
        $form['#action'] = substr_replace($form['#action'], $base_url, 0, strlen($base_path) - 1);
      }
    }
  }

  /**
   * Redirects an insecure request to the same path on the secure base URL.
   */
  function securelogin_secure_redirect() {
    global $is_https;
    // POST requests are not redirected, to prevent unintentional redirects which
    // result in lost POST data. HTTPS requests are also not redirected.
    if ($is_https || $_SERVER['REQUEST_METHOD'] == 'POST') {
      return;
    }
    $path = drupal_is_front_page() ? '' : $_GET['q'];
    $http_response_code = 301;
    // Do not permit redirecting to an external URL.
    $options = array('query' => UrlHelper::filterQueryParameters($_GET['q']), 'https' => TRUE, 'external' => FALSE);
    // We don't use drupal_goto() here because we want to be able to use the
    // page cache, but let's pretend that we are.
    \Drupal::moduleHandler()->alter('drupal_goto', $path, $options, $http_response_code);
    // The 'Location' HTTP header must be absolute.
    $options['absolute'] = TRUE;
    $url = url($path, $options);
    $status = "$http_response_code Moved Permanently";
    $this->response->headers->set('Status', $status);
    $this->response->headers->set('Location', $url);
    // Drupal page cache requires a non-empty page body for some reason.
    print $status;
    // Mimic drupal_exit() and drupal_page_footer() and then exit.
    //module_invoke_all('exit', $url);
    \Drupal::ModuleHandler()->invokeAll('exit', $url);
    //drupal_session_commit();
    $session_manager = \Drupal::service('session_manager');
    $session_manager->save();
    if (\Drupal::config('system.performance')->get('cache.page.user_internal') && ($cache = drupal_page_set_cache($this->response, $this->request))) {
      foreach ($cache->data['headers'] as $name => $value) {
        $this->response->headers->set($name, $value);
      }
    }
    else {
      ob_flush();
    }
    exit;
  }
}
