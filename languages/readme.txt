------------------------------------------------------------------------------
About languages
------------------------------------------------------------------------------

Put load_plugin_textdomain() compatible language files here.

Domain: pressbooks

We also support overriding core WordPress strings. See "core-en_US.php" for example syntax.

Files should be named like:

* pressbooks-zh_TW.mo
* pressbooks-zh_TW.po
* core-zh_TW.php

@see pressbooks/includes/pb-l10n.php

------------------------------------------------------------------------------
Creating new PO and MO files for PressBooks
--------------------------------------------------------------------------

Follow the steps described here:
http://codex.wordpress.org/I18n_for_WordPress_Developers#Generating_a_POT_file

Quick and nerdy HOWTO, search and replace accordingly:

$ mkdir tmp
$ cd tmp
$ svn co http://i18n.svn.wordpress.org/tools/trunk/
$ cd trunk
$ php makepot.php wp-plugin /path/to/pressbooks
$ mv pressbooks.pot /path/to/pressbooks/languages/pressbooks-es_ES.po

... Translate pressbooks-es_ES.po ...

$ cd /path/to/pressbooks/languages/
$ msgfmt -o pressbooks-es_ES.mo pressbooks-es_ES.po

Finally, add your new language to \PressBooks\Admin\Metaboxes\add_user_meta() in admin/pb-admin-metaboxes.php

------------------------------------------------------------------------------
Installing the rest of a language in PressBooks (Ie. WordPress)
------------------------------------------------------------------------------

In your existing PressBooks install, create the 'wordpress/wp-content/languages/' directory.

Download a translated WordPress from:
http://codex.wordpress.org/WordPress_in_Your_Language

Copy 'wordpress/wp-content/languages/*' from the downloaded file into your own 'wordpress/wp-content/languages/' folder.
