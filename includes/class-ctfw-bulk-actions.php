<?php

class CTFW_Bulk_Actions
{
  /**
   * Registers custom bulk actions for adding or removing categories.
   *
   * @param array $bulk_actions The existing bulk actions.
   * @return array The modified bulk actions.
   */
  public function register_bulk_actions($bulk_actions)
  {
    $bulk_actions[ 'ctfw_add_to_category' ] = __('Add to Category', 'category-tools-for-woocommerce');
    $bulk_actions[ 'ctfw_remove_from_category' ] = __('Remove from Category', 'category-tools-for-woocommerce');

    return $bulk_actions;
  }

  /**
   * Handles the custom bulk actions by redirecting to the category selection page.
   *
   * @param string $redirect_to The URL to redirect to.
   * @param string $doaction The action being taken.
   * @param array $post_ids The IDs of the selected posts.
   * @return string The modified redirect URL.
   */
  public function handle_bulk_actions($redirect_to, $doaction, $post_ids)
  {
    $doaction = sanitize_key($doaction);
    $post_ids = array_map('absint', $post_ids);

    if ($doaction === 'ctfw_add_to_category' || $doaction === 'ctfw_remove_from_category') {
      $nonce = wp_create_nonce('ctfw_category_action');
      $redirect_to = add_query_arg(array(
        'page' => 'ctfw_category_product_selection',
        'ctfw_action' => $doaction,
        'post_ids' => implode(',', $post_ids),
        'redirect_to' => urlencode(wp_get_referer()),
        'ctfw_nonce' => $nonce,
      ), admin_url('admin.php'));
    }

    return esc_url_raw($redirect_to);
  }
}
