<?php

/**
 * @file
 * Contains Drupal\securelogin\SecureloginManager.
 */

namespace Drupal\securelogin;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines the securelogin service.
 */
class SecureloginManager {

  protected $request;
  protected $config;

  public function __construct(Request $request, ConfigFactory $config) {
    $this->request = $request;
    $this->config = $config;
  }

  public function secureAction(&$form) {
    $securelogin = $this->config->get('securelogin.settings');
    // @TODO does this take in to account forms with external action?
    // Should those forms be allowed to be altered?
    // @TODO do we need to check if !$this->request->isSecure()
    if ($base_url = $securelogin->get('base_url')) {
      $form['#action'] = str_replace('http://', 'https://', $base_url) . $form['#action'];
    }
    else {
      $form['#action'] = str_replace('http://', 'https://', $this->request->getSchemeAndHttpHost()) . $form['#action'];
    }

    // Add in the https flag for theming purposes.
    $form['#https'] = TRUE;
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
