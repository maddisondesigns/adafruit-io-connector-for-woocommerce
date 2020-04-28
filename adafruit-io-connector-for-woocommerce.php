<?php
/*
Plugin Name: Adafruit IO Connector for WooCommerce
Plugin URI: http://maddisondesigns.com/adafruit-io-for-woocommerce
Description: Sends product sale information from your WooCommerce site, to an Adafruit IO feed.
Version: 1.0.0
WC requires at least: 4.0
WC tested up to: 4.0
Author: Anthony Hortin
Author URI: http://maddisondesigns.com
Text Domain: adafruit-io-for-woocommerce
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/


class skyrocket_adafruit_io_connector_for_woocommerce_plugin {

	const SETTINGS_NAMESPACE = 'skyrocket_adafruit_io_woocommerce';

	public function __construct() {
		if ( is_admin() ) {
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'aiocw_add_settings_tab' ), 50) ;
			add_action( 'woocommerce_settings_tabs_' . self::SETTINGS_NAMESPACE, array( $this, 'aiocw_catalog_settings_tab' ) );
			add_action( 'woocommerce_update_options_' . self::SETTINGS_NAMESPACE, array( $this, 'aiocw_catalog_update_settings' ) );
			add_filter( 'plugin_action_links', array( $this, 'aiocw_add_settings_link'), 10, 2);
		}
		else {
			add_action( 'woocommerce_payment_complete', array( $this, 'aiocw_payment_complete' ) );
		}
	}

	/**
	 * Add a new tab to the WooCommerce Settings page
	 */
	public static function aiocw_add_settings_tab( $settings_tabs ) {
		$settings_tabs[self::SETTINGS_NAMESPACE] = __( 'AIO Connector', 'adafruit-io-for-woocommerce' );
		return $settings_tabs;
	}

	/**
	 * Add a settings link to plugin page
	 */
	public function aiocw_add_settings_link( $links, $file ) {
		static $this_plugin;

		if( !$this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}

		if( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=wc-settings&tab=' . self::SETTINGS_NAMESPACE . '">' . __( 'Settings', 'adafruit-io-for-woocommerce' ) . '</a>';
			array_unshift( $links, $settings_link ) ;
		}

		return $links;
	}

	/**
	 * Get the settings for our WooCommerce tab
	 */
	public function aiocw_catalog_settings_tab() {
		woocommerce_admin_fields( $this->aiocw_get_tab_settings() );
	}

	/**
	 * Update the settings for our WooCommerce tab
	 */
	public function aiocw_catalog_update_settings() {
		woocommerce_update_options( $this->aiocw_get_tab_settings() );
	}

	/**
	* Get an option set in our settings tab
	*/
	public function aiocw_aio_woocommerce_get_option( $key ) {
		$fields = $this->aiocw_get_tab_settings();

		return apply_filters( 'wc_option_' . $key, get_option( 'wc_settings_' . self::SETTINGS_NAMESPACE . '_' . $key, ( ( isset( $fields[$key] ) && isset( $fields[$key]['default'] ) ) ? $fields[$key]['default'] : '' ) ) );
	}

	/**
	 * Add all our settings to our WooCommerce tab
	 */
	private function aiocw_get_tab_settings() {
		$settings = '';

		$settings = array(
			'section_title' => array(
				'name' => __( 'Settings', 'adafruit-io-for-woocommerce' ),
				'desc' => 'Configure the settings to connect your site to your Adafruit IO account. If you haven\'t done so already, you\'ll need to create an <a href="https://io.adafruit.com" target="_blank">Adafruit IO account</a> and then within that, create a Feed to store your data.',
				'type' => 'title',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_section_title'
			),
			'username' => array(
				'name' => __( 'Adafruit IO Username', 'adafruit-io-for-woocommerce' ),
				'desc_tip' => __( 'Your Adafruit IO Username' ),
				'type' => 'text',
				'default' => '',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_username'
			),
			'activekey' => array(
				'name' => __( 'Adafruit IO Key', 'adafruit-io-for-woocommerce' ),
				'desc_tip' => __( 'Your Adafruit IO Key' ),
				'type' => 'password',
				'default' => '',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_activekey'
			),
			'feedname' => array(
				'name' => __( 'Adafruit IO Feed Key', 'adafruit-io-for-woocommerce' ),
				'desc_tip' => __( 'Your Adafruit Feed Key. This is the name of your feed, in lowercase with spaces replaced by hyphens. If your feed is in a group other than default, prefix the feed key with the Group Key and a forward slash. eg. my-group/my-feed-name' ),
				'type' => 'text',
				'default' => '',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_feedname'
			),
			'feedprefix' => array(
				'name' => __( 'Feed Prefix Message', 'adafruit-io-for-woocommerce' ),
				'desc_tip' => __( 'Enter the text to display before your WooCommerce order details.' ),
				'type' => 'text',
				'default' => 'New Order:',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_feedprefix'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_' . self::SETTINGS_NAMESPACE . '_section_end'
			)
		);
		return apply_filters( 'wc_settings_tab_' . self::SETTINGS_NAMESPACE, $settings );
	}

	/**
	 * Once the payment is complete, send the order details to the Adafruit IO feed
	 */
	public function aiocw_payment_complete( $order_id ) {
		$username = $this->aiocw_aio_woocommerce_get_option( 'username' );
		$aio_key = $this->aiocw_aio_woocommerce_get_option( 'activekey' );
		$feedname = $this->aiocw_aio_woocommerce_get_option( 'feedname' );
		$feedprefix = $this->aiocw_aio_woocommerce_get_option( 'feedprefix' );
		$product_name = '';
		$value = '';

		// If the Adafruit IO Username, Key, or Feedname is not specified, we can't do anything
		if( !empty( $username ) && !empty( $aio_key ) && !empty( $feedname ) ) {
			$url = 'https://io.adafruit.com/api/v2/' . $username . '/feeds/' . $feedname . '/data';
			$order = wc_get_order( $order_id );
			$items = $order->get_items();
			$item_count = 0;

			foreach( $items as $item ) {
				$item_count++;
				// Get the name of the first product
				if( $item_count == 1 ) {
					$product_name .= $item->get_name();	
				}
			}

			if( $item_count > 1 ) {
				$item_count--;
				$value = $product_name . ' plus ' . sprintf( _n( '%s other product', '%s other products', $item_count, 'adafruit-io-for-woocommerce' ), $item_count );
			}
			else {
				$value = $product_name;
			}

			$response = wp_remote_post( $url,
				array(
					'method' => 'POST',
					'timeout' => 20,
					'blocking' => false,
					'headers' => array(
						'X-AIO-Key' => $aio_key,
					),
					'body' => array(
						'value' => $feedprefix . '/n' . $value,
					),
				)
			);
		}
	}
}

/**
 * Initialise our plugin, but only if WooCommerce is active
 */
function aiocw_plugin_init() {
	
	// If Parent Plugin is NOT active
	if ( current_user_can( 'activate_plugins' ) && !class_exists( 'WooCommerce' ) ) {
		
		add_action( 'admin_init', 'aiocw_plugin_deactivate' );
		add_action( 'admin_notices', 'aiocw_plugin_admin_notice' );
		
		// Deactivate the Child Plugin
		function aiocw_plugin_deactivate() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
		
		// Throw an Alert to tell the Admin why it didn't activate
		function aiocw_plugin_admin_notice() {
			$child_plugin = __( 'Adafruit IO Connector for WooCommerce', 'textdomain' );
			$parent_plugin = __( 'WooCommerce', 'textdomain' );

			echo '<div class="notice notice-error is-dismissible"><p>';
			echo sprintf( __( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'textdomain' ), '<strong>' . esc_html( $child_plugin ) . '</strong>', '<strong>' . esc_html( $parent_plugin ) . '</strong>' );
			echo '</p></div>';

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}

	} else {
		// Do all the things
		$skyrocket_adafruitio_woocommerce = new skyrocket_adafruit_io_connector_for_woocommerce_plugin();
	}
}
add_action( 'plugins_loaded', 'aiocw_plugin_init' );
