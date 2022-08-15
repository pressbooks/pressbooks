<?php

// @see http://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata

namespace PHPSTORM_META {

    override(\Pressbooks\Container::get(0),
        map([
	        'PBlade' => \Jenssegers\Blade\Blade::class,
	        'GlobalTypography' => \Pressbooks\GlobalTypography::class,
	        'Sass' => \Pressbooks\Sass::class,
	        'Styles' => \Pressbooks\Styles::class,
        ]));
    //basicaly the same as get(0), just for array["arg"] lookups
    override(new \Pressbooks\Container,
        map([
	        'PBlade' => \Jenssegers\Blade\Blade::class,
	        'GlobalTypography' => \Pressbooks\GlobalTypography::class,
	        'Sass' => \Pressbooks\Sass::class,
	        'Styles' => \Pressbooks\Styles::class,
        ]));

    override(\Illuminate\Container\Container::make(0),
        map([
	        'PBlade' => \Jenssegers\Blade\Blade::class,
	        'GlobalTypography' => \Pressbooks\GlobalTypography::class,
	        'Sass' => \Pressbooks\Sass::class,
	        'Styles' => \Pressbooks\Styles::class,
        ]));
    //basicaly the same as make(0), just for array["arg"] lookups
    override(new \Illuminate\Container\Container,
        map([
	        'PBlade' => \Jenssegers\Blade\Blade::class,
	        'GlobalTypography' => \Pressbooks\GlobalTypography::class,
	        'Sass' => \Pressbooks\Sass::class,
	        'Styles' => \Pressbooks\Styles::class,
        ]));

}
