<?php

class CTFW_Category_Actions
{
  /**
   * Processes adding a category to the selected products.
   *
   * This method includes batch processing to handle large selections of products.
   */
  public function process_add_category()
  {
    if (!current_user_can('edit_products')) {
      error_log('CTFW | User does not have permission to edit products.');

      wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'category-tools-for-woocommerce'));
    }

    $nonce = isset($_POST[ 'ctfw_category_nonce' ]) ? sanitize_text_field(wp_unslash($_POST[ 'ctfw_category_nonce' ])) : '';
    if (!wp_verify_nonce($nonce, 'ctfw_category_action')) {
      error_log('CTFW | Security check failed during add category process.');

      wp_die(esc_html__('Security check failed.', 'category-tools-for-woocommerce'));
    }

    if (!isset($_POST[ 'post_ids' ]) || !isset($_POST[ 'category_id' ])) {
      error_log('CTFW | Missing required parameters for add category process.');

      wp_die(esc_html__('Missing required parameters.', 'category-tools-for-woocommerce'));
    }

    $post_ids = explode(',', sanitize_text_field(wp_unslash($_POST[ 'post_ids' ])));
    $category_id = absint(sanitize_text_field(wp_unslash($_POST[ 'category_id' ])));
    $redirect_to = isset($_POST[ 'redirect_to' ]) ? esc_url_raw(wp_unslash($_POST[ 'redirect_to' ])) : admin_url('edit.php?post_type=product');

    try {
      $batch_size = 100;
      $chunks = array_chunk($post_ids, $batch_size);

      foreach ($chunks as $chunk) {
        foreach ($chunk as $post_id) {
          wp_set_object_terms($post_id, $category_id, 'product_cat', true);
        }

        if (function_exists('wp_suspend_cache_invalidation')) {
          wp_suspend_cache_invalidation(true);
        }

        wp_cache_flush();

        if (function_exists('wp_suspend_cache_invalidation')) {
          wp_suspend_cache_invalidation(false);
        }
      }

      $nonce = wp_create_nonce('ctfw_category_action');
      wp_redirect(add_query_arg(array(
        'message' => 'ctfw_success',
        'ctfw_nonce' => $nonce,
      ), $redirect_to));
    } catch (Exception $e) {
      error_log('CTFW | Error adding category to products: ' . esc_html($e->getMessage()));

      wp_redirect(add_query_arg('error_message', urlencode($e->getMessage()), admin_url('admin.php?page=ctfw_category_product_selection')));
    }

    exit;
  }

  /**
   * Processes removing a category from the selected products.
   *
   * This method includes batch processing to handle large selections of products.
   */
  public function process_remove_category()
  {
    if (!current_user_can('edit_products')) {
      error_log('CTFW | User does not have permission to edit products.');

      wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'category-tools-for-woocommerce'));
    }

    $nonce = isset($_POST[ 'ctfw_category_nonce' ]) ? sanitize_text_field(wp_unslash($_POST[ 'ctfw_category_nonce' ])) : '';
    if (!wp_verify_nonce($nonce, 'ctfw_category_action')) {
      error_log('CTFW | Security check failed during remove category process.');
      wp_die(esc_html__('Security check failed.', 'category-tools-for-woocommerce'));
    }

    if (!isset($_POST[ 'post_ids' ]) || !isset($_POST[ 'category_id' ])) {
      error_log('CTFW | Missing required parameters for remove category process.');

      wp_die(esc_html__('Missing required parameters.', 'category-tools-for-woocommerce'));
    }

    $post_ids = array_map('absint', explode(',', sanitize_text_field(wp_unslash($_POST[ 'post_ids' ]))));
    $category_id = absint(sanitize_text_field(wp_unslash($_POST[ 'category_id' ])));
    $redirect_to = isset($_POST[ 'redirect_to' ]) ? esc_url_raw(wp_unslash($_POST[ 'redirect_to' ])) : admin_url('edit.php?post_type=product');

    try {
      $batch_size = 100;
      $chunks = array_chunk($post_ids, $batch_size);

      foreach ($chunks as $chunk) {
        foreach ($chunk as $post_id) {
          if (get_post_type($post_id) !== 'product') {
            error_log('CTFW | Invalid product ID encountered during category removal: ' . esc_html($post_id));

            continue;
          }

          wp_remove_object_terms($post_id, $category_id, 'product_cat');
        }

        if (function_exists('wp_suspend_cache_invalidation')) {
          wp_suspend_cache_invalidation(true);
        }

        wp_cache_flush();

        if (function_exists('wp_suspend_cache_invalidation')) {
          wp_suspend_cache_invalidation(false);
        }
      }

      $nonce = wp_create_nonce('ctfw_category_action');
      wp_redirect(add_query_arg(array(
        'message' => 'ctfw_success',
        'ctfw_nonce' => $nonce,
      ), $redirect_to));
    } catch (Exception $e) {
      error_log('CTFW | Error removing category from products: ' . esc_html($e->getMessage()));

      wp_redirect(add_query_arg('error_message', urlencode($e->getMessage()), admin_url('admin.php?page=ctfw_category_product_selection')));
    }

    exit;
  }
}
