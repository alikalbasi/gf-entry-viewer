<?php
/**
 * Plugin Name:       Gravity Form Entry Viewer
 * Plugin URI:        https://github.com/alikalbasi/gf-entry-viewer/
 * Description:       A secure, styled, and professional viewer for Gravity Forms entries with auto-updates from GitHub.
 * Version:           7.0
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
define( 'GFEV_VERSION', '7.0' );

/**
 * Set up the GitHub Plugin Updater.
 * This allows the plugin to be updated directly from the GitHub repository.
 */
require GFEV_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

try {
    $gfevUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/alikalbasi/gf-entry-viewer/', // GitHub repository URL.
        __FILE__, // Main plugin file.
        'gf-entry-viewer' // Plugin slug.
    );
    // Specify the branch to check for updates.
    $gfevUpdateChecker->setBranch( 'main' );
} catch ( Exception $e ) {
    // Optionally log the error if the update checker fails to initialize.
    error_log( 'GF Entry Viewer update checker failed to initialize: ' . $e->getMessage() );
}

/**
 * Main plugin class to encapsulate all functionality.
 */
class Gravity_Form_Entry_Viewer {

    /**
     * Constructor.
     * Hooks all necessary actions and filters for the plugin.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'setup_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'display_entries_page' ] );
    }

    /**
     * Sets up the custom rewrite rule for the /gf-entries/ endpoint.
     * This makes the URL user-friendly.
     */
    public function setup_rewrite_rules() {
        add_rewrite_rule( '^gf-entries/?$', 'index.php?gf_entries_page=1', 'top' );
    }

    /**
     * Adds the custom query variable to WordPress's list of recognized query variables.
     *
     * @param array $vars The array of existing query variables.
     * @return array The modified array of query variables.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'gf_entries_page';
        return $vars;
    }

    /**
     * Checks if the current request is for our custom page and, if so,
     * loads the renderer to display the page.
     */
    public function display_entries_page() {
        // get_query_var returns the value of the query var, not just 1/0.
        // We check if it's not empty to be more robust.
        if ( get_query_var( 'gf_entries_page' ) ) {
            require_once GFEV_PATH . 'includes/class-gfev-renderer.php';
            $renderer = new GFEV_Renderer();
            $renderer->render_page();
            exit;
        }
    }
}

// Instantiate the main plugin class to get everything started.
new Gravity_Form_Entry_Viewer();