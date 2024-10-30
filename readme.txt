=== Link Summarizer ===
Contributors: GrafZahl, rigacha
Donate link: http://mac.partofus.org/macpress/?p=40#donate
Tags: post, links
Requires at least: 2.0
Tested up to: 4.4.2
Stable tag: 1.8

Link Summarizer appends a list of the links referred to in a post at the end of a post automatically.
You can also configure the output position when calling the plugin in your template files at the desired location. 

== Description ==

With Link Summmarizer you can produce a list of all the links you referred to in a posting. 
You can exclude links from the summary by specifying regular expressions for links that shouldn't
show up.
You can enable/disable the link summary globally through the Options page of Link Summarizer, but you also
can enable/disable the display of the summary for specific post by specifying a custom flag named
"lnsum_show" (without quotes) and setting a value of 0 (disable) or 1 (enable).
You also can choose the regex engine to use (the standard php one or the PCRE extension)

== Installation ==
1. Upload the plugin to your plugin directory (wp-content/plugins)
2. Activate the plugin through the 'Plugins' menu in WordPress

Optional: If you have special design concepts in mind you can get a summary directly using the following code in 
your template files at the place you want it to appear:

<?php if (function_exists('get_link_summary')) { get_link_summary(); } ?>

== Uninstallation ==
For uninstallation deactivate the plungin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Can I have the admin page in my native language? =

Since 1.7 Link Summarizer uses Wordpress' localization features. To use it,
set WPLANG ind wp-config.php to your locale, e.g. for german use
define('WPLANG', 'de_DE')

At the moment Link Summarizer is only available in english (default) and
german. If you want your native language to be included, please help to
translate. It's really simple, please contact me using the plugin homepage.

= I want to enable/disable the display of the summary on a single post
differtently from the default setting. What can I do? =

You can use a custom variable called "lnsum_show" (without parantheses). You
can set it to "1" to enable the summary, or "0" to disable it.

You can also use this feature to control the display of the link summary on
the index page and in RSS feeds. There are additional options available to
enable the use of the pre post settings in these areas. Check out the admin
panel of Link Summarizer for more information.

= I have other plugins that interfere with Link Summarizer. What can I do? =

Since 1.6.2 it is possible to adjust the priority of the plugin execution. By
default Link Summarizer uses a default priority of 20 (in opposite to
Wordpress default of 10). As most other plugins use a priority of 10 Link
Summarizer is executed after the other plugins. This is especially needed if
you use MarkDown plugins, which replace HTML shorthands with correct HTML
tags.

But if you e.g. use plugins like Twitter Tool or Sociable and don't want to
filter out these links using regular expressions, simply set the priority of
Link Summarizer to a value lower than 10 and give it a try. But keep in mind
that if you also use plugins which need Link Summarizer to be run afterwards,
you can't use this option and must filter out via regular expressions.

= Is it possible to use CSS to style the summarizer? =

Yes, since version 1.4.1 it is possible. I set the surrounding div tag to use the css class
"link-summarizer". For details see the included file style.css.sample.

If you use then template function, the enclosing div uses the css class
"link-summarizer-loop".

The contents of the surrounding div tag can be configured completely through
the admin interface independently for the manually used template function and
the automatic variant.

To use the CSS styling you have to include appropriate CSS code in your theme
stylesheet.

= Is it possible to decide if the URL or the link text get shown in the summary? =

Yes, this is also a new feature included in 1.4.1. In the Admin panel there's an option for it.

= I have special design needs. =

Starting with version 1.5 the design of the output the pluging produces is completely configurable in the admin interface

== Screenshots ==
1. Link Summarizer in action

== Contributors ==
* Thomas Ohms
