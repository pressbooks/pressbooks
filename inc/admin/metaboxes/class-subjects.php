<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Metaboxes;

use function Pressbooks\Metadata\get_thema_subjects;
use Pressbooks\Admin\Fields\Select;

class Subjects extends Metabox {

	public function getSlug(): string {
		return 'subjects';
	}

	public function getTitle(): string {
		return __( 'Subject(s)', 'pressbooks' );
	}

	public function getFields(): array {
		return [
			new Select(
				name: 'pb_primary_subject',
				label: __( 'Primary Subject', 'pressbooks' ),
				description: sprintf( __( '%1$s subject terms appear on the web homepage of your book and help categorize your book in your network catalog and Pressbooks Directory (if applicable). Use %2$s to determine which subject category is best for your book.', 'pressbooks' ), sprintf( '<a href="%1$s"><em>%2$s</em></a>', 'https://www.editeur.org/151/Thema', __( 'Thema', 'pressbooks' ) ), sprintf( '<a href="%1$s">%2$s</a>', 'https://ns.editeur.org/thema/en', __( 'the Thema subject category list', 'pressbooks' ) ) ),
				options: $this->getSubjects(),
			),
			new Select(
				name: 'pb_additional_subjects',
				label: __( 'Additional Subject(s)', 'pressbooks' ),
				description: sprintf( __( '%1$s subject terms appear on the web homepage of your book and help categorize your book in your network catalog and Pressbooks Directory (if applicable). Use %2$s to determine which subject category is best for your book.', 'pressbooks' ), sprintf( '<a href="%1$s"><em>%2$s</em></a>', 'https://www.editeur.org/151/Thema', __( 'Thema', 'pressbooks' ) ), sprintf( '<a href="%1$s">%2$s</a>', 'https://ns.editeur.org/thema/en', __( 'the Thema subject category list', 'pressbooks' ) ) ),
				options: $this->getSubjects(),
				multiple: true,
			),
		];
	}

	public function getSubjects(): array {
		$data = [];

		foreach ( get_thema_subjects() as $subject_group ) {
			$group = $subject_group['label'];
			$children = [];
			foreach ( $subject_group['children'] as $key => $value ) {
				$children[ $key ] = $value;
			}

			if ( ! empty( $children ) ) {
				$data[ $group ] = $children;
			}
		}

		return $data;
	}
}
