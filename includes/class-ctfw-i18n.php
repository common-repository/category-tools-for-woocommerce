<?php

class CTFW_i18n
{
  /**
   * Loads the plugin text domain for translation.
   *
   * This function loads the text domain 'category-tools-for-woocommerce' to make the plugin translatable.
   * If the text domain fails to load, an error is logged.
   */
  public function load_plugin_textdomain()
  {
    load_plugin_textdomain('category-tools-for-woocommerce');
  }
}
