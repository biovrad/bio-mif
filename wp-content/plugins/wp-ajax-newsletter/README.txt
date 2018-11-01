---=[ Index ]=------------------------------------------------------------------
1 - General Information
2 - License
3 - Installation
4 - Configuration


---=[ 1 - General Information ]=------------------------------------------------

Version: 1.0
Date: 10/04/2006
Author: Tiago Pocinho, Siemens Networks, S.A.

This plugin allows users to subscribe and receive a newsletter containing the blog latest posts.

---=[ 2- License ]=-------------------------------------------------------------

This plugin is protected under the GNU General Public License.

---=[ 3 - Installation ]=-------------------------------------------------------

After downloading this plugin, extract the directory "wp-ajax-newsletter".

Go to the wordpress back-office and activate the plugin. This will create the needed tables in the database.

To add the subscription form in your template, insert the following code where you want the form to appear:


<?php if (class_exists('ajaxNewsletter')): ?>
<!-- place your HTML code here -->
<?php ajaxNewsletter::newsletterForm(); ?>
<!-- place your HTML code here -->
<?php endif; ?>

You can use the following CSS classes to style the form in the front-office:

#ajaxNewsletter, .newsletterContainer {
	/* newsletter container */
}

#newsletterFormDiv {
	/* newsletter form elements*/
}

.newsletterTextInput {
	/* email text input */
}

#newsletterLoading {
	/* the loading message while the subscription is beeing handled */
}

.success {
	/* the success message container */
}


.error {
	/* the error message container */
}


---=[ 4 - Configuration ]=-------------------------------------------------------

To configure this plugin access "Newsletter" under the "Options" tab.

The following tags can be used in the template for the newsletter:
{TITLE} - The post title
{URL} - The post URL
{DATE} - The date when the post was published (uses wordpress defined format)
{TIME} - The time when the post was published (uses wordpress defined format)
{AUTHOR} - The post author
{EXCERPT} - The post excerpt. If none is available, it will be generated based on the content.
{CONTENT} - The post content.