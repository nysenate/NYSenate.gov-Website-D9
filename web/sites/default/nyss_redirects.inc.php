<?php

/**
 * nyss_redirects.inc.php - Perform various URL rewrites and redirects that
 * would normally be done in .htaccess
 *
 * Organization: New York State Senate
 * Project: NYSenate.gov Public Website
 * Author: Ken Zalewski
 * Date: 2017-12-30
 * Revised: 2018-02-09 - added URL redirects for committees
 *
 *
 * There are three components of the URL to consider:
 *   scheme (REQUEST_SCHEME)
 *   hostname (HTTP_HOST)
 *   path (REQUEST_URI)
 * Each component has its own rewriting rules associated with it.  If any
 * component ends up getting rewritten, then an HTTP redirect will be issued
 * at the end of this block.
 *
 */

require_once 'nyss_url_patterns.inc.php';


function get_server_value($server_param, $default_value = null)
{
  if (isset($_SERVER[$server_param])) {
    return $_SERVER[$server_param];
  }
  else {
    return $default_value;
  }
} // get_server_value()


$must_redirect = false;
$site_env = get_server_value('PANTHEON_ENVIRONMENT', 'unknown');
$url_hostname = get_server_value('HTTP_HOST', 'localhost');
$url_path = get_server_value('REQUEST_URI', '');
$url_patterns = get_nyss_url_patterns();

// Set the primary domain based on the Pantheon environment.
$primary_domain = $url_hostname;
switch ($site_env) {
  case 'dev':  $primary_domain = 'www-dev.nysenate.gov';  break;
  case 'test': $primary_domain = 'www-test.nysenate.gov'; break;
  case 'live': $primary_domain = 'www.nysenate.gov';      break;
}

// If the request scheme is not using SSL, flag the request for redirection.
if (!isset($_SERVER['HTTP_X_SSL']) || $_SERVER['HTTP_X_SSL'] != 'ON') {
  $must_redirect = true;
}

// Rewrite senator subdomains, committee subdomains, and other virtual hosts
// that match any of the URL patterns.
// URL patterns can be "full" or "abbreviated".  A full pattern begins with
// '^' and will not be augmented.  An abbreviated pattern is assumed to
// contain only the senator name or committee name, and will be augmented to
// form a full virtual hostname pattern.
// Similarly, the microsite can be "absolute" or "relative".  An absolute
// microsite begins with '/' and will not be augmented.  A relative microsite
// will be prefixed with the page type (either "/senators" or "/committees").
foreach ($url_patterns as $page_type => $url_map) {
  foreach ($url_map as $microsite => $pattern) {
    if ($pattern[0] == '^') {
      $full_pattern = "/$pattern/";
    }
    else {
      $full_pattern = "/^(www\.)?$pattern\.nysenate\.gov$/";
    }

    if (preg_match($full_pattern, $url_hostname) === 1) {
      $url_hostname = $primary_domain;
      $must_redirect = true;

      if ($url_path == '/') {
        // Since the original path is appended to the microsite path, this
        // prevents a trailing slash from being appended to the microsite path.
        $url_path = '';
      }

      // If the microsite is an absolute path, use it as is.  Otherwise,
      // prefix it with the page type.
      if ($microsite[0] == '/') {
        $url_path = "$microsite$url_path";
      }
      else {
        $url_path = "/$page_type/$microsite$url_path";
      }
      break 2;
    }
  }
}

// If any other virtual hostnames are used (eg. "nysenate.gov",
// "open.nysenate.gov"), set the hostname to the primary domain,
// unless it's a Pantheon hostname.
if ($url_hostname != $primary_domain && strpos($url_hostname, 'pantheon') === false) {
  $url_hostname = $primary_domain;
  $must_redirect = true;
}

// Rewrite old-style legislation URLs of the form:
//   "/legislation/bill/<billNumber>-<sessionYear>".
// This rewrite must happen before the other legislation rewrites.
if (preg_match('#^/legislation/bill/[^-]+\-[0-9]+$#i', $url_path) === 1) {
  $url_path = preg_replace('#^/legislation/bill/([SA][^-]+)\-([0-9]+)$#i', '/legislation/bills/$2/$1', $url_path);
  $url_path = preg_replace('#^/legislation/bill/([^SA][^-]+)\-([0-9]+)$#i', '/legislation/resolutions/$2/$1', $url_path);
  $must_redirect = true;
}

// Rewrite legislation URLs that include the amendment letter
// in the base print number and url parameters
if (preg_match('#^/legislation/(bills|resolutions)/[0-9]{4}/[A-Z][0-9]+[A-Z]\??.*$#i', $url_path) === 1) {
  $url_path = preg_replace('#([A-Z[0-9]+)([A-Z])\?#i', '$1/amendment/$2?', $url_path);
  $must_redirect = true;
}

// Rewrite legislation URLs that include the amendment letter
// in the base print number.
if (preg_match('#^/legislation/(bills|resolutions)/[0-9]{4}/[A-Z][0-9]+[A-Z]$#i', $url_path) === 1) {
  $url_path = preg_replace('#([A-Z])$#i', '/amendment/$1', $url_path);
  $must_redirect = true;
}

// Remove leading zeroes from bill numbers.
if (preg_match('#^/legislation/(bills|resolutions)/[0-9]{4}/[A-Z]0+[1-9]#i', $url_path) === 1) {
  $url_path = preg_replace('#(/[A-Z])0+([1-9])#i', '$1$2', $url_path);
  $must_redirect = true;
}

// If any component was changed, then $must_redirect is true and
// the request will be redirected.
if ($must_redirect) {
  header('HTTP/1.0 301 Moved Permanently');
  header("Location: https://$url_hostname$url_path");
  exit();
}

