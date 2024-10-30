<?php
/*
Plugin Name: Category Tools for WooCommerce
Description: A plugin that simplifies bulk category management in WooCommerce, allowing you to easily add or remove categories from multiple products at once.
Version: 1.0.1
Author: Palaventura
License: GPLv2 or later
Text Domain: category-tools-for-woocommerce
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Checks if WooCommerce is installed and active.
 */
function ctfw_check_woocommerce_installed()
{
  if (!class_exists('WooCommerce')) {
    add_action('admin_notices', 'ctfw_display_woocommerce_error');

    return false;
  }

  return true;
}

/**
 * Displays an error notice if WooCommerce is not installed.
 */
function ctfw_display_woocommerce_error()
{
  ?>
    <div class="error notice">
        <p><?php echo esc_html__('Category Tools for WooCommerce requires WooCommerce to be installed and activated.', 'category-tools-for-woocommerce'); ?></p>
    </div>
    <?php
}

/**
 * Initializes the Category Tools for WooCommerce plugin.
 *
 * This function is hooked into 'plugins_loaded' and is responsible for
 * loading the main plugin loader class and starting the plugin.
 */
function ctfw_init()
{
  if (!ctfw_check_woocommerce_installed()) {
    error_log('CTFW | WooCommerce is not installed or active. Category Tools for WooCommerce plugin will not be initialized.');

    return;
  }

  try {
    require_once plugin_dir_path(__FILE__) . 'includes/class-ctfw-loader.php';

    $loader = new CTFW_Loader();
    $loader->run();
  } catch (Exception $e) {
    error_log('CTFW | Error initializing Category Tools for WooCommerce plugin: ' . esc_html($e->getMessage()));
    wp_die(esc_html__('There was an error initializing the Category Tools for WooCommerce plugin. Please check the error logs for more details.', 'category-tools-for-woocommerce'));
  }
}
add_action('plugins_loaded', 'ctfw_init');
