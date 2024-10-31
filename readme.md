=== Quiver Delivery ===
Contributors: Quiver, louiscollarsmith
Donate link:
Tags: Quiver, Shipping, WooCommerce, Fulfilment, London, Courier, Carrier, Immediate, Fast
Requires at least: 4.6
Tested up to: 6.0
Requires PHP: 7.0
Stable tag: 1.0.16
WC requires at least: 3.0.0
WC tested up to: 6.6.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Faster, emissionless deliveries - it's magic.

== Description ==

Boost your conversion rate, your customer satisfaction and reduce your returns rate. [Quiver](https://quiver.co.uk) will do all of this for you.

== How? ==

Quiver guarantees same-hour delivery of your products to your customers- we do this in two ways - delivering from your stores or we hold your items in a Quiver Fulfilment Centre!

== The delivery options ==

- Immediate delivery
- Same day delivery
- Delivery window
- Next day delivery
- Faster returns!

Quiver is currently available in London and Paris and we're expanding quickly. Quiver's well-trained couriers (all full or part-time employees) will collect the selected item(s) from your store or our micro-fulfilment centre and quickly deliver them to your customer.

== Features ==

- Real-time, transparent and intuitive tracking so your customers always know where their order is.
- See the stock levels we hold and the deliveries completed in your dashboard.
- Transparent and fair pricing. We only make money when you make money!

With this extension, you can connect your Magento store to your Quiver admin panel where you can toggle and edit delivery options, add shippable locations and change rates. These changes will be reflected in the options shown to your customers at check-out.

Let us take care of the delivery, so you can focus on growing your business.

From checkout to delivered in under 60 minutes. Wow.

== Account & Pricing ==

A Quiver account is created during the installation process of this extension. It's free to install and free to create an account - Quiver only charges you per delivery. [You can find an overview of our pricing here](https://quiver.co.uk/pricing).

== Demo ==

Check out our 48-second demo video [here](https://www.loom.com/share/820140a8a531424e9436221d65325923).

== Installation ==

Follow the steps listed below to install the Quiver plugin for WooCommerce

1. Upload the downloaded plugin files to your `/wp-content/plugins/quiver-delivery` directory, **OR** install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Update your Quiver settings inside /wp-admin by clicking through WooCommerce > Settings > Shipping, select Quiver Delivery and provide your API key.
4. Give Quiver REST API access inside /wp-admin by clicking through WooCommerce > Settings > Advanced > REST API > Add key. Set "Description" to "Quiver". Set "Permissions" to "Read/Write". Click "Generate API key" and copy the Consumer Key and Consumer Secret to your settings page in app.quiver.co.uk.
5. Make sure Quiver shipping rates are never stale inside /wp-admin by clicking through WooCommerce > Settings > Shipping > Shipping options > Calculations. Untick “Enable the shipping calculator on the cart page” and tick “Hide shipping costs until an address is entered”.

== Frequently Asked Questions ==

== Screenshots ==

1. screenshot-1.jpg
1. screenshot-2.jpg
1. screenshot-3.jpg
1. screenshot-4.jpg
1. screenshot-5.jpg

== Changelog ==

= 1.0.0 =

- First public release

= 1.0.2 =

- Performance upgrades

= 1.0.3 =

- Quiver API compatibility updates

= 1.0.4 =

- Quiver API compatibility updates

= 1.0.5 =

- Support for PHP v8.08

= 1.0.6 =

- Quiver API compatibility updates
- Clear shipping rate cache when at checkout to remove stale Quiver rates

= 1.0.7 =

- Improved logging for debugging purposes

= 1.0.8 =

- Quiver API compatibility updates
- Specify a delivery date by adding \_delivery_date to your order's meta fields
- Small bugfixes

= 1.0.9 =

- Specify a delivery date by adding \_delivery*date*, delivery_date, Delivery Date, delivery date to your order's meta fields.

= 1.0.10 =

- More delivery date meta field options added.

= 1.0.11 =

- More flexibility with Delivery Date meta fields. Specify a delivery date by adding a new field to your order or order line items' meta data. The key must contain 'delivery' and 'date' to be recognised.

= 1.0.12 =

- Quiver API compatibility updates

= 1.0.13 =

- Accept alternative different delivery date format

= 1.0.14 =

- Support for back ordered line items. Add the 'Back-ordered' meta field to your product to delay delivery by 3 days.

= 1.0.15 =

- Support for date specific back ordered line items. Add a value to your 'Back-ordered' meta field in the format '.. x-y Days' to set the delivery date to today + y days. 3 days will be used as a default if a value in the aforementioned format is not provided.

= 1.0.16 =

- Quiver API compatibility updates
