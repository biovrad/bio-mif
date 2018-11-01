=== Lighter Menus ===
Contributors: corpodibacco
Tags: menu, admin, css, drop down, light
Requires at least: 2.5
Tested up to: 2.6
Stable tag: 2.7.2

Creates Drop Down Menus instead of the regular Admin menus for WordPress, so that you can browse items with one click. Ready for Worpress 2.6+.

== Description ==

Lighter Menus creates Drop Down Menus instead of the regular Admin menus for WordPress, so that you can browse items with one click. Fast to load, adaptable to color schemes, Lighter Menus comes with silk icons, a option page, and a design that fits within the Wordpress interface taking the less room possible.

== Screenshots ==

1. At work under Firefox 2.x.

2. At work under Internet Explorer 7 with the Classic color scheme.

3. Under Opera 9.x

4. With Safari for windows and the classic color scheme.

5. The option page.

== Installation ==

1. Upload the entire content of plugin archive to your `/wp-content/plugins/` directory, so that everything will remain in a `/wp-content/plugins/lighter-menus/` folder
2. Activate the plugin through the 'Plugins' menu in WordPress (deactivate and reactivate if you're upgrading).
3. Review the option page in "Design" > "Lighter menus"
4. Done. Enjoy.

== Translations ==

This version of Lighter Menus features localizations in:

* Chinese, thanks to [dupola](http://dupola.com)
* Japanese, thanks to [ne-design](http://www.ragard-jp.com)
* German, thanks to Kristian Bollnow
* Chinese (Taiwan), thanks to [hit1205](http://hit1205.blogdns.org/blog)
* Russian, thanks to [rlector](http://www.wordpressplugins.ru/) (also thanks to Dimox who proposed his translation approximately at the same moment)
* Polish, thanks to x600
* Swedish, thanks to [Mikael Jorhult](http://www.mishkin.se/)
* Spanish, thanks to [Alejandro Urrutia Daglio](http://theindependentproject.com/)
* Italian, thanks to, well, yours truly.

More are needed! If you want to translate this plugin in your language you are very welcome! In fact there are very few lines to translate. Just be aware that despite what [poEdit](http://www.poedit.net/) wants you to do, you should translate only the items that in the code are actually under the textdomain 'lighter-menus'. You will notice in lighter-menus.php that 35 or so translatable items have no textdomain indicated (they look like this: `__('Dashboard')`, instead of this: `__('Dashboard', 'lighter-menus')` ). They're in the functions "lm\_icons" and "lm\_build" and they are just menu items names used for reference when binding them with the respective icons or style classes. In such cases just leave the translation empty, it would be useless work to translate said items since they are not for the user to read. Your Wordpress locale takes care of those translations.

== Changelog ==

* 2.7.2 This version simply adds new localizations of Lighter Menus. Polish thanks to x600, Swedish thanks to [Mikael Jorhult](http://www.mishkin.se/) and Spanish thanks to [Alejandro Urrutia Daglio](http://theindependentproject.com/). In case you were wondering, that couple of minor problems with other plugins that have been pointed out recently on the blog have NOT been investigated or addressed by this release because there is no time. No time, my friends.  Ugly words indeed but that's how it goes.
* 2.7.1 This version fixes a couple of minor problems given by the new WP 2.6: the press-this page, that didn't work (thanks to [Robert](http://robv.de/) for pointing this bug out), and the notification bubble of the plugins menu, which wasn't considered. This version also includes a Russian translation, thanks to [rlector](http://www.wordpressplugins.ru/).
* 2.7 This version includes Chinese-Taiwan translation. Thanks to [hit1205](http://hit1205.blogdns.org/blog) for the effort! hit1205 also heroically helped to fix the "no howdy" feature for Wordpress Chinese. Thanks man! This version also implements a new feature, asked for by Callum on the blog: the ability to optionally show the comment bubble even if there is nothing to moderate. You're welcome, man! :) I also cleaned a little the appearance of the above mentioned bubble, it should look less ugly now. 
And with this I'm really done. An almost round number, 2.7 is _really_ going to be the last update for a while. Have a nice blogging, everyone!
* 2.6.9 A leftover "blah" of some testing I had done in the past was still in the output. It's a miracle it didn't show up in a worse way. A big thank you to Shobba for poiting this out and apologies for yet another bugfix update... I also fixed something with the deactivator... Anyway, I am amazed, there's always something to fix!
* 2.6.8 This version includes a Japanese translation, thanks to [ne-design](http://www.ragard-jp.com), and a German translation, thanks to Kristian Bollnow. Much obliged to both of them! I also managed to have the "no howdy" option working with Wordpress in japanese (still no luck with the chinese version, I need a guru of regex to understand this), and fixed a problem with menu icons when running with Wordpress in japanese (thanks to ne-design for pointing it out). Also, I made the regular menu link color a little brighter, and cleaned the code here and there. I think this will be the last update for a while so let's hope everything is fine with it.
* 2.6.7 This version includes Chinese translation! A million thanks go to [dupola](http://dupola.com) for the effort. Also: I fixed a pretty ugly bug that broke the optional different colors of the sidemenu with local versions of Wordpress; I changed the aspect of the option page to make it more similar to the regular Wordpress 2.5 option pages; I fixed the removal of 'Help' and 'Forums' links, where local versions of Wordpress that personalized those links would broke the function; I added the option to remove the 'Howdy', too, although this does not work with the chinese version of Wordpress. While I was there I also redid the italian translation of the plugin, it was horrible. Now it is just a little less horrible. I can't believe how much the IT lexicon sounds ugly in italian. Finally, once again thanks to [dupola](http://dupola.com) who made me notice a couple of oversights, including that the link to the plugin home page was mysteriously all scrambled. All should be fixed now.
* 2.6.6 Corrected a bug that prevented the new 2.5.1 media uploader to work with Lighter Menus. Now everything should get along finely. Also fixed the comment awaiting moderation message to incorrectly show up with 0 comments to moderate. Recommended.
* 2.6.5 This version revamped almost entirely the most part of the code... It already was in a way, but this plugin is basically now an adaptation of the current Ozh's [Admin Drop Down Menus](http://planetozh.com/blog/my-projects/wordpress-admin-menu-drop-down-css/) plugin. Anyway, my plugin required many hours of extra work to implement new features including an option page (now with more options), more consistent colors and adaptation to color schemes (also custom ones), an uninstaller etc. This update also fixes a lot of bugs and potential bugs compared to version 2.6.1, and also imports from Ozh a number of great features like WPMU support, the ability to optionally display the original submenu, and the wrapping of the main menu within smaller windows. Useless to say how much I am grateful to the man.
* 2.6.1 This version adds an easy to use uninstaller to optionally remove every trace of Lighter Menus upon deactivation. This feature can be found in the Design > Lighter Menus page. You won't notice, but this version also moves all the options in only one record in the database to occupy less space. Okay, there were only two records before, but still. IMPORTANT: because of the modified options, you are advised to deactivate and reactivate the plugin upon upgrading. Also in this version there are few small fixes. I always find small things to fix, I can't say why.
* 2.6 Fixed a bug with Safari not adjusting the width of the menus correctly and other minor fixes.
* 2.5.9 This version prepared Lighter menus for localization. Do come forward if you're willing to translate this plugin in your language, there are very few lines to translate. Only italian and default english are available so far (please check the "Technical note for Translators"). Also, I slightly changed the name of the plugin, I hated the old name, I hope you'll understand.
* 2.5.8 Fixed a issue with other plugins adding extra long menu entries. Now menus widen and narrow at ease. Thanks to Karl for pointing out this.
* 2.5.7 Added a nifty option page where you can toggle a couple of customizations, like menu icons. For your convenience, the necessary files of the plugin now consist in just one file "lighteradminmenus.php", and the "images" folder. All the css files are now integrated and can be removed from the plugin folder after the upgrade.
* 2.5.6 Tried to distinguish chromatically the elements of the menu accordingly to Wordpress 2.5,  sliding them to the right in the main menu. Maybe a good idea, maybe not. Let me know.
* 2.5.5 I never realized Wordpress 2.5 had two color schemes (thanks tonchi!). Lighter admin menus now adapts to this, quite beautifully I must say. Also very important fix: the menu now gives feedback when editing posts and pages (it failed to before).
* 2.5.4 fixed the awaiting comments alert and other minor glitches. Cleaned the code a good deal. Recommended.
* 2.5.3 slight redesign to favor smooth corners in the menus. 
* 2.5.2 small fixes (thanks to Robert!) and a slight redesign favoring the "tab" effect. Also FF 2 and IE 7 look more alike now.
* 2.5.1 small fixes
* 2.5 redesigned to fit into wordpress 2.5.
* 2.3 trying to fix a function that breaks translations of wordpress. We'll see.
* 2.2 actually the first version of this plugin. It should be alpha something. But because I haven't changed a line of php code in it, it is only fair that the version number should depend on the plugin I used as the base for this.
