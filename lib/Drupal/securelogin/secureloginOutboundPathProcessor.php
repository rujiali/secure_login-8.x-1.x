<?php
/**
 * @file
 * Contains \Drupal\securelogin\secureloginOutboundPathProcessor.php.
 */
namespace Drupal\securelogin;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class PathProcessorSecurelogin implements OutboundPathProcessorInterface {
  function processOutbound($path, &$options = array(), Request $request = NULL) {
    global $base_insecure_url, $base_secure_url;
    // Modules and themes may set the 'https' option to TRUE to generate HTTPS
    // URLs or FALSE to generate HTTP URLs.
    if (isset($options['https'])) {
      $options['base_url'] = $options['https'] ? \Drupal::config('securelogin.settings')->get('base_url') : $base_insecure_url;
      $options['absolute'] = TRUE;
    }
  }
}
