=== Adminimize ===
Contributors: Bueltge
Donate link: http://bueltge.de/wunschliste/
Tags: color, scheme, theme, admin, dashboard, color scheme, plugin, interface, ui, metabox, hide, editor, minimal, menu, customization, interface, administration, lite, light, usability, lightweight, layout, zen
Requires at least: 2.5
Tested up to: 2.7beta
Stable tag: 0.5

At first: Visually compresses the administratrive header so that more admin page content can be initially seen. Also moves 'Dashboard' onto the main administrative menu because having it sit in the tip-top black bar was ticking me off and many other changes in the edit-area.
At second. Adminimize is a WordPress plugin that lets you hide 'unnecessary' items from the WordPress administration menu, submenu and even the 'Dashboard', with forwarding to the Manage-page. On top of that, you can also hide post meta controls on the Write page and other areas in the admin-area and Write-page, so as to simplify the editing interface. All is addicted from your rights, admin (user_level 10) or not admin-rights (smaller user_level 10).

== Description ==
Visually compresses the administratrive header so that more admin page content can be initially seen. Also moves 'Dashboard' onto the main administrative menu because having it sit in the tip-top black bar was ticking me off and many other changes in the edit-area. Adminimize is a WordPress plugin that lets you hide 'unnecessary' items from the WordPress administration menu, submenu and even the 'Dashboard', with forwarding to the Manage-page. On top of that, you can also hide post meta controls on the Write page and other areas in the admin-area and Write-page, so as to simplify the editing interface. Compatible with WordPress 2.5 or later. 
Configure all metaboxes and other areas in the write-area. The new theme move the Tags- and Categorys-box to the sidebar, switch off optional metaboxes and other areas in the write-area. Scoll automatocly to the Textbox, when you click the write-button. Many options for menu, submenu and all areas, metaboxes in the write-area, separated for admins (user_level 10) and other users (< user_level 10).

= Compatibility with the drop-down menu plugins =
1. [Ozh Admin Drop Down Menu](http://planetozh.com/blog/my-projects/wordpress-admin-menu-drop-down-css/ "Admin Drop Down Menu for WordPress 2.5") by Ozh
1. [Drop Down Admin Menus](http://www.stuff.yellowswordfish.com/ "Drop Down Admin Menus for WordPress 2.5") by Andy Staines

= Requirements =
1. WordPress version 2.5 and later

Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") for further details and the latest information on this plugin.

= What does this plugin do? =
The plugin changes the administration backend and gives you the power to assign rights on certain parts. Admins can activate/deactivate every part of the menu and even parts of the submenu. Meta fields can be administered separately for posts and pages. Certain parts of the write menu can be deactivated separately for admins or non-admins. The header of the backend is minimized and optimized to give you more space and the structure of the menu gets changed to make it more logical - this can all be done per user so each user can have his own settings.

= Details =
1. the admin theme can be set per user. To change this go to user settings
1. currently you can use the theme together with the color settings for the Fresh and Classic themes
1. more colors can be easily added
1. new menu structure: on the left hand site you find classic menu points for managing and writing, while the right part is reserved for settings, design, plugins and user settings
1. the dashboard has been moved into the menu itself but this can be deactivated if its not desired
1. the menu is now smaller and takes up less space
1. the WRITE menu has been changed as follows:
1. it is no longer limited to a fixed width but flows to fill your whole browser window now
1. you can scroll all input fields now, no need to make certain parts of the WRITE screen bigger
1. categories moved to the sidebar
1. tags moved to the sidebar if you are not using the plugin "Simple Tags"
1. the editing part gets auto-scrolled which makes sense when using a small resolution
1. the media uploader now uses the whole screen width
1. supports the plugin "Admin Drop Down Menu" - when the plugin is active the user has two more backend-themes to chose from
1. supports the plugin "Lighter Menus" - when that plugin is active the user has another two backend-themes to chose from
1. two new color schemes are now available
1. the width of the sidebar is changeable to standard, 300px, 400px or 30%
1. each meta field can now be deactivated (per user setting) so it doesn't clutter up your write screen
1. you can even deactivate other parts like h2, messages or the info in the sidebar
1. the part of the user info you have on the upper - right part of your menu can be deactivated or just the log-out link
1. the dashboard can be completely removed from the backend
1. all menu and sub menu points can be completely deactivated for admins and non-admins
1. most of these changes are only loaded when needed - i.e. only in the write screen
1. set a backend-theme for difficult user
1. ... many more

== Installation ==
1. Unpack the download-package

2. Upload folder include all files to the `/wp-content/plugins/` directory.  The
final directory tree should look like `/wp-content/plugins/adminimize/adminimize.php`, `/wp-content/plugins/adminimize/adminimize_page.php`, `/wp-content/plugins/adminimize/css/` and `/wp-content/plugins/adminimize/languages`

3. Activate the plugin through the `Plugins` menu in WordPress

4. Colour Scheme and Theme selection in Your Profile Go to your User Profile (under `Users` > `Your Profile` or by clicking on your name at the top right corner of the administration panel).

4. Administrator can go to `Options` > `Adminimize` menu and configure the plugin (Menu, Submenu, Metaboxes, ...)

= Advice =
Please use the `Deinstall-Funktion` in the option-area bevor update to version 1.4! Version 1.4 and bigger has only one databas entry and the `Deinstall-Option` desinatll the old entrys. 

See on [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize").

== Screenshots ==
1. minimize header after activate
1. configure-area for user
1. configure-area for user/admin; options for metaboxes, areas in write-area and menu
1. Adminimize Theme how in WordPress 2.3

== Other Notes ==
= Acknowledgements =
Thanks to [Eric Meyer](http://meyerweb.com/ "Eric Meyer") for the Idea and the Stylesheet to minimize the header of backend and thanks to [Alphawolf](http://www.schloebe.de/ "Alphawolf") for write a smaller javascript with jQuery.
Also Thanks to [Ovidio](http://pacura.ru/ "pacaru.ru") for an translations the details in english.


= Licence =
Good news, this plugin is free for everyone! Since it's released under the GPL, you can use it free of charge on your personal or commercial blog. But if you enjoy this plugin, you can thank me and leave a [small donation](http://bueltge.de/wunschliste/ "Wishliste and Donate") for the time I've spent writing and supporting this plugin. And I really don't want to know how many hours of my life this plugin has already eaten ;)

= Translations =
The plugin comes with various translations, please refer to the [WordPress Codex](http://codex.wordpress.org/Installing_WordPress_in_Your_Language "Installing WordPress in Your Language") for more information about activating the translation. If you want to help to translate the plugin to your language, please have a look at the sitemap.pot file which contains all defintions and may be used with a [gettext](http://www.gnu.org/software/gettext/) editor like [Poedit](http://www.poedit.net/) (Windows).

== Frequently Asked Questions ==
= History? =
Please see the changes on version on the [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize")!

= Where can I get more information? =
Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") for the latest information on this plugin.

= I love this plugin! How can I show the developer how much I appreciate his work? =
Please visit [the official website](http://bueltge.de/wordpress-admin-theme-adminimize/674/ "Adminimize") and let him know your care or see the [wishlist](http://bueltge.de/wunschliste/ "Wishlist") of the author.