<?php
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Delete plugin options from the database
delete_option( 'wctpbulksms_settings_group' );
delete_option( 'wctpbulksms_settings' );

// Delete any additional options or data stored in the database by your plugin

// Remove any custom post meta or tables created by your plugin
// For example:
delete_post_meta_by_key( '_draft_duration_logged' );
delete_post_meta_by_key( '_admin_sms_sent' );
delete_post_meta_by_key( '_sms_sent_logged' );

// Remove any custom tables created by your plugin
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}your_table_name" );

// Remove any files or directories created by your plugin (if applicable)

// Remove any other cleanup tasks specific to your plugin