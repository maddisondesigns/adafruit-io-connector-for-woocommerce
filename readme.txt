=== Adafruit IO Connector for WooCommerce ===
Contributors: ahortin
Donate Link: http://maddisondesigns.com/adafruit-io-connector-for-woocommerce
Tags: ecommerce, e-commerce, commerce, adafruit, adafruit io, wordpress ecommerce, woocommerce
Requires at least: 5.4
Tested up to: 5.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sends product sale information from your WooCommerce site, to an Adafruit IO feed.

== Description ==

This is a simple plugin to connect your WooCommerce site to an Adafruit IO feed.

Once a customer purchase is made in your store, details of the purchase will be sent to your nominated Adafruit IO feed. You can then subscribe to this MQTT feed using any compatible device such as the Adafruit Feather Huzzah, ESP8266, Raspberry Pi, Arduino, just to name a few.

The order information is prefixed by your speficied text. By default, this is "New Order:". If the customer order contains only one product, then the product name will be appended to your prefix text.

If the customer order contains more than one product, the text will be in the form of:
New Order:[first product name] plus 1 other product

or

New Order:[first product name] plus x other products

Where:
[first product name] is replaced with the name of ther first product oin the order
x is replaced with the remaining number or products in the order

To use this plugin, you'll need an <a href="https://io.adafruit.com" target="_blank">Adafruit IO account</a>. Once you've created this account, you'll then need to create a Feed to store your data.

For use with WooCommerce 4.0 and above.


== Installation ==

1. Upload the 'adafruit-io-connector-for-woocommerce' folder to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'AIO Connector' tab on the WooCommerce > Settings page


== Frequently Asked Questions ==

= What version of WooCommerce does this work for? =
This plugin has been tested with WooCommerce 4.0+.

= Where are the order details sent =
The order details will be sent to your nominated Adafruit IO Feed. If you haven't done so already, you'll need an <a href="https://io.adafruit.com" target="_blank">Adafruit IO account</a>. Once you've created this account, you'll then need to create a Feed to store your data.


== Screenshots ==

1. Simple Adafruit IO Connector for WooCommerce settings
2. Adafruit IO Feed settings
3. Adafruit Feather Huzzah with OLED display showing order details


== Changelog ==

= 1.0 =
- Initial version. Yay!


== Upgrade Notice ==
