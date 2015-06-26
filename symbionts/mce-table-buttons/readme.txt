=== MCE Table Buttons ===
Contributors: jakemgold, 10up, thinkoomph
Donate link: http://10up.com/plugins-modules/wordpress-mce-table-buttons/
Tags: tables, table, editor, WYSIWYG, buttons, tinymce
Requires at least: 3.4
Tested up to: 4.0
Stable tag: 3.2

Adds table editing controls to the visual content editor (TinyMCE).

== Description ==

Adds table editing controls to the visual content editor (TinyMCE).

A light weight plug-in that adds the table editing controls from the full version of TinyMCE, optimized for WordPress. Note that this may not work in conjunction with other plug-ins that significantly alter or replace the visual editor's default behavior.

Note that the table controls are contained in the “kitchen sink” toolbar, toggled with the last button on the first row of controls.

== Installation ==

1. Install easily with the WordPress plugin control panel or manually download the plugin and upload the folder 
`mce-table-buttons` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Show the toolbar in the editor by opening the "kitchen sink" (the last button in the first row)

== Screenshots ==

1. The editor with the new table editing controls in WordPress 3.9 or newer.
1. The editor with the new table editing controls in WordPress 3.4 through 3.8.

== Changelog ==

= 3.2 =
* WordPress 4.0 support, including a much newer TinyMCE plugin, with many new features, including background color and horizontal alignment
* Dramatically improved support for paragraphed content inside of a cell (paragraph breaks no longer disappear on save)

= 3.1 =
* Updated core TinyMCE table plugin from 4.0.20 to 4.0.21 in sync with WordPress - mostly bug and accessibility fixes
* Refactored for compatibility with plugins like Advanced Custom Fields that do not use the_editor hook

= 3.0 =
* Support for WordPress 3.9 and newer, which includes a major visual editor upgrade (TinyMCE 4)

= 2.0 =
* New button icons that better conform with WordPress's editor design
* Retina (HiDPI) ready button icons!
* Upgraded to latest version of TinyMCE tables plugin (fixes a lot of edge case bugs)
* Rewrote code for hiding / display toolbar with kitchen sink (now a TinyMCE plug-in instead of a workaround) - the table buttons no longer briefly appear before page loading is finished

= 1.5 =
* Table toolbar is hidden or displayed along with the kitchen sink (yay!)
* Minor clean up to code base and files; optimized for WordPress 3.3

= 1.0.4 =
* Updated TinyMCE table plug-in to corresponding TinyMCE update in WordPress 3.1 (still supports <3.1 too)

= 1.0.3.1 =
* Updated support / developer information

= 1.0.3 =
* Code clean up, 3.1 testing

= 1.0.2 =
* Fixed editor save warning appearing for unchanged content when the table is at the bottom of the editor
* Minor code clean up

= 1.0.1 =
* Fixed issue with WebKit browsers (Safari and Chrome) - TinyMCE bug

== Upgrade Notice ==

= 1.5 =
REQUIRES WordPress 3.3 or higher. Finally links table buttons row to kitchen sink!