<?php

class CTFW_Loader
{
  /**
   * Runs the plugin by loading dependencies and defining admin hooks.
   */
  public function run()
  {
    try {
      $this->load_dependencies();
      $this->define_admin_hooks();
    } catch (Exception $e) {
      error_log('CTFW | Error in Category Tools for WooCommerce loader: ' . esc_html($e->getMessage()));
      wp_die(esc_html__('An error occurred while loading the Category Tools for WooCommerce plugin. Please check the error logs for more details.', 'category-tools-for-woocommerce'));
    }
  }

  /**
   * Loads the required dependencies for this plugin.
   *
   * This includes the necessary admin classes and any other components required by the plugin.
   */
  private function load_dependencies()
  {
    require_once plugin_dir_path(__FILE__) . 'class-ctfw-admin.php';
    require_once plugin_dir_path(__FILE__) . 'class-ctfw-bulk-actions.php';
    require_once plugin_dir_path(__FILE__) . 'class-ctfw-category-actions.php';
    require_once plugin_dir_path(__FILE__) . 'class-ctfw-i18n.php';
  }

  /**
   * Defines the hooks related to the admin area functionality of the plugin.
   *
   * This method registers the hooks for enqueueing styles, adding admin menus, handling bulk actions,
   * and processing category actions.
   */
  private function define_admin_hooks()
  {
    $plugin_admin = new CTFW_Admin();
    $plugin_bulk_actions = new CTFW_Bulk_Actions();
    $plugin_category_actions = new CTFW_Category_Actions();
    $plugin_i18n = new CTFW_i18n();

    add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
    add_action('admin_menu', array($plugin_admin, 'add_admin_menu'));
    add_filter('bulk_actions-edit-product', array($plugin_bulk_actions, 'register_bulk_actions'));
    add_filter('handle_bulk_actions-edit-product', array($plugin_bulk_actions, 'handle_bulk_actions'), 10, 3);
    add_action('admin_post_ctfw_process_add_category', array($plugin_category_actions, 'process_add_category'));
    add_action('admin_post_ctfw_process_remove_category', array($plugin_category_actions, 'process_remove_category'));
    add_action('admin_notices', array($plugin_admin, 'display_success_message'));
    add_action('plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain'));
  }
}
