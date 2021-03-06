=== TAC (Theme Authenticity Checker) ===
Contributors: builtBackwards
Donate link: http://builtbackwards.com/donate
Tags: themes, security, javascript, admin
Requires at least: 2.2
Tested up to: 2.6.1
Stable tag: 1.3

*Scan all of your theme files for potentially malicious or unwanted code.*

== Description ==
Scan all of your theme files for potentially malicious or unwanted code.

**NEW STUFF IN TAC 1.3** [CHANGELOG](http://builtbackwards.com/projects/tac/ "CHANGELOG")

* Compatible with WordPress 2.2 - 2.6.1
* **NEW!** Checks for embedded Static Links 
* **NEW!** Direct links for editing suspicious files in the WordPress Theme Editor 


**History**

TAC got its start when we repeatedly found obfuscated malicious code in free Wordpress themes available throughout the web. A quick way to scan a theme for undesirable code was needed, so we put together this plugin.

After Googling and exploring on our own we came upon the [article by Derek](http://5thirtyone.com/archives/870 "article by Derek") from 5thiryOne regarding this very subject. The deal is that many 3rd party websites are providing free Wordpress themes with encoded script slipped in - some even going as far as to claim that decoding the gibberish constitutes breaking copyright law. The encoded script may contain a variety of undesirable payloads, such as promoting third party sites or even hijack attempts.


**What TAC Does**

TAC stands for Theme Authenticity Checker. Currently, TAC searches the source files of every installed theme for signs of malicious code. If
such code is found, TAC displays the path to the theme file, the line
number, and a small snippet of the suspect code. As of **v1.3** *TAC* also searches for and displays static links.

Then what do you do? Just because the code is there doesn't mean it's not supposed to be or even qualifies as a threat, but most theme authors don't include code outside of the Wordpress scope and have no reason to obfuscate the code they make freely available to the web. We recommend contacting the theme author with the code that the script finds, as well as where you downloaded the theme. 
But the real value of this Plugin is that you can quickly determine what and where code needs to be cleaned up.

== Installation ==

After downloading and extracting the latest version of TAC:

1. Upload `tac.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Design -> TAC in the Wordpress Admin
4. The results of the scan will be displayed for each theme with the filename and line number of any threats.
5. You can click on the path to the theme file to edit in the WordPress Theme Editor

== Frequently Asked Questions ==

= What if I find something? =

Contact the theme's original author to double check if that section of code is supposed to be in the theme in the first place - chances are it shouldn't as there isn't a logical reason
have base64 encoding in a theme.

Static Links aren't necessarily bad, *TAC* just lists them so you can see where your theme is linking to.

If something is malicious or simply unwanted, *TAC* tells you what file to edit, you can even just click on the file path to be taken straight to the WordPress Theme Editor.

= What about future vulnerabilities? =

As we find them we will add them to *TAC*. If you find one, PLEASE let us know: [Contact builtBackwards](http://builtbackwards.com/contact/ "Contact builtBackwards")

== Screenshots ==

1. TAC Report Page

= Closing Thoughts =

Do your part by developing clean GPL compatible themes!

The builtBackwards Team