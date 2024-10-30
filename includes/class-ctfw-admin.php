<?php

class CTFW_Admin
{
  /**
   * Displays a success message if the 'ctfw_success' message is present in the URL.
   */
  public function display_success_message()
  {
    $nonce = isset($_GET[ 'ctfw_nonce' ]) ? sanitize_text_field(wp_unslash($_GET[ 'ctfw_nonce' ])) : '';
    if ($nonce !== '') {
      if (!wp_verify_nonce($nonce, 'ctfw_category_action')) {
        wp_die(esc_html__('Security check failed. Please try again.', 'category-tools-for-woocommerce'));
      }

      if (isset($_GET[ 'message' ]) && sanitize_key(wp_unslash($_GET[ 'message' ])) === 'ctfw_success') {
        ?>
          <div class="updated notice is-dismissible">
              <p><?php echo esc_html__('The products were successfully updated.', 'category-tools-for-woocommerce'); ?></p>
          </div>
          <?php
}
    }
  }

  /**
   * Enqueues the admin styles for the plugin.
   *
   * @param string $hook The current admin page hook.
   */
  public function enqueue_styles($hook)
  {
    if ($hook !== 'admin_page_ctfw_category_product_selection') {
      return;
    }

    wp_enqueue_style('ctfw-admin', plugin_dir_url(__FILE__) . '../assets/css/ctfw-admin.css', array(), '1.0.0', 'all');
  }

  /**
   * Adds a hidden admin menu page for category selection.
   */
  public function add_admin_menu()
  {
    add_submenu_page(
      null,
      esc_html__('Category Product Selection', 'category-tools-for-woocommerce'),
      esc_html__('Category Tools', 'category-tools-for-woocommerce'),
      'edit_products',
      'ctfw_category_product_selection',
      array($this, 'render_category_selection_page')
    );
  }

  /**
   * Renders the category selection page for bulk actions.
   *
   * This page allows users to select a category to either add or remove from the selected products.
   */
  public function render_category_selection_page()
  {
    if (!isset($_GET[ 'ctfw_nonce' ]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET[ 'ctfw_nonce' ])), 'ctfw_category_action')) {
      wp_die(esc_html__('Security check failed. Please try again.', 'category-tools-for-woocommerce'));
    }

    if (!isset($_GET[ 'post_ids' ]) || !isset($_GET[ 'ctfw_action' ]) || !isset($_GET[ 'redirect_to' ])) {
      wp_die(esc_html__('Invalid request', 'category-tools-for-woocommerce'));
    }

    $post_ids = array_map('absint', explode(',', sanitize_text_field(wp_unslash($_GET[ 'post_ids' ]))));
    $action = sanitize_key(wp_unslash($_GET[ 'ctfw_action' ]));
    $redirect_to = esc_url_raw(wp_unslash($_GET[ 'redirect_to' ]));
    $error_message = isset($_GET[ 'error_message' ]) ? esc_html(sanitize_text_field(wp_unslash($_GET[ 'error_message' ]))) : '';

    try {
      $categories = $action === 'ctfw_remove_from_category'
      ? $this->get_unique_categories_of_products($post_ids)
      : $this->get_all_categories_with_breadcrumbs();
    } catch (Exception $e) {
      error_log('CTFW | Error fetching categories: ' . esc_html($e->getMessage()));
      wp_die(esc_html__('There was an error fetching categories. Please check the error logs.', 'category-tools-for-woocommerce'));
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html($action === 'ctfw_remove_from_category' ? __('Remove from Category', 'category-tools-for-woocommerce') : __('Add to Category', 'category-tools-for-woocommerce')) . '</h1>';

    $this->display_success_message();

    echo '<div class="ctfw-content">';
    echo '<div class="ctfw-products-table">';
    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>' . esc_html__('Product Name', 'category-tools-for-woocommerce') . '</th><th>' . esc_html__('Existing Categories', 'category-tools-for-woocommerce') . '</th></tr></thead>';
    echo '<tbody>';
    foreach ($post_ids as $post_id) {
      if (get_post_type($post_id) !== 'product') {
        error_log('CTFW | Invalid product ID: ' . esc_html($post_id));

        continue;
      }

      $product_categories = wp_get_post_terms($post_id, 'product_cat');

      echo '<tr>';
      echo '<td>' . esc_html(get_the_title($post_id)) . '</td>';
      echo '<td>';
      $breadcrumbs = [  ];
      foreach ($product_categories as $category) {
        $breadcrumbs[  ] = esc_html($this->get_category_breadcrumb($category));
      }
      echo esc_html(implode(', ', $breadcrumbs));
      echo '</td>';
      echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // .ctfw-products-table

    echo '<div class="ctfw-category-selection">';
    if ($error_message) {
      echo '<div class="error notice"><p>' . esc_html($error_message) . '</p></div>';
    }

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('ctfw_category_action', 'ctfw_category_nonce');
    echo '<p>' . esc_html($action === 'ctfw_remove_from_category' ? __('Select a category to remove from the selected products:', 'category-tools-for-woocommerce') : __('Select a category to associate with the selected products. If the selected category is already associated, it will not be added again:', 'category-tools-for-woocommerce')) . '</p>';
    echo '<select name="category_id" required>';
    foreach ($categories as $category) {
      echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
    }
    echo '</select>';
    echo '<input type="hidden" name="post_ids" value="' . esc_attr(implode(',', $post_ids)) . '">';
    echo '<input type="hidden" name="action" value="' . esc_attr($action === 'ctfw_remove_from_category' ? 'ctfw_process_remove_category' : 'ctfw_process_add_category') . '">';
    echo '<input type="hidden" name="redirect_to" value="' . esc_attr($redirect_to) . '">';
    echo '<input type="submit" class="button-primary" value="' . esc_html__('Apply Category Changes', 'category-tools-for-woocommerce') . '">';
    echo '</form>';
    echo '</div>'; // .ctfw-category-selection
    echo '</div>'; // .ctfw-content
    echo '</div>'; // .wrap
  }

  /**
   * Retrieves a list of unique categories associated with the given products, sorted alphabetically
   * after applying breadcrumb-style names.
   *
   * @param array $post_ids An array of product IDs.
   * @return array An array of unique WP_Term objects.
   */
  private function get_unique_categories_of_products($post_ids)
  {
    try {
      $post_ids = array_map('absint', $post_ids);

      $terms = wp_get_object_terms($post_ids, 'product_cat');
      $unique_terms = array_unique($terms, SORT_REGULAR);

      foreach ($unique_terms as &$term) {
        $term->name = $this->get_category_breadcrumb($term);
      }

      usort($unique_terms, function ($a, $b) {
        return strcmp($a->name, $b->name);
      });

      return $unique_terms;
    } catch (Exception $e) {
      error_log('CTFW | Error in get_unique_categories_of_products: ' . esc_html($e->getMessage()));

      return [  ];
    }
  }

  /**
   * Retrieves all categories with breadcrumb-style names, sorted alphabetically.
   *
   * @return array An array of WP_Term objects with breadcrumb-style names.
   */
  private function get_all_categories_with_breadcrumbs()
  {
    try {
      $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => false,
      ));

      if (is_wp_error($terms)) {
        error_log('CTFW | Error fetching categories: ' . esc_html($terms->get_error_message()));
        return [  ];
      }

      foreach ($terms as &$term) {
        $term->name = $this->get_category_breadcrumb($term);
      }

      usort($terms, function ($a, $b) {
        return strcmp($a->name, $b->name);
      });

      return $terms;
    } catch (Exception $e) {
      error_log('CTFW | Error in get_all_categories_with_breadcrumbs: ' . esc_html($e->getMessage()));

      return [  ];
    }
  }

  /**
   * Generates a breadcrumb-style name for a given category.
   *
   * @param WP_Term $term The category term object.
   * @return string The breadcrumb-style category name.
   */
  private function get_category_breadcrumb($term)
  {
    $breadcrumb = [  ];
    $parent_id = absint($term->parent);

    while ($parent_id) {
      $parent = get_term($parent_id, 'product_cat');
      if ($parent && !is_wp_error($parent)) {
        $breadcrumb[  ] = esc_html($parent->name);
        $parent_id = $parent->parent;
      } else {
        break;
      }
    }

    $breadcrumb = array_reverse($breadcrumb);
    $breadcrumb[  ] = esc_html($term->name);

    return implode(' > ', $breadcrumb);
  }
}
