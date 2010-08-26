=== My Taxonomy Order ===
Donate link: http://beapi.fr/donate/
Tags: taxonomies, taxonomy, order, sidebar, widget
Requires at least: 3.0
Tested up to: 3.0.1
Stable tag: 1.0.2

My Taxonomy Order allows you to set the order in which taxonomies will appear in the sidebar.
Original work from My category Order! by Andrew Charlton | http://www.geekyweekly.com

== Description ==

[My Taxonomy Order] allows you to set the order in which taxonomy will appear in the sidebar. Uses a drag 
and drop interface for ordering. Adds a widget with additional options for easy installation on widgetized themes.

For compatibility reasons the plugin [My Category order] is disabled by this plugin. 
This plugin make the same as [My Category order] but support all taxonomies.


== Change Log ==

= 1.0.0 =
* Version 1.0.0 : Initial release

= 1.0.2 =
* Fix bug with javascript in first loading (no differences between a taxonomy and a term/sub-term)

== Installation ==

1. Install and activate the plugin
3. Go to the "My Taxonomy Order" tab under Posts and specify your desired order for taxonomies.
4. If you are using widgets then replace the standard "Taxonomy" widget with the "My Taxonomy Order" widget. That's it.
5. If you aren't using widgets, modify sidebar template to use correct orderby value:
	`wp_list_categories('orderby=order&title_li=');`

== Frequently Asked Questions ==

= Why isn't the order changing on my site? =

The change isn't automatic. You need to modify your theme or widgets.

= I Like this plugin =

If you like the plugin, consider showing your appreciation by saying thank you or making a [small donation]( http://beapi.fr/donate/ ).