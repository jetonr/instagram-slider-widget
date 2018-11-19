=== Instagram Slider Widget ===
Contributors: jetonr
Tags: instagram, slider, widget, images
Donate link: http://bit.ly/2EseW2p
Requires at least: 3.5
Tested up to: 4.9.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Instagram Slider Widget is a responsive slider widget that shows 12 latest images from a public instagram user or a hashtag.

== Description ==
= Instagram Slider Widget is a responsive slider widget that shows 12 latest images from a public instagram user or a hashtag. =


= Features =
* Images from Instagram are imported as WordPress attachments
* Display Images in Slider or Thumbnails
* No API Key Needed
* Link images to user profile, image URL, attachment URL, custom URL or none
* Sort images Randomly, Popularity, Date
* For more info visit http://instagram.jrwebstudio.com

= Where can I get support =
I will try to respond to all on plugin support forum but users showing back-link on their website will be more privileged!


= If you like this plugin. Rate it and Donate =

== Installation ==

= Installation =
1. Upload `instagram-slider-widget` to the `/wp-content/plugins/` directory
2. Activate the plugin through the \'Plugins\' menu in WordPress
3. Go to Appearance > Widgets and drag \'Instagram Slider Widget\' to your sidebar
4. Update the settings in the widget: Instagram Username, Images Layout, Number of Images to show, Check for new images hours 

= Requirements =
* PHP 5.2.0 or later
* Wordpress 3.5 or later
* WordPress Cron must be enabled

== Screenshots ==
1. Frontend Widget Slider
2. Frontend Widget Thumbs
3. Backend Configuration

== Changelog ==
= 1.4.3 =
* Fix for instagram api change

= 1.4.2 =
* Minor fix for instagram json change

= 1.4.1 =
* Fixed Instagram update that stoped hashtags from working
* Fixed hellip that showed when using wp trim words
* Modified the function that saved images localy to only save as attachments

= 1.4.0 =
* Fixed the issue where duplicate images were being inserted into Media Library
* Added a button in widget to remove previously created duplicate images
* Simplified the options to save images into media library
* Added an option to show backlink to help plugin development

= 1.3.3 =
* Fixed notification error message.

= 1.3.2 =
* Fixed deeplink issue with smartphones. Contributors via wordpress forum @ricksportel
* Added option to block users when searching for hashtag. Sponsored by VirtualStrides.com
* Modified sizes to show square croped and original sizes
* Added new wordpress size only for instagram plugin - regenerating thumbnails might be required.
* Added option to stop Pinterest pinning on images

= 1.3.1 =
* Fixed issue when no images were shown due to instagram recent changes.
* Caption fix when no caption in image
* set wait time to 3 min for php because of larger images
* updated flexislider to latest version

= 1.3.0 =
* Added Option to search for hashtags
* Added Limit for number of words to appear in caption
* Fixed 500 server error that accured when loading 15+ images
* Fixed css for some themes
 
= 1.2.3 =
* Added Links for Instagram Hashtags
* Updtated flexislider to 2.5.0
* Added Slide Speed in miliseconds
* Brought back Image Size for images loaded directly from Instagram
* Changed CSS for thumbnails Template
* Added Thumbnails Without borders template

= 1.2.2 =
* Modified the code to work with new Instagram Page
* Removed Image Size option when loading images directly from Instagram
* 24 Images can now be displayed
* Fixed multiple widget bug using widget ids in class names
* Added better explanation for sources

= 1.2.1 =
* Bug fixes
* Shortcode for widgets
* Option not to insert images into media library

= 1.2.0 =
* Full Rewritte of the plugin

= 1.1.3 =
* bug fix not working after wordpresss update
* Added multisite support
* Javascript for slider is enqueued at the top of the page

= 1.1.2 =
* minor bug fix
* Added Optional Slider Caption Overlay template

= 1.1.1 =
* The text and control for slider visible on mouse over
* Reorganised Slider html format
* Css Styling for slider

= 1.1.0 =
* Added Option to link images to a Custom URL
* Added Option to link images to localy saved instagram images
* Fixed flexislider namespace causing problems in sites using flexislider
* Rename css classes to match new flexislider namespace

= 1.0.4 =
* Added Option to insert images into media library
* Fixed error caused by missing json_last_error() function ( php older than 5.3 only )

= 1.0.3 =
* Added Option to link images to User Profile or Image Url 
* Code Cleanup

= 1.0.2 =
* Compatibility for php older than 5.3  
* Stlying fix for thumbnail layout
* Added Option to Randomise Images 

= 1.0.1 =
* Removed preg_match 
* Using exact array index 
* Bug Fixes

= 1.0 =
* First Realease