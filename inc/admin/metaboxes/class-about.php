<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use Pressbooks\Admin\Fields\Wysiwyg;
use Pressbooks\Admin\Fields\TextArea;
use Pressbooks\Admin\Fields\Text;

class About extends Metabox
{
    public function __construct(bool $expanded = false)
    {
        parent::__construct($expanded);

        $this->slug = 'about-the-book';
        $this->title = __('About the Book', 'pressbooks');
    }

    public function getFields(): array
    {
        return [
			new Text(
				name: 'pb_about_140',
				label: __( 'Book Tagline', 'pressbooks' ),
				description: __( 'A very short description of your book. It should fit in a Twitter post, and encapsulate your book in the briefest sentence.', 'pressbooks' )
			),
			new TextArea(
				name: 'pb_about_50',
				label: __( 'Short Description', 'pressbooks' ),
				description: __( 'A short paragraph about your book, for catalogs, reviewers etc. to quote.', 'pressbooks' )
			),
			new Wysiwyg(
				name: 'pb_about_unlimited',
				label: __( 'Long Description', 'pressbooks' ),
				description: __( 'The full description of your book.', 'pressbooks' ),
				rows: 4
			)
		];
    }
}
