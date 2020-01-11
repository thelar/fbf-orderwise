=== Plugin Name ===
Contributors: raisonon
Donate link: https://www.chapteragency.com
Tags: orderwise, woocommerce
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrates WooCommerce Orders with Orderwise

== Description ==

Provides endpoint for export of orders to be received as XML for Orderwise.
Provides endpoint for orders to be marked as complete by Orderwise.

Two endpoints created

/api/v2/orderwise_export
/api/v2/orderwise_success

== Installation ==

Head to WooCommerce > export

1. Choose Custom Formats Tab
2. Create Custom Format called 'OrderWise'
3. Choose Orders (default), Set Output to XML, create one field (not used but required)
4. For debugging you can choose Indent Output
5. Save
6. Head to Automated Exports
7. Create new Automated Exports
8. Choose XML, Orders, Format = Orderwise
9. Order Status can be selected to just new orders (processing) -- note that after the order is imported then this status is cahnage to compelte.



== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`