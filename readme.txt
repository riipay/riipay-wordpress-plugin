=== Riipay for WooCommerce ===
Contributors: riipay
Tags: riipay, payment gateway, Malaysia, ecommerce, woocommerce
Requires at least: 4.4
Tested up to: 5.8
Stable tag: 1.0.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provide a better payment experience with easy, seamless, zero-interest instalments on your WooCommerce store.

== Description ==

Riipay for WooCommerce allows you to securely provide zero-interest instalments with debit or credit cards on your WooCommerce store for free.

Your customers can now connect to Riipay on a click of a button during checkout!

== Installation ==

**Step 1:**

- Login to your *WordPress Dashboard*
- Navigate to **Plugins >> Add New**
- Search **Riipay for WooCommerce >> Install Now**

**Step 2:**

- Activate Plugin

**Step 3:**

- Navigate to **WooCommerce >> Settings >> Checkout >> Riipay**
- Insert your **Merchant Code** and **Secret Key**
- Modify your **Minimum Order Amount** and **Maximum Order Amount** as desired. By default the minimum order amount is RM0, whereas the maximum order amount if RM1000.
  For orders amount exceeding RM1000, Riipay will reject payment for the time being until further notice.
- Save changes

== Frequently Asked Questions ==

= Where can I get Secret Key? =

You can retrieve the Secret Key at your Riipay Merchant Portal Profile Section.

= Where can I get my Merchant Code =

You can retrieve the Merchant Code at your Riipay Merchant Portal Profile Section.

= What if I have some other question related to Riipay? =
Kindly contact Riipay customer service at contact@riipay.my.

== Screenshots ==

== Changelog ==

= 1.0.16 =
Enhanced callback response handling.

= 1.0.15 =
Enhanced callback response handling.

= 1.0.14 =
Added visibility control setting.

= 1.0.13 =
Added Instalment Count & Show Instalment Price Setting.
Added custom class to product price text.

= 1.0.12 =
Bugfix

= 1.0.11 =
Make product instalment price bold.

= 1.0.10 =
Enhance product price display text by showing first instalment price.
Support latest Wordpress version.

= 1.0.9 =
Support latest Wordpress and WooCommerce version.

= 1.0.8 =
Allow customisation for Riipay custom price logo position.

= 1.0.7 =
Do not update order status if server error.

= 1.0.6 =
Only update order status to Failed for certain response error codes.
Update order status to On-Hold or Processing if response signature is valid and status code is not Failed.

= 1.0.5 =
Fix product description logo alignment.

= 1.0.4 =
Do not process callback response if order is already under processing, completed or refunded

= 1.0.3 =
Remove order status checking when processing callback response

= 1.0.2 =
* Allow merchant to add surcharge to customers when they make payments using Riipay
* Make Riipay visible to store administrators only when in sandbox environment
* Set plugin settings default value

= 1.0.1 =
* Allow merchant to enabled Riipay price display on product widget
* Fix production merchant portal URL

= 1.0.0 =
First stable release
* both sandbox and production ready
* custom riipay payment title and description

= 0.1.0 =
Beta version for sandbox testing.

== Links ==
Use [Riipay](https://riipay.my) to provide 0% instalment payment plan for your customers today!

