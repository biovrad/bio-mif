=== Plugin Name ===
Contributors: weefselkweekje
Donate link: http://www.roytanck.com
Tags: tag cloud, flash, sphere, categories, widget
Requires at least: 2.3
Tested up to: 2.6.2
Stable tag: 1.14

WP-Cumulus displays your tags and/or categories in 3D by placing them on a rotating sphere.

== Description ==

WP-Cumulus allows you to display your site's tags, categories or both using a Flash movie that rotates them in 3D. It works just like a regular tags cloud, but is more visually exciting. Clicking the tags can be a little hard (depending on your speed setting) but does take you to the appropriate page :).

The sources code for the Flash movie are available from subversion.

== Installation ==

= Installation =
1. Make sure you’re running WordPress version 2.3 or better. It won’t work with older versions. Really.
1. Download the zip file and extract the contents.
1. Upload the 'wp-cumulus' folder to your plugins directory (wp-content/plugins/).
1. Activate the plugin through the 'plugins' page in WP.
1. See 'Options->WP Cumulus' to adjust things like display size, etc…

= In order to actually display the tag cloud, you have three options. =
1. Create a page or post and type [WP-CUMULUS] anywhere in the content. This 'tag' will be replaced by the flash movie when viewing the page.
1. Add the following code anywhere in your theme to display the cloud. `<?php wp_cumulus_insert(); ?>` This can be used to add WP Cumulus to your sidebar, although it may not actually be wide enough in many cases to keep the tags readable.
1. The plugin adds a widget, so you can place it on your sidebar through 'Design'->'Widgets'. The widget uses a separate set of settings, so it's possible to have different background colors, sizes, etc.

== Frequently Asked Questions ==

= I see a regular tag could and a line of of text about how I need the Flash plugin =
It appears you either do not have Flash Player 9 or better installed, or you've disabled javascript.

= Hey, but what about SEO? =
I’m not sure how beneficial tag clouds are when it comes to SEO, but just in case WP Cumulus outputs the regular tag cloud (and/or categories listing) for non-flash users. This means that search engines will see the same links.

= I’d like to change something in the Flash movie, will you release the .fla? =
As of  version 1.12 the source code is included in the download.

= My theme/site appears not to like this plugin. It's not displaying correctly. =
Older versions had issues with PHP 5.2 (or better). This has been fixed. If you're having trouble getting WP-Cumulus to work, get the latest version, try a different theme, and check for HTML markup errors. If this does not help, please contact me with as much info as you can.

= Some of my tags occasionally hit the sides of the movie and are cropped =
If this happens you should change the aspect for the movie to make it wider. This can be done by increasing the width, but also by decreasing the height. Both will make the movie 'more landscape' giving long tags more room.

= Some characters are not showing up =
Because of the way Flash handles text, only Latin characters are supported in the current version. This is due to a limitation where in order to be able to animate text fields smoothly the glyphs need to be embedded in the movie. The Flash movie's source code is available for download through Subversion. Doing so will allow you to create a version for your language. There's a text field in the root of the movie that you can use to embed more characters. If you change to another font, you'll need to edit the Tag class as well.

More info [here](http://www.roytanck.com/2008/08/04/how-to-add-more-characters-to-wp-cumulus/).

= When I click on tags, nothing happens. =
This is usually caused by a Flash security feature that affects movies served from another domain as the surrounding page. If your blog is http://yourblog.com, but you have http://www.youblog.com listed as the ‘WordPress address’ under Settings -> General this issue can occur. In this case you should adjust this setting to match your blog’s actual URL. If you haven’t already, I recommend you decide on a single URL for your blog and redirect visitors using other options. This will increase your search engine ranking and in the process help solve this issue :).

= I’m not using WordPress… =
* Steve Springett has ported this to Movable Type. More info over on [his site](http://www.6000rpms.com/blog/2008/04/04/flash-tag-cloud-for-mt-4.html).
* Michael Robinson has ported WP-Cumulus to RapidWeaver, see his tutorial [here](http://pagesofinterest.net/mikes/blog_of_interest_files/tag_cloud.php).
* Amanda Fazani managed to get Cumulus working on Blogger. More info on Blogumus [here](http://www.bloggerbuster.com/2008/08/blogumus-flash-animated-label-cloud-for.html).
* Yannick Lejeune has doen a [TypePad version](http://www.yannicklejeune.com/2008/09/tumulus-wp-cumu.html) based in part on Steve's work.
* I wrote [this post](http://www.roytanck.com/2008/05/19/how-to-repurpose-my-tag-cloud-flash-movie/) on how to use the flash movie in other contexts.

== Screenshots ==

1. The tag sphere. You can set colors that match your theme on the plugin's options page.
2. The options panel.
3. There's a separate one for the widget.

== Options ==

The options page allows you to change the Flash movie’s dimensions, change the text color as well as the background.

= Width of the Flash tag cloud =
The movie will scale itself to fit inside whatever dimensions you decide to give it. If you make it really small, chances are people will not be able to read less-used tags that are further away. Anything up from 300 will work fine in most cases.

= Height of the Flash tag cloud =
Ideally, the height should be something like 3/4 of the width. This will make the rotating cloud fit nicely, while the extra width allows for the tags to be displayed without cropping. Western text is horizontal by nature, which is why the ideal aspect is slightly landscape even though the cloud is circular.

= Color of the tags =
Type the hexadecimal color value you’d like to use for the tags, but not the '#' that usually precedes those in HTML. Black (000000) will obviously work well with light backgrounds, white (ffffff) is recommended for use on dark backgrounds.

= Background color =
The hex value for the background color you’d like to use. This options has no effect when 'Use transparent mode' is selected.

= Use transparent mode =
Turn on/off background transparency. Enabling this might cause issues with some (mostly older) browsers. Under Linux, transparency doesn't work in at all due to a known limitation in the Flash player.

= Rotation speed =
Allows you to change the speed of the sphere. Options between 25 and 500 work best.

= Distribute tags evenly on sphere =
When enabled, the movie will attempt to distribute the tags evenly over the surface of the sphere.

= Display =
Choose whether to show tags only, categories only, or both mixed together. Choosing 'both' can result in 'duplicate tags' if you have categories and tags with the same name. These words will appear twice, with one linking to the tag and the other to the category overview.

= wp\_tag\_cloud parameters = 
This setting allows you to pass parameters to the wp\_tag\_cloud function, which is used to fetch the tag cloud. Use caution with this setting. Everything you enter will be passed to the function. Be sure to read the function’s manual. Please also note that these parameters affect tags only. If you’ve chosen to show categories or both, the category 'tags' will not be affected.


== Version history ==

= Version 1.14 =
+ Fixes an issue where no tags are displayed when viewing the movie locally in MSIE.
+ Fixes an issue where one random tag would not be displayed.

= Version 1.13 =
+ No longer breaks when the wp-content folder is moved to a non-standard location.

= Version 1.12 =
+ First version hosted on WordPress.org, and released under GPL license.
+ Uses the Arial font to avoid font licensing issues.

= Version 1.11 =
+ Restores an earlier fix for IE to force loading of the Flash movie.

= Version 1.1 =
+ Complete rewrite of the Flash movie (Actionscript 3, requires Flash Player 9 or better).
+ Better mouse detection.
+ Adds option to distribute the tags evenly over the sphere.
+ Adds support for categories.
+ Adds the ability to pass parameters to the WordPress wp_tag_cloud function.
+ Several smaller enhancements.

= Version 1.05 =
* Fixes several issues with IE, including an issue where it was impossible to use the regular version and the widget on the same page. Thanks to Fadi for alerting me to these.

= Version 1.04 =
* Fixes the 'it kills my blog' error for people using PHP 5.2 or newer. Thanks to Mujahid for helping me debug this.
* Speed improvements in the Flash code.

= Version 1.03 =
* Removes the wp_head hook in yet another attempt to fix issues with some other plugins and themes.
* Reduces system overhead by storing less options.
* Adds setting for speed.
* Adds a widget with seperate options (size, colors, speeds, etc).
* Attemps to detect when the mouse leaves the movie, reducing the 'spinning, but not out of control' effect.
* Several minor fixes.

= Version 1.02 =
* Fixes issues with sites not loading after activation, reduces server load and fixes lost spaces in tags. Thanks to Dimitry for helping me debug these issues.

= Version 1.01 =
* Fixes an issue where the cloud would spin out of control when the browsers loses focus on OSX.

= Version 1.00 =
* Initial release version.
