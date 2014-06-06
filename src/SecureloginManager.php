<?php

/**
 * @file
 * Contains Drupal\securelogin\SecureloginManager.
 */

namespace Drupal\securelogin;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   * Redirects an insecure request to the same path on the secure base URL.
   */
  function redirect() {

    // POST requests are not redirected, to prevent unintentional redirects which
    // result in lost POST data. HTTPS requests are also not redirected.
    if ($this->request->isSecure() || $this->request->isMethod('POST')) {
      return;
    }

    // Get the current uri and convert it to HTTPS.
    $secure_url = $this->config->get('securelogin.settings')->get('base_url');
    $url = isset($secure_url) ? $secure_url : str_replace('http://', 'https://', $this->request->getUri());
    $this->response = new RedirectResponse($url, RedirectResponse::HTTP_MOVED_PERMANENTLY);

    // Mimic the standard functions that run at the end of a request.
    $session_manager = \Drupal::service('session_manager');
    $session_manager->save();

    if (\Drupal::config('system.performance')->get('cache.page.use_internal')) {
      drupal_page_set_cache($this->response, $this->request);
    }
    $this->response->send();
  }

}
