<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.chapteragency.com
 * @since      1.0.0
 *
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fbf_Order_Wise
 * @subpackage Fbf_Order_Wise/includes
 * @author     Kevin Price-Ward <kevin.price-ward@chapteragency.com>
 */
class Fbf_Order_Wise_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        //Install the logging database
        self::db_install();

	}

    private static function db_install()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fbf_orderwise_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          starttime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
          endpoint varchar(20),
          log mediumtext NOT NULL,
          response mediumtext NOT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        add_option('fbf_importer_db_version', FBF_ORDER_WISE_DB_VERSION);
    }

}
