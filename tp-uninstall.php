<?php
// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// Delete options created by the plugin
delete_option( 'wctpbulksms_enable' );
delete_option( 'wctpbulksms_senderid' );
delete_option( 'wctpbulksms_apitoken' );
delete_option( 'wctpbulksms_adminnumber' );
delete_option( 'wctpbulksms_ordernewadmin' );
delete_option( 'wctpbulksms_ordernewadminsms' );
delete_option( 'wctpbulksms_orderdraft' );
delete_option( 'wctpbulksms_orderdraftsms' );
delete_option( 'wctpbulksms_orderpending' );
delete_option( 'wctpbulksms_orderpendingsms' );
delete_option( 'wctpbulksms_orderhold' );
delete_option( 'wctpbulksms_orderholdsms' );
delete_option( 'wctpbulksms_ordernew' );
delete_option( 'wctpbulksms_ordernewsms' );
delete_option( 'wctpbulksms_ordercomplete' );
delete_option( 'wctpbulksms_ordercompletesms' );
delete_option( 'wctpbulksms_ordercancelled' );
delete_option( 'wctpbulksms_ordercancelledsms' );
delete_option( 'wctpbulksms_orderrefunded' );
delete_option( 'wctpbulksms_orderrefundedsms' );
delete_option( 'wctpbulksms_orderfailed' );
delete_option( 'wctpbulksms_orderfailedsms' );
delete_option( 'wctpbulksms_ordershipped' );
delete_option( 'wctpbulksms_ordershippedsms' );
delete_option( 'wctpbulksms_orderreadypickup' );
delete_option( 'wctpbulksms_orderreadypickupsms' );
delete_option( 'wctpbulksms_orderfaileddelivery' );
delete_option( 'wctpbulksms_orderfaileddeliverysms' );
delete_option( 'wctpbulksms_orderreturned' );
delete_option( 'wctpbulksms_orderreturnedsms' );

// Remove any additional cleanup actions as needed
// For example, if your plugin created custom database tables, you would drop them here
// Example:
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}your_custom_table_name" );