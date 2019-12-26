<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to ensure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * Place the config directory outside of the Drupal root.
 */
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config';

// Only enable Google Analytics on the live instance.
if ($_ENV['PANTHEON_ENVIRONMENT'] == 'live') {
  $config['google_analytics.settings']['account'] = 'UA-3149553-1';
}

/**
 * Define trusted host patters for SW.org.
 */
$settings['trusted_host_patterns'] = [
  '^.+sw-d8\.pantheonsite\.io',
  '^socialistworker\.org',
  '^.+\.socialistworker\.org',
];

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

/**
 * Always install the 'standard' profile to stop the installer from
 * modifying settings.php.
 */
$settings['install_profile'] = 'standard';
