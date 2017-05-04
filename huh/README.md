# Who?
_huh_ was dreamt up  by [secret pizza party](https://secretpizza.party) and brought to life by the amazing [Dan Hauk](https://danhauk.com/). Seriously, Dan is the best. He took a broad idea and really brought it to life like only he could. The SPP offices are open on Christmas Day but we close on May 5th (Dan's birthday) as it's a company wide holiday. 

# What?
_huh_ is the best way to offer in dashboard documentation for all your WordPress projects. The content is generated from a markdown file which makes it super quick & easy to update your documentation whenever you want. You can learn more from the launch post [here](https://secretpizza.party/huh-making-documentation-easier/).

# Where?
We think _huh_ is awesome and we really want you to use it in all your projects. It's totally free/open source and you can find it on [github](https://github.com/secretpizzaparty/huh/).

## Wanna Contribute? 
If you found a bug, [report it here](https://github.com/secretpizzaparty/huh/issues/new). If you're a developer, we welcome pull requests of all types!

### Development Workflow
1. Make sure you have `git`, `node`, and `npm` installed and a working WordPress installation.
2. Clone this repository inside your theme directory.

	```
	$ git clone https://github.com/secretpizzaparty/huh.git
	$ cd huh
	```

3. Watch the front-end CSS/Sass for changes and rebuild accordingly with [Grunt](https://github.com/gruntjs/grunt). Please only modify the Sass files to keep the CSS consistent and clean.

	```
	$ npm install
	$ grunt watch
	```

4. Open `/wp-admin/` in your browser.
5. Have fun!


# Why?
[secret pizza party](https://secretpizza.party) is in the process of developing a bunch of new WordPress themes and while they are quite simple there is still a need for a wee bit of documentation. External documentation is dumb and everything should be contained in the dashboard. We created _huh_ to make that happen.

# How?
Adding _huh_ to your theme is incredibly easy.

## Formatting your markdown
_huh_ pulls all of your `<h1>` tags to use as a table of contents. Each section of your documentation will be contained between these `<h1>` tags. For example:

```
# First section
The content of the first section of your documentation would go here. You can include links, bullets, images, anything!

# Second section
This would be the next section.

## You can even use subheadings
It will all be formatted correctly, but only the first-level headings will show on the table of contents.
```

## Adding _huh_ to your theme
Once you have your documentation formatted correctly, adding _huh_ to your theme is simple.

Just download the zipped plugin and extract it to your theme directory. At the bottom of your theme's `functions.php` file add the following lines:

``` php
require get_stylesheet_directory() . '/huh/huh.php';
function secretpizzaparty_huh() {
	// Enter the URL of your markdown file below
	$markdown_url = 'https://raw.githubusercontent.com/secretpizzaparty/huh/master/README.md';
	$huh = new WP_Huh();
	$huh->init( $markdown_url );
}
add_action( 'admin_init', 'secretpizzaparty_huh' );
```

Make sure you change the URL of the `$markdown_url` variable to point to your markdown file. It's that easy!
