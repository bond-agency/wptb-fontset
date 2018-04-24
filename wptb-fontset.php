<?php
/*
Plugin Name: Fontset
Description: Load fonts from webservices or local folders.
Version: 1.0.0
Author: Bond
Author uri: https://bond-agency.com
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

// Run installation function only once on activation.
register_activation_hook(__FILE__, ['WPTB_Fontset', 'on_activation']);
register_deactivation_hook(__FILE__, ['WPTB_Fontset', 'on_deactivation']);
add_action('plugins_loaded', ['WPTB_Fontset', 'init']);

class WPTB_Fontset {
  protected static $instance; // Holds the instance.
  protected static $version = '1.0.0'; // The current version of the plugin.
  protected static $min_wp_version = '4.7.5'; // Minimum required WordPress version.
  protected static $min_php_version = '7.0'; // Minimum required PHP version.
  protected static $class_dependencies = ['acf']; // Class dependencies of the plugin.
  protected static $required_php_extensions = []; // PHP extensions required by the plugin.
  protected static $post_type = 'wptb-fontset'; // The slug of the fontset post type.



  public function __construct(){
    /**
     * Register your hooks here. Remember to register only on admin side if
     * it's only admin plugin and so forth.
     */
     add_action('init', [$this, 'register_fontset_post_type']);
     add_action('wp_head', [$this, 'load_published_fontsets']);
     add_filter('acf/settings/load_json', [$this, 'add_acf_json_load_point']);
  }



  public static function init() {
    is_null( self::$instance ) AND self::$instance = new self;
    return self::$instance;
  }



  /**
   * Checks if plugin dependencies & requirements are met.
   */
  protected static function are_requirements_met() {
    // Check for WordPress version
    if ( version_compare( get_bloginfo('version'), self::$min_wp_version, '<' ) ) {
      return false;
    }

    // Check the PHP version
    if ( version_compare( PHP_VERSION, self::$min_php_version, '<' ) ) {
      return false;
    }

    // Check PHP loaded extensions
    foreach ( self::$required_php_extensions as $ext ) {
      if ( ! extension_loaded( $ext ) ) {
        return false;
      }
    }

    // Check for required classes
    foreach ( self::$class_dependencies as $class_name ) {
      if ( ! class_exists( $class_name ) ) {
        return false;
      }
    }

    return true;
  }



  /**
   * Checks if plugin dependencies & requirements are met. If they are it doesn't
   * do anything if they aren't it will die.
   */
  public static function ensure_requirements_are_met() {
    if (!self::are_requirements_met()) {
      deactivate_plugins(__FILE__);
      wp_die( "<p>Some of the plugin dependencies aren't met and the plugin can't be enabled. This plugin requires the followind dependencies:</p><ul><li>Minimum WP version: ".self::$min_wp_version."</li><li>Minimum PHP version: ".self::$min_php_version."</li><li>Classes / plugins: ".implode (", ", self::$class_dependencies)."</li><li>PHP extensions: ".implode (", ", self::$required_php_extensions)."</li></ul>" );
    }
  }



  /**
   * A function that's run once when the plugin is activated. We just create
   * a scheduled run for the press release update.
   */
   public static function on_activation() {
    // Security stuff.
    if ( ! current_user_can( 'activate_plugins' ) ) {
      return;
    }

    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "activate-plugin_{$plugin}" );

    // Check requirements.
    self::ensure_requirements_are_met();

    // Your activation code below this line.
  }



  /**
   * A function that's run once when the plugin is deactivated. We just delete
   * the scheduled run for the press release update.
   */
   public static function on_deactivation() {
    // Security stuff.
    if ( ! current_user_can( 'activate_plugins' ) ) {
      return;
    }

    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
    check_admin_referer( "deactivate-plugin_{$plugin}" );

    // Your deactivation code below this line.
  }



 /**
  * Appends all published Fontsets to WP head.
  */
  public static function load_published_fontsets() {
    // Get all published fontsets.
    $fontsets = get_posts([
      'post_type' => 'wptb-fontset',
      'posts_per_page' => -1
    ]);

    // If there are no fontsets return an empty string.
    if (!$fontsets) { return ''; }

    // Construct the string that's going to be appeended to head.
    $head = '';
    foreach ($fontsets as $set) {
      // Get type.
      $type = get_field('fontset_type', $set->ID);

      // Append the string.
      if ($type === 'webservice') {
        $head .= get_field('embed_code', $set->ID);
      } elseif ($type === 'selfhosted') {
        $css = get_field('css_code', $set->ID);
        $head .= "<style>$css</style>";
      }
    }

    echo $head;
  }



  /**
   * Registers the Fontset post type.
   *
   * @since    1.0.0
   */
  function register_fontset_post_type() {
    $labels = [
      "name" => __('Fontsets', 'wptb'),
      "singular_name" => __('Fontset', 'wptb'),
      "add_new_item" => __('Add Fontset', 'wptb'),
      "edit_item" => __('Edit Fontset', 'wptb'),
      "new_item" => __('New Fontset', 'wptb'),
      "view_item" => __('View Fontset', 'wptb'),
      "search_items" => __('Search Guides', 'wptb'),
      "not_found" => __('No Guides Found', 'wptb'),
      "not_found_in_trash" => __('No Guides found in Trash', 'wptb')
    ];

    $args = [
      "labels" => $labels,
      "description" => "",
      "public" => false,
      "show_ui" => true,
      "has_archive" => false,
      "show_in_menu" => true,
      "exclude_from_search" => true,
      "capability_type" => "page",
      "map_meta_cap" => true,
      "hierarchical" => false,
      "menu_position" => 101,
      "rewrite" => ["slug" => self::$post_type],
      "query_var" => false,
      "supports" => ['title'],
      "menu_icon" => "dashicons-editor-customchar"
    ];

    register_post_type(self::$post_type, $args);
  }



  static function add_acf_json_load_point($paths) {
    // Append path
    $paths[] = trailingslashit(plugin_dir_path(__FILE__)) . 'acf-json';

    return $paths;
  }

} // Class ends