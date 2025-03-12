<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class Settings {

    /**
     * @var Settings
     */
    private static $instance = null;

    /**
     * @return Settings
     */
    public static function init() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::hooks(self::$instance);
        }
        return self::$instance;
    }

    /**
     * @param Settings $obj
     */
    public static function hooks(Settings $obj) {
        add_action('admin_init', [$obj, 'adminInit']);
        add_action('admin_menu', [$obj, 'adminMenu']);
    }

    /**
     * Constructor
     */
    public function __construct() {
    }

    /**
     * Admin init hook
     */
    public function adminInit() {
        register_setting(
            'pb_google_docs_settings',
            'pb_google_docs_credentials',
            [
                'type' => 'string',
                'description' => __('Google Docs API credentials JSON', 'pressbooks'),
                'sanitize_callback' => [$this, 'sanitizeCredentials'],
            ]
        );

        add_settings_section(
            'pb_google_docs_settings_section',
            __('Google Docs Settings', 'pressbooks'),
            [$this, 'settingsSectionCallback'],
            'pb_google_docs_settings'
        );

        add_settings_field(
            'pb_google_docs_credentials',
            __('API Credentials', 'pressbooks'),
            [$this, 'credentialsCallback'],
            'pb_google_docs_settings',
            'pb_google_docs_settings_section'
        );
    }

    /**
     * Admin menu hook
     */
    public function adminMenu() {
        add_options_page(
            __('Google Docs Import Settings', 'pressbooks'),
            __('Google Docs Import', 'pressbooks'),
            'manage_options',
            'pb_google_docs_settings',
            [$this, 'settingsPage']
        );
    }

    /**
     * Settings section callback
     */
    public function settingsSectionCallback() {
        echo '<p>' . __('Configure your Google Docs API credentials here. You need to create a project in the Google Cloud Console and enable the Google Drive API.', 'pressbooks') . '</p>';
    }

    /**
     * Credentials field callback
     */
    public function credentialsCallback() {
        $credentials = get_option('pb_google_docs_credentials');
        echo '<textarea name="pb_google_docs_credentials" rows="10" cols="50" class="large-text code">' . esc_textarea($credentials) . '</textarea>';
        echo '<p class="description">' . __('Paste your Google Cloud service account credentials JSON here.', 'pressbooks') . '</p>';
    }

    /**
     * Settings page
     */
    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('pb_google_docs_settings');
                do_settings_sections('pb_google_docs_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize credentials
     * 
     * @param string $input
     * @return string
     */
    public function sanitizeCredentials($input) {
        if (empty($input)) {
            add_settings_error(
                'pb_google_docs_credentials',
                'empty_credentials',
                __('Credentials cannot be empty.', 'pressbooks')
            );
            return '';
        }

        $json = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_settings_error(
                'pb_google_docs_credentials',
                'invalid_json',
                __('Invalid JSON format.', 'pressbooks')
            );
            return '';
        }

        // Verify required fields
        $required_fields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email'];
        foreach ($required_fields as $field) {
            if (!isset($json[$field])) {
                add_settings_error(
                    'pb_google_docs_credentials',
                    'missing_field',
                    sprintf(__('Missing required field: %s', 'pressbooks'), $field)
                );
                return '';
            }
        }

        return $input;
    }
} 