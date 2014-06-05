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
  protected $response;

  public function __construct(Request $request) {
    $this->request = $request;
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
