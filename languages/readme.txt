------------------------------------------------------------------------------
About languages
------------------------------------------------------------------------------

Put load_plugin_textdomain() compatible language files here.

Domain: pressbooks

We also support overriding core WordPress strings. See 'core-en_US.php' for example syntax.

Files should be named like:

* pressbooks-zh_TW.mo
* pressbooks-zh_TW.po
* core-zh_TW.php

@see pressbooks/includes/pb-l10n.php

------------------------------------------------------------------------------
Creating new translations for Pressbooks
--------------------------------------------------------------------------

We use Transifex to manage translations of Pressbooks. If you would like to submit a translation in your language, join a team at:
https://www.transifex.com/pressbooks/pressbooks/

We update the .pot file and incorporate new translations with every release of the Pressbooks plugin. Please be patient if your translations do not appear immediately.

For core WordPress strings, create a new file (i.e. 'core-pt_BR.php' and submit a pull request via GitHub:
http://github.com/pressbooks/pressbooks/

------------------------------------------------------------------------------
Installing the rest of a language in Pressbooks (i.e. WordPress)
------------------------------------------------------------------------------

In your existing Pressbooks install, create the 'wordpress/wp-content/languages/' directory.

Download a translated WordPress from:
https://make.wordpress.org/polyglots/teams/

Copy 'wordpress/wp-content/languages/*' from the downloaded file into your own 'wordpress/wp-content/languages/' folder.

Alternatively, use wp-cli:

$ wp core language install es_ES