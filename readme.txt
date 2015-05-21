=== BP Expire Category ===
Contributors: densey
Donate link: http://example.com/
Tags: categories, category, expire
Requires at least: 4.0
Tested up to: 4.2.1
Stable tag: /trunk/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Expire Category allows users to add a category and expiration date to any Post.  

== Description ==

Expire Category allows users to add a category and expiration date to any Post.  Once the date is reached, the category is removed from the Post.  The Post remains intact, as does the category itself, however they are no longer associated.  

== Installation ==

From your WordPress dashboard

1. Visit 'Plugins->Add New'
2. Search for 'BP Expire Category'
3. Activate BP Expire Category from your Plugins page

From WordPress.org

1. Download BP Expire Category
2. Extract the folder 'bp-expire-category' to your desktop
3. Upload the folder 'bp-expire-category' to your '/wp-content/plugins' directory, using your favorite method (ftp, sftp, scp, etc...)
4. Activate BP Expire Category from your Plugins page. 

Once Activated
Go to a edit Post page.  Look for a box on the right called 'Expire Category'.  From the select box, choose a category and then choose a date and time with the datepicker. 

The category will expire when the expiration date is equal to or older than the current date.  The expiration function runs when your WordPress installation is accessed.  If you don't get many visits, or want to ensure that your scripts are running, you will need to set up a cron job.  Check out [this article](http://beyond-paper.com/why-didnt-my-wordpress-scheduled-post-appear/"Why Didn't my Post Appear" )for more information.  

== Frequently Asked Questions ==

= When does the category expire? =

The category will expire the first time your WordPress site is accessed on (or after) the expiration date.

= I just checked and my post is still appearing, even though it was supposed to expire =

The expiration function runs when your WordPress installation is accessed.  If you don't get many visits, or want to ensure that your scripts are running, you will need to set up a cron job.  Check out [this article](http://beyond-paper.com/why-didnt-my-wordpress-scheduled-post-appear/"Why Didn't my Post Appear" )for more information. 

== Screenshots ==

1. The metabox that appears on the edit Post page.

== Changelog ==

= 0.2 =
* Updated Readme.txt.

= 0.1 =
* Initial release.

== Upgrade Notice ==
= =