<?php
/**
 * Plugin Name: Gravity Form Entry Viewer
 * Description: A secure, styled, and professional viewer for Gravity Forms entries accessible at /gf-entries.
 * Version: 5.0
 * Author: Ali Kalbasi
 * Author URI: https://alikalbasi.ir
 */

if (!defined('ABSPATH')) exit;

define('GFEV_PATH', plugin_dir_path(__FILE__));
define('GFEV_URL', plugin_dir_url(__FILE__));

class Gravity_Form_Entry_Viewer {

    public function __construct() {
        add_action('init', [$this, 'setup_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'display_entries_page']);
    }

    public function setup_rewrite_rules() {
        add_rewrite_rule('^gf-entries/?$', 'index.php?gf_entries_page=1', 'top');
        // Flush rewrite rules on activation/deactivation for reliability
        register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
        register_activation_hook(__FILE__, 'flush_rewrite_rules');
    }

    public function add_query_vars($vars) {
        $vars[] = 'gf_entries_page';
        return $vars;
    }

    public function display_entries_page() {
        if (get_query_var('gf_entries_page') == 1) {
            require_once GFEV_PATH . 'includes/class-gfev-renderer.php';
            $renderer = new GFEV_Renderer();
            $renderer->render_page();
            exit;
        }
    }
}

new Gravity_Form_Entry_Viewer();