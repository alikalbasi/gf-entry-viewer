<?php
/**
 * Plugin Name:       Gravity Form Entry Viewer
 * Plugin URI:        https://github.com/alikalbasi/gf-entry-viewer/
 * Description:       A secure, styled, and professional viewer for Gravity Forms entries with a custom login page and auto-updates.
 * Version:           1.1.1
 * Author:            Ali Kalbasi
 * Author URI:        https://alikalbasi.ir
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       gf-entry-viewer
 * Requires at least: 5.5
 * Requires PHP:      7.4
 */

// Exit if accessed directly for security.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define plugin constants for easy access to paths and URLs.
 */
define( 'GFEV_PATH', plugin_dir_path( __FILE__ ) );
define( 'GFEV_URL', plugin_dir_url( __FILE__ ) );
define( 'GFEV_VERSION', '1.1.2' ); // Updated version number for the patch fix

/**
 * Set up the GitHub Plugin Updater.
 */
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

if ( file_exists( GFEV_PATH . 'lib/plugin-update-checker/plugin-update-checker.php' ) ) {
    require GFEV_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';


    try {
        $gfevUpdateChecker = PucFactory::buildUpdateChecker(
            'https://github.com/alikalbasi/gf-entry-viewer/', // GitHub repository URL.
            __FILE__, // Main plugin file.
            'gf-entry-viewer' // Plugin slug.
        );
        $gfevUpdateChecker->setBranch( 'main' );
    } catch ( Exception $e ) {
        error_log( 'GF Entry Viewer update checker failed to initialize: ' . $e->getMessage() );
    }
}

/**
 * Main plugin class to encapsulate all functionality.
 */
class Gravity_Form_Entry_Viewer {

    public function __construct() {
        add_action( 'init', [ $this, 'setup_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'display_entries_page' ] );
    }

    public function setup_rewrite_rules() {
        add_rewrite_rule( '^gf-entries/?$', 'index.php?gf_entries_page=1', 'top' );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'gf_entries_page';
        return $vars;
    }

    public function display_entries_page() {
        if ( get_query_var( 'gf_entries_page' ) ) {
            require_once GFEV_PATH . 'includes/class-gfev-renderer.php';
            $renderer = new GFEV_Renderer();
            $renderer->render_page();
            exit;
        }
    }
}

new Gravity_Form_Entry_Viewer();