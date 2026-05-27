<?php
/**
 * Plugin Name: Master Plugin
 * Plugin URI:  https://github.com/zoheryrakoto/master-plugin
 * Description: A basic but complete WordPress plugin with an admin settings page, shortcode, and custom widget.
 * Version:   1.0.0
 * Author:    Albert Rakoto
 * Author URI:  https://github.com/zoheryrakoto
 * License:   GPL-2.0+
 * Text Domain: master-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Prevent direct access.
}

// ─────────────────────────────────────────────
// Constants
// ─────────────────────────────────────────────
define( 'MASTER_PLUGIN_VERSION', '1.0.0' );
define( 'MASTER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MASTER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ─────────────────────────────────────────────
// Activation / Deactivation / Uninstall Hooks
// ─────────────────────────────────────────────
register_activation_hook( __FILE__, 'master_plugin_activate' );
register_deactivation_hook( __FILE__, 'master_plugin_deactivate' );

function master_plugin_activate() {
  if ( ! get_option( 'master_plugin_options' ) ) {
    add_option( 'master_plugin_options', [
      'greeting' => 'Hello, World!',
      'show_in_footer' => '1',
    ]);
  }
}

function master_plugin_deactivate() {
  // Clean up transients, scheduled events, etc.
  wp_clear_scheduled_hook( 'master_plugin_daily_event' );
}

// ─────────────────────────────────────────────
// Load Text Domain (i18n)
// ─────────────────────────────────────────────
add_action( 'plugins_loaded', 'master_plugin_load_textdomain' );
function master_plugin_load_textdomain() {
  load_plugin_textdomain( 'master-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// ─────────────────────────────────────────────
// Enqueue Scripts & Styles
// ─────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'master_plugin_enqueue_assets' );
function master_plugin_enqueue_assets() {
  wp_enqueue_style(
    'master-plugin-style',
    MASTER_PLUGIN_URL . 'assets/css/master-plugin.css',
    [],
    MASTER_PLUGIN_VERSION
  );

  wp_enqueue_script(
    'master-plugin-script',
    MASTER_PLUGIN_URL . 'assets/js/master-plugin.js',
    [ 'jquery' ],
    MASTER_PLUGIN_VERSION,
    true
  );

  // Pass PHP data to JS
  wp_localize_script( 'master-plugin-script', 'masterPluginData', [
    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'master_plugin_nonce' ),
  ]);
}

// ─────────────────────────────────────────────
// Admin Menu & Settings Page
// ─────────────────────────────────────────────
add_action( 'admin_menu', 'master_plugin_admin_menu' );
function master_plugin_admin_menu() {
  add_menu_page(
    __( 'Master Plugin', 'master-plugin' ),
    __( 'Master Plugin', 'master-plugin' ),
    'manage_options',
    'master-plugin',
    'master_plugin_settings_page',
    'dashicons-superhero',
    60
  );
}

add_action( 'admin_init', 'master_plugin_register_settings' );
function master_plugin_register_settings() {
  register_setting( 'master_plugin_settings_group', 'master_plugin_options', 'master_plugin_sanitize_options' );

  add_settings_section(
    'master_plugin_general_section',
    __( 'General Settings', 'master-plugin' ),
    '__return_false',
    'master-plugin'
  );

  add_settings_field(
    'greeting',
    __( 'Greeting Message', 'master-plugin' ),
    'master_plugin_field_greeting',
    'master-plugin',
    'master_plugin_general_section'
  );

  add_settings_field(
    'show_in_footer',
    __( 'Show Greeting in Footer', 'master-plugin' ),
    'master_plugin_field_show_in_footer',
    'master-plugin',
    'master_plugin_general_section'
  );
}

function master_plugin_sanitize_options( $input ) {
  $output = [];
  $output['greeting']     = isset( $input['greeting'] ) ? sanitize_text_field( $input['greeting'] ) : '';
  $output['show_in_footer'] = isset( $input['show_in_footer'] ) ? '1' : '0';
  return $output;
}

function master_plugin_field_greeting() {
  $options = get_option( 'master_plugin_options' );
  $value   = esc_attr( $options['greeting'] ?? '' );
  echo "<input type='text' name='master_plugin_options[greeting]' value='{$value}' class='regular-text' />";
}

function master_plugin_field_show_in_footer() {
  $options = get_option( 'master_plugin_options' );
  $checked = checked( $options['show_in_footer'] ?? '0', '1', false );
  echo "<input type='checkbox' name='master_plugin_options[show_in_footer]' value='1' {$checked} />";
}

function master_plugin_settings_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }
  ?>
  <div class="wrap">
    <h1><?php esc_html_e( 'Master Plugin Settings', 'master-plugin' ); ?></h1>
    <form method="post" action="options.php">
      <?php
        settings_fields( 'master_plugin_settings_group' );
        do_settings_sections( 'master-plugin' );
        submit_button();
      ?>
    </form>
  </div>
  <?php
}

// ─────────────────────────────────────────────
// Shortcode: [master_greeting]
// ─────────────────────────────────────────────
add_shortcode( 'master_greeting', 'master_plugin_greeting_shortcode' );
function master_plugin_greeting_shortcode( $atts ) {
  $atts  = shortcode_atts( [ 'color' => '#333333' ], $atts, 'master_greeting' );
  $options = get_option( 'master_plugin_options' );
  $message = esc_html( $options['greeting'] ?? __( 'Hello!', 'master-plugin' ) );
  $color   = esc_attr( $atts['color'] );

  return "<p class='master-plugin-greeting' style='color:{$color};'>{$message}</p>";
}

// ─────────────────────────────────────────────
// Footer Output
// ─────────────────────────────────────────────
add_action( 'wp_footer', 'master_plugin_footer_greeting' );
function master_plugin_footer_greeting() {
  $options = get_option( 'master_plugin_options' );
  if ( ! empty( $options['show_in_footer'] ) && $options['show_in_footer'] === '1' ) {
    $message = esc_html( $options['greeting'] ?? '' );
    echo "<div class='master-plugin-footer-greeting'>{$message}</div>";
  }
}

// ─────────────────────────────────────────────
// AJAX Handler (logged-in + public)
// ─────────────────────────────────────────────
add_action( 'wp_ajax_master_plugin_action',    'master_plugin_ajax_handler' );
add_action( 'wp_ajax_nopriv_master_plugin_action', 'master_plugin_ajax_handler' );
function master_plugin_ajax_handler() {
  check_ajax_referer( 'master_plugin_nonce', 'nonce' );

  $response = [
    'success' => true,
    'message' => __( 'AJAX is working!', 'master-plugin' ),
  ];

  wp_send_json_success( $response );
}

// ─────────────────────────────────────────────
// Custom Widget
// ─────────────────────────────────────────────
add_action( 'widgets_init', function () {
  register_widget( 'Master_Plugin_Widget' );
});

class Master_Plugin_Widget extends WP_Widget {

  public function __construct() {
    parent::__construct(
      'master_plugin_widget',
      __( 'Master Plugin Widget', 'master-plugin' ),
      [ 'description' => __( 'Displays the Master Plugin greeting message.', 'master-plugin' ) ]
    );
  }

  public function widget( $args, $instance ) {
    $title   = apply_filters( 'widget_title', $instance['title'] ?? '' );
    $options = get_option( 'master_plugin_options' );
    $message = esc_html( $options['greeting'] ?? __( 'Hello!', 'master-plugin' ) );

    echo $args['before_widget'];
    if ( $title ) {
      echo $args['before_title'] . $title . $args['after_title'];
    }
    echo "<p class='master-plugin-widget-message'>{$message}</p>";
    echo $args['after_widget'];
  }

  public function form( $instance ) {
    $title = esc_attr( $instance['title'] ?? __( 'Greeting', 'master-plugin' ) );
    ?>
    <p>
      <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
        <?php esc_html_e( 'Title:', 'master-plugin' ); ?>
      </label>
      <input class="widefat" type="text"
        id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
        name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
        value="<?php echo $title; ?>" />
    </p>
    <?php
  }

  public function update( $new_instance, $old_instance ) {
    $instance      = $old_instance;
    $instance['title'] = sanitize_text_field( $new_instance['title'] );
    return $instance;
  }
}

// ─────────────────────────────────────────────
// Custom Post Type: "Announcement"
// ─────────────────────────────────────────────
add_action( 'init', 'master_plugin_register_cpt' );
function master_plugin_register_cpt() {
  $labels = [
    'name'         => __( 'Announcements', 'master-plugin' ),
    'singular_name'    => __( 'Announcement', 'master-plugin' ),
    'add_new_item'     => __( 'Add New Announcement', 'master-plugin' ),
    'edit_item'      => __( 'Edit Announcement', 'master-plugin' ),
    'view_item'      => __( 'View Announcement', 'master-plugin' ),
    'search_items'     => __( 'Search Announcements', 'master-plugin' ),
    'not_found'      => __( 'No announcements found.', 'master-plugin' ),
  ];

  register_post_type( 'announcement', [
    'labels'    => $labels,
    'public'    => true,
    'has_archive' => true,
    'supports'  => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
    'menu_icon'   => 'dashicons-megaphone',
    'rewrite'   => [ 'slug' => 'announcements' ],
    'show_in_rest' => true, // Enables Gutenberg support
  ]);
}

// ─────────────────────────────────────────────
// Scheduled Event (Daily Cron)
// ─────────────────────────────────────────────
add_action( 'master_plugin_daily_event', 'master_plugin_daily_task' );
function master_plugin_daily_task() {
  // Placeholder for any recurring background task.
  error_log( '[Master Plugin] Daily cron task ran at ' . current_time( 'mysql' ) );
}

if ( ! wp_next_scheduled( 'master_plugin_daily_event' ) ) {
  wp_schedule_event( time(), 'daily', 'master_plugin_daily_event' );
}
