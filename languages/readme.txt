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
Creating new PO and MO files
--------------------------------------------------------------------------

Follow the steps described here:
http://codex.wordpress.org/I18n_for_WordPress_Developers#Generating_a_POT_file

Add your new language to \PressBooks\Admin\Users\add_user_meta() in admin/pb-admin-users.php

------------------------------------------------------------------------------
Installing the rest of a language in PressBooks
------------------------------------------------------------------------------

In your existing PressBooks install, create the 'wordpress/wp-content/languages/' directory.

Download a translated WordPress from:
http://codex.wordpress.org/WordPress_in_Your_Language

Copy 'wordpress/wp-content/languages/*' from the downloaded file into your own 'wordpress/wp-content/languages/' folder.

