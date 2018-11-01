=== Shutter Reloaded ===
Contributors: Andrew Ozz
Donate link: 
Tags: images, javascript, viewer, lightbox
Requires at least: 2.5
Tested up to: 2.5
Stable tag: 2.1

Darkens the current page and displays an image on top (like Lightbox, Thickbox, etc.), but is a lot smaller (10KB) and faster.


== Description ==

Shutter Reloaded is an image viewer for your website that works similary to Lightbox, Thickbox, etc. but is under 10KB in size and does not require any external libraries. It has many features: resizing large images if the window is too small to display them with option to show the full size image, combining images in sets, redrawing the window after resizing, pre-loading of neighbour images for faster display and very good browser compatibility.

New in version 2.1: Upgrades for compatibility with WordPress 2.5.

New in version 2.0: Option to display full size image if it was resized to fit the browser window, display of image count (for sets), option for graphic or text buttons, support for localization (.pot file included), the code is better organized and improved.

New in version 1.2: Compatibility with WordPress version 2.0 (2.0.9 and 2.0.11) and 2.3, several improvements and small bugfixes. 

New in version 1.1: Support for Lightbox style activation (rel = lightbox[...]), better build-in help, several bugfixes.

This plugin offers customisation of the colour and opacity settings for the background and colour for the caption text, buttons text and the menu background.

There are options to enable it for all links pointing to an image on your site (with option to exclude some pages), or just on selected pages. It can be enabled only for image links with CSS class="shutter" with option to create a single set or multiple sets for each page.

The plugin can also "auto-make" image sets for each Post, so when several posts are displayed on the "Home" page, links to images on each post will be in a separate set. See the built-in help for more information.


== Installation ==

Standard WordPress quick and easy installation:
 
1. Download.
2. Unzip. 
3. Upload the shutter-reloaded folder to the plugins directory.
4. Activate the plugin.
5. Go to "Options - Shutter Reloaded" and set your preferences.

= Upgrade =

1. Deactivate and delete the old version.
2. Upload and activate the new one.


== Frequently Asked Questions == 

= Does this plugin work on WordPress version... =

Shutter Reloaded has been tested on WordPress 2.0.11, 2.1, 2.2, 2.3 and 2.5. For WordPress version 2.3.3 and earlier, please use version 2.0 of the plugin.

= I have ... plugin installed that uses javascript, will there be any conflicts/incompatibilities? =

Since Shutter Reloaded does not use any js libraries, it does not interfere with them. It uses an onload event but has a function to play nice with other scripts that use onload too (from WordPress).

= What will happen if my site visitors have Javascript disabled? =

Then none of your links will be changed and will work as usual.

= I have a thumbnail link but it points to a webpage, not to image. Will that affect Shutter Reloaded? =

No, Shutter Reloaded looks only for links pointing to an image (with thumbnails or not), and will not change any other link, even if the link has the same CSS class used for activation.


== Screenshots ==

For screenshots and demo, visit the home page for [Shutter Reloaded](http://www.laptoptips.ca/projects/wp-shutter-reloaded/). 
