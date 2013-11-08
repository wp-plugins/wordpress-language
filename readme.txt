=== WordPress Language ===
Contributors: brucepearson, AmirHelzer
Donate link: http://wpml.org/documentation/related-projects/wordpress-language/
Tags: localization, translation, i18n, language, gettext, mo, po, international
License: GPLv2
Requires at least: 3.2
Tested up to: 3.7.1
Stable tag: 1.1.1

Run localized WordPress sites easily. Select the language and everything else happens automatically.

== Description ==

WordPress Language plugin lets you easily run localized WordPress sites. It will change the WordPress language, without having to install translation files or edit PHP.

* Adds a language switcher to the WordPress admin bar
* Automatically downloads and installs the correct .mo file
* Let's you choose country-variants
* Provides one-click update when new translations are available

[vimeo http://vimeo.com/48982621]

= HOW IT WORKS =

WordPress Language uses an online index of current translation files for WordPress. That index includes entries for each WordPress version, language and country variant.

When you switch language, the plugin checks for the best translation file. Then, it downloads the translations, scans them and stores the translations in the database.

You don't need to install any .mo files or edit your wp-config.php to switch languages.

= NEED A MULTILINGUAL SITE? =

When you need to run multilingual sites, you're welcome to try [WPML](http://wpml.org). You'll be able to run several languages on the same site from one database.

Running a multilingual site is a lot easier and more efficient than having several WordPress sites - one for each language.

= FEATURE SUGGESTIONS and QUESTIONS =
WordPress Language is evolving. To suggest new features or ask a quesion, visit [WordPress Language plugin page](http://wpml.org/documentation/related-projects/wordpress-language/).

== Installation ==

1. Upload 'wordpress-language' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Frequently Asked Questions ==

= Which languages are supported? =

WordPress Language lists all the languages that appear in the WordPress Translation project. If your language is not listed, it just doesn't have official WordPress translation.

= Where do the translations come from? =

WordPress Language downloads the official translations from the [WordPress localization project](http://translate.wordpress.org/projects/wp/). These are the same translations used to create the localized WordPress downloads.

= Can I edit the translations myself? =

No. WordPress Language only downloads the existing translations from the WordPress Translation project.

= What happens when WordPress gets a new version? =

The plugin detects new changes and will offer you to update the translation. Of course, that depends on the translation actually being available.

= Can I use this to build multilingual sites? =

No. WordPress Language sets one single language for your site. For multilingual sites, have a look at [WPML](http://wpml.org).

== Screenshots ==

1. Choose the admin language

== Changelog ==

= 1.1.1 =
* Allows to select different Chinese variants

= 1.1.0 =
* Updated the translation sources, to come directly from the WordPress translation project
* Added an option to set different languages for the admin and public pages
* Improved the language menu to include more options and easier usage

= 1.0.1 =
* Text changes

= 1.0.0 =
* First release

== Upgrade Notice ==

= 1.1.1 =
* You can now choose locales for Chinese variants