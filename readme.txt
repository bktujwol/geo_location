=== Geo Location ===
Contributors: ujw0l
Tags: visitor,tracking, bing map, mapping, IP tracking, IP Address blocking 
Requires at least: 3.5	+
Tested up to: 4.9.6
Stable tag: 2.0
License: GPLv2

Plugin that that collect geological info of visitors and display them in Bing Map and gives admin ability to block them.	


== Description ==

This plugin lets you track vistors IP and Map them in Bing Map, or just can be use in backend only. Good for tracking website activities based on geographical region.

This plugin also lets you block users based on IP address from admin menu.

You can also use it with out IPinfoDB API key, just to block IP address, however Location functionality will not work.

You can change map type now.

It displays all of the unique vistiors on the bing map

You need to get IPinfobd API key from 
https://ipinfodb.com/register.php

Bing Map API key from(Developers have option of getting free key)
https://www.microsoft.com/en-us/maps/choose-your-bing-maps-api

Note:     


== Installation ==

1. Upload the folder `geolocation_master` and its contents to the `/wp-content/plugins/` directory or use the wordpress plugin installer
2. Activate the plugin through the 'Plugins' menu in WordPress
3. A new "Geo Locationt" will be available under Settings, 
4) You must to get both API keys put them into respective field for application to work
5) Then you have to put [getip] shortcode to the page whose visitor you want to track
6) To display visitors in map you have to put [displaymap] shortcode to the page where you want display the map
  


= Uninstall =

1. Deactivate Geo Location in the 'Plugins' menu in Wordpress.
2. After Deactivation a 'Delete' link appears below the plugin name, follow the link and confim with 'Yes, Delete these files'.
3. This will delete all the plugin files from the server as well as erasing all options the plugin has stored in the database.

== Frequently Asked Questions ==

= Why can't I customize it or display more feeds? =

This is first release I wish to make it more customizable on future releases please provide feedback so i will know what are you looking for.

= Where can I use this plugin? =

You need to use the short code in the post and pages

== Screenshots ==

1.  Screenshot of the backend visitors display page tab 
2.  Screen shot of backend with API key form 
3.  Screenshot of single visitor in bing map on thickbox in admin area.
4.  Screenshot of all unique visitors mapped in bing map  
5.  Screenshot of page blocked visitors will see. 


== Changelog ==

= 1.0 =
* This is first stable version 
* Many upgrades to follow

= 1.1 =
* Now admin can block/unblock visitors absed on IP Address
* Admin can now dlete visiters from database
* Better Admin UI 
* Many upgrades to follow

= 2.0 =
* Collectes data Acronymously
* Admin can select map type to display
* Admin can select map dimension
* Better Admin UI 
* Displays visitors count
*Uses CTC overlay instead of thickbox now 


