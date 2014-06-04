<?php

/**
 * @file
 * Contains Drupal\securelogin\SecureloginManager.
 */

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the securelogin service.
 */
class SecureloginManager {

  protected $request;
  protected $config;
  protected $response;

  public function __construct(Request $request, ConfigFactory $config) {
    $this->request = $request;
    $this->config = $config;
  }

  /**
   * Alters the action of a form such that it will POST to a secure address.
   *
   * @TODO is this method needed anymore?
   * @param $form
   */
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
  function redirect() {

    // POST requests are not redirected, to prevent unintentional redirects which
    // result in lost POST data. HTTPS requests are also not redirected.
    if ($this->request->isSecure() || $this->request->isMethod('POST')) {
      return;
    }

    // Get the current uri and convert HTTPS-ify it.
    $url = str_replace('http://', 'https://', $this->request->getUri());
    $this->response = new RedirectResponse($url, Response::HTTP_MOVED_PERMANENTLY);

    // We don't use drupal_goto() here because we want to be able to use the
    // page cache, but let's pretend that we are.
    // @TODO investigate if this is still needed
    \Drupal::moduleHandler()->alter('drupal_goto', $path, $options, $http_response_code);

    // Mimic the standard functions that run at the end of a request.
    $session_manager = \Drupal::service('session_manager');
    $session_manager->save();

    if (\Drupal::config('system.performance')->get('cache.page.use_internal')) {
      drupal_page_set_cache($this->response, $this->request);
    }
    $this->response->send();
  }

}
