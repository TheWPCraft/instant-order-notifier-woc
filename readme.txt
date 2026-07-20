=== Instant Order Notification for WooCommerce – Get Audio Alert on new Orders ===
Contributors: thewpcraft, alkesh7
Tags: order notification, order alert, woocommerce notification
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.0
WC tested up to: 9.3

Instant popup, sound, and desktop alerts the moment a WooCommerce order arrives — plus a live dashboard and recent-orders table in wp-admin.

== Description ==

Instant Order Notification for WooCommerce helps store owners stay informed the moment a new order is placed. No more constantly refreshing the WooCommerce orders page or keeping the dashboard open all day.

As soon as a customer places an order, the plugin instantly displays a popup notification, plays a notification sound, sends a desktop browser notification, and automatically refreshes the recent orders table—so you never miss an order.

Designed for busy WooCommerce store owners, this lightweight plugin keeps you updated in real time while you focus on running your business.

What happens when a new order arrives?
- Displays a clean popup with Order ID, Customer Name, Order Total, and Order Status
- Plays an instant audio notification
- Sends a desktop browser notification (with permission)
- Automatically refreshes the recent orders table
- Updates dashboard summary cards instantly
- Keeps you informed without manually refreshing the WooCommerce dashboard

Free Features
🚀 Real-Time Order Detection
Instantly detects new WooCommerce orders as soon as they are placed.

🔔 Popup Notifications
Displays a clean and modern popup with important order details.

🔊 Audio Notifications
Never miss a new order with built-in notification sounds.

🖥 Desktop Browser Notifications
Receive browser notifications even while working in another tab.

📊 Dashboard Summary

View your store performance at a glance.
- Today's Orders
- Processing Orders
- Completed Orders
- Cancelled Orders

📋 Recent Orders Table
Automatically refreshes the latest orders without reloading the page.

🔍 Order Status Filter
Quickly filter orders by status.
- Processing
- Pending
- Completed
- Cancelled
- On Hold
- Failed

🎵 Multiple Notification Sounds
Choose from three built-in ringtone options.

🔇 Sound Control
Mute or unmute notifications anytime with a visual reminder.

⚡ Adjustable Check Speed
Choose how frequently the plugin checks for new orders.
- Fast (1 Second)
- Normal (2 Seconds)
- Slow (6 Seconds)

📱 Responsive Admin Interface
Works perfectly across desktop, tablet, and mobile devices.

⚡ Lightweight & Optimized
Built for speed with no unnecessary scripts or external CDN dependencies.

🚀 Upgrade to Pro
Take your WooCommerce order notifications to the next level with powerful WhatsApp Notifications and advanced notification rules.
The Pro version ensures you receive WhatsApp alerts only for the orders that matter most.

Pro Features

📲 Instant WhatsApp Order Notifications
Receive instant WhatsApp notifications whenever a new WooCommerce order is placed.
Stay connected with your store even when you're away from your computer.

💰 Minimum Order Value Notifications
Avoid unnecessary notifications by sending WhatsApp alerts only when an order reaches your preferred minimum amount.
Examples:
- Notify only for orders above ₹500
- Notify only for orders above ₹1000
- Set any custom order value
Perfect for stores that want alerts only for high-value orders.

🛍 Product-Based Notifications
Choose exactly which WooCommerce products should trigger WhatsApp notifications.
Ideal for:
- Premium Products
- Best Sellers
- Limited Stock Products
- High Priority Items

📂 Category-Based Notifications
Receive WhatsApp notifications only when customers purchase products from selected categories.
Perfect for stores with multiple departments or product categories.

🏷 Product Tag Notifications
Send WhatsApp notifications only for products containing selected product tags.
Gain precise control over which orders generate alerts.

🎯 Smart Notification Rules
Create advanced notification rules by combining:
- Minimum Order Value
- Selected Products
- Selected Categories
- Selected Product Tags
Receive WhatsApp notifications only when your custom conditions are met.

⚡ Priority Updates
Get access to new premium features and improvements before the free version.

💬 Premium Support
- Receive fast and dedicated support for all Pro users.
- Why Choose Instant Order Notification?
- Never miss a WooCommerce order
- Instant popup, sound, and desktop notifications
- Stay informed in real time
- Save time by eliminating manual dashboard refreshes
- Advanced WhatsApp notifications available in Pro
- Smart filtering to reduce unnecessary alerts
- Lightweight, fast, and easy to use
- Beginner-friendly setup
- Perfect For
- WooCommerce Store Owners
- Online Businesses
- Retail Stores
- Wholesale Businesses
- Grocery Stores
- Restaurants & Food Delivery
- Fashion Stores
- Electronics Stores
- Print-on-Demand Stores
- Dropshipping Businesses
- Digital Product Sellers
- Upgrade to Pro
Unlock the complete notification experience with advanced WhatsApp features.

✅ Pro Includes
- Instant WhatsApp Order Notifications
- Minimum Order Value Rules
- Product-Based Notifications
- Category-Based Notifications
- Product Tag Notifications
- Smart Notification Rules
- Premium Support
- Priority Updates
- Future Premium Features
Instant Order Notification Pro gives you complete control over your WooCommerce notifications, helping you stay informed while reducing unnecessary alerts. Configure powerful notification rules and receive WhatsApp notifications only for the orders that matter most.

== Installation ==

1. Upload the `instant-order-notifier-woc` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Order Notifier** in the admin menu
4. You're all set! New orders will trigger instant alerts

== Frequently Asked Questions ==

= Does this work with all WooCommerce themes? =

Yes. It works in the WordPress admin area only, so no conflicts with frontend themes.

= Can I change the notification sound? =

Yes! Go to **General Settings** → choose from 3 built-in ringtones.

= Can I disable sound but keep the popup? =

Yes. Click the sound button to mute — you'll still see the popup alert.

= How frequently does it check for new orders? =

You can set it in settings: Fast (every 1 second), Normal (every 2 seconds), or Slow (every 6 seconds).

= Is it compatible with the latest WooCommerce? =

Yes. Fully tested with WooCommerce 9.3 and WordPress 7.0.

= Do I need to allow browser notifications? =

Yes. The first time you enable desktop notifications, your browser will ask for permission. Once allowed, you will receive instant alerts for new orders.

= Will desktop notifications work if I switch tabs? =

Yes. You will still receive the notification even if you are working in another browser tab.


== Changelog ==

= 1.4.2 =
* SECURITY: Fixed a stored XSS in the new-order popup (customer billing name was not escaped before being rendered).
* SECURITY: Added missing capability checks on several AJAX endpoints (order list, dashboard stats, mark-as-seen).
* SECURITY: Stopped shipping the Twilio Auth Token and other saved credentials into page/JS source; disabled autoload on the settings option.
* FIX: Corrected a mismatched text domain and a settings-page fatal caused by two undefined sanitizer functions.
* FIX: Corrected a cache-group typo and a timezone-unsafe date() call in the dashboard stats.
* Removed the Font Awesome CDN dependency (cdnjs.cloudflare.com); icons now use the Bootstrap Icons set already bundled with the plugin.
* Confirmed compatibility with WordPress 7.0 and WooCommerce 9.3.
* Various code-quality and documentation improvements (WordPress Coding Standards compliance).

= 1.4.1 =
* Updated plugin images and screenshots.
* Refined descriptions and content.
* Fixed several bugs.

= 1.4.0 =
* PRO: Added Advanced Notification Rules.
* PRO: Filter notifications by products, categories, tags and minimum order amount.
* PRO: Added Twilio WhatsApp notifications for new WooCommerce orders.
* Improved settings UI and overall performance.
* Minor bug fixes and code optimizations.

= 1.3.2 =
* Added popup queue for multiple orders
* Fixed multiple modal issue
* Disabled outside click to close popup
* Auto show next popup after close
* Fixed JS error (data undefined)
* Improved sound handling with localStorage
* Better audio compatibility
* Minor bug fixes & performance improvements

= 1.3.1 =
* NEW: Added feedback form on plugin deactivation to collect user insights
* NEW: Added support button for quick help and user assistance
* UPDATED: Improved UI design for better user experience
* UPDATED: Updated plugin images and visual assets
* Minor bug fixes and performance improvements

= 1.3.0 =
* NEW: Dashboard summary cards (Today Orders, Processing, Completed, Cancelled)
* NEW: Status-wise filtering for orders table
* NEW: Improved UI with better card design and layout
* Improved AJAX performance for order loading
* Minor bug fixes and UI enhancements

= 1.2.0 =
* NEW: Desktop browser notification for new WooCommerce orders
* NEW: Orders table pagination added for better order management
* Improved order table performance
* Minor UI improvements

= 1.1.1 =
* Full-screen black overlay when sound is muted
* Overlay only removes on mute/unmute button click (not on overlay click)
* Sound now only unmutes via the dedicated button
* Overlay now more prominent (darker opacity)
* Popup showing even when muted (as per user request)

= 1.1.0 =
* Layout and styling updates
* Improved responsiveness for mobile and tablet
* Minor bug fixes and performance enhancements

= 1.0.0 =
* Initial release
* Real-time order detection
* Sound and popup notifications
* Auto-refresh order table

== Upgrade Notice ==

= 1.4.2 =
Security update: fixes a stored XSS in the new-order popup and adds missing capability checks on AJAX endpoints. Updating is recommended for all users.