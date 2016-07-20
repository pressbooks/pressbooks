<?php
/**
 * This class has two purposes:
 *  + Handle the registration and population of taxonomies.
 *  + Perform upgrades on individual books as Pressbooks evolves.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;

class Taxonomy {

	/**
	 * The value for option: pressbooks_taxonomy_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 1;

	function __construct() {}

	/**
	 * Create a custom taxonomy for Chapter, Front Matter and Back Matter post types
	 */
	static function registerTaxonomies() {

		/* Front Matter Type */

		$labels = array(
			'name' => _x( 'Front Matter Types', 'taxonomy general name' ),
			'singular_name' => _x( 'Front Matter Type', 'taxonomy singular name' ),
			'search_items' => __( 'Search Front Matter Types', 'pressbooks' ),
			'popular_items' => __( 'Popular Front Matter Types', 'pressbooks' ),
			'all_items' => __( 'All Front Matter Types', 'pressbooks' ),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __( 'Edit Front Matter Type', 'pressbooks' ),
			'update_item' => __( 'Update Front Matter Type', 'pressbooks' ),
			'add_new_item' => __( 'Add New Front Matter Type', 'pressbooks' ),
			'new_item_name' => __( 'New Front Matter Type Name', 'pressbooks' ),
			'separate_items_with_commas' => __( 'Separate front matter types with commas', 'pressbooks' ),
			'add_or_remove_items' => __( 'Add or remove front matter type', 'pressbooks' ),
			'choose_from_most_used' => __( 'Choose from the most used front matter type', 'pressbooks' ),
			'menu_name' => __( 'Front Matter Types', 'pressbooks' ),
		);

		// can only apply front matter taxonomy to front matter post type
		register_taxonomy(
			'front-matter-type',
			'front-matter',
			array(
				'hierarchical' => true,
				// only super-admins can change front matter terms
				'capabilities' => array( 'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts' ),
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'front-matter-type' ),
			)
		);

		/* Back Matter Type */

		$labels = array(
			'name' => _x( 'Back Matter Types', 'taxonomy general name' ),
			'singular_name' => _x( 'Back Matter Type', 'taxonomy singular name' ),
			'search_items' => __( 'Search Back Matter Types', 'pressbooks' ),
			'popular_items' => __( 'Popular Back Matter Types', 'pressbooks' ),
			'all_items' => __( 'All Back Matter Types', 'pressbooks' ),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __( 'Edit Back Matter Type', 'pressbooks' ),
			'update_item' => __( 'Update Back Matter Type', 'pressbooks' ),
			'add_new_item' => __( 'Add New Back Matter Type', 'pressbooks' ),
			'new_item_name' => __( 'New Back Matter Type Name', 'pressbooks' ),
			'separate_items_with_commas' => __( 'Separate back matter types with commas', 'pressbooks' ),
			'add_or_remove_items' => __( 'Add or remove back matter type', 'pressbooks' ),
			'choose_from_most_used' => __( 'Choose from the most used back matter type', 'pressbooks' ),
			'menu_name' => __( 'Back Matter Types', 'pressbooks' ),
		);

		// can only apply back matter taxonomy to back matter post type
		register_taxonomy(
			'back-matter-type',
			'back-matter',
			array(
				'hierarchical' => true,
				// only super-admins can change back matter terms
				'capabilities' => array( 'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts' ),
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'back-matter-type' ),
			)
		);

		/* Chapter Type */

		$labels = array(
			'name' => _x( 'Chapter Types', 'taxonomy general name' ),
			'singular_name' => _x( 'Chapter Type', 'taxonomy singular name' ),
			'search_items' => __( 'Search Chapter Types', 'pressbooks' ),
			'popular_items' => __( 'Popular Chapter Types', 'pressbooks' ),
			'all_items' => __( 'All Chapter Types', 'pressbooks' ),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __( 'Edit Chapter Type', 'pressbooks' ),
			'update_item' => __( 'Update Chapter Type', 'pressbooks' ),
			'add_new_item' => __( 'Add New Chapter Type', 'pressbooks' ),
			'new_item_name' => __( 'New Chapter Type Name', 'pressbooks' ),
			'separate_items_with_commas' => __( 'Separate chapter types with commas', 'pressbooks' ),
			'add_or_remove_items' => __( 'Add or remove chapter type', 'pressbooks' ),
			'choose_from_most_used' => __( 'Choose from the most used chapter type', 'pressbooks' ),
			'menu_name' => __( 'Chapter Types', 'pressbooks' ),
		);

		// can only apply chapter taxonomy to chapter post type
		register_taxonomy(
			'chapter-type',
			'chapter',
			array(
				'hierarchical' => true,
				// only super-admins can change chapter terms
				'capabilities' => array( 'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts' ),
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'chapter-type' ),
			)
		);
	}

	/**
	 * Insert Front Matter, Back Matter terms and Chapter Terms
	 */
	static function insertTerms() {

		// Front Matter
		wp_insert_term( 'Abstract', 'front-matter-type', array( 'slug' => 'abstracts' ) );
		wp_insert_term( 'Acknowledgements', 'front-matter-type', array( 'slug' => 'acknowledgements' ) );
		wp_insert_term( 'Before Title Page', 'front-matter-type', array( 'slug' => 'before-title' ) );
		wp_insert_term( 'Chronology, Timeline', 'front-matter-type', array( 'slug' => 'chronology-timeline' ) );
		wp_insert_term( 'Dedication', 'front-matter-type', array( 'slug' => 'dedication' ) );
		wp_insert_term( 'Disclaimer', 'front-matter-type', array( 'slug' => 'disclaimer' ) );
		wp_insert_term( 'Epigraph', 'front-matter-type', array( 'slug' => 'epigraph' ) );
		wp_insert_term( 'Foreword', 'front-matter-type', array( 'slug' => 'foreword' ) );
		wp_insert_term( 'Genealogy, Family Tree', 'front-matter-type', array( 'slug' => 'genealogy-family-tree' ) );
		wp_insert_term( 'Image credits', 'front-matter-type', array( 'slug' => 'image-credits' ) );
		wp_insert_term( 'Introduction', 'front-matter-type', array( 'slug' => 'introduction' ) );
		wp_insert_term( 'List of Abbreviations', 'front-matter-type', array( 'slug' => 'list-of-abbreviations' ) );
		wp_insert_term( 'List of Characters', 'front-matter-type', array( 'slug' => 'list-of-characters' ) );
		wp_insert_term( 'List of Illustrations', 'front-matter-type', array( 'slug' => 'list-of-illustrations' ) );
		wp_insert_term( 'List of Tables', 'front-matter-type', array( 'slug' => 'list-of-tables' ) );
		wp_insert_term( 'Miscellaneous', 'front-matter-type', array( 'slug' => 'miscellaneous' ) );
		wp_insert_term( 'Other Books by Author', 'front-matter-type', array( 'slug' => 'other-books' ) );
		wp_insert_term( 'Preface', 'front-matter-type', array( 'slug' => 'preface' ) );
		wp_insert_term( 'Prologue', 'front-matter-type', array( 'slug' => 'prologue' ) );
		wp_insert_term( 'Recommended citation', 'front-matter-type', array( 'slug' => 'recommended-citation' ) );
		wp_insert_term( 'Title Page', 'front-matter-type', array( 'slug' => 'title-page' ) );

		// Back Matter
		wp_insert_term( 'About the Author', 'back-matter-type', array( 'slug' => 'about-the-author' ) );
		wp_insert_term( 'About the Publisher', 'back-matter-type', array( 'slug' => 'about-the-publisher' ) );
		wp_insert_term( 'Acknowledgements', 'back-matter-type', array( 'slug' => 'acknowledgements' ) );
		wp_insert_term( 'Afterword', 'back-matter-type', array( 'slug' => 'afterword' ) );
		wp_insert_term( 'Appendix', 'back-matter-type', array( 'slug' => 'appendix' ) );
		wp_insert_term( "Author's Note", 'back-matter-type', array( 'slug' => 'authors-note' ) );
		wp_insert_term( 'Back of Book Ad', 'back-matter-type', array( 'slug' => 'back-of-book-ad' ) );
		wp_insert_term( 'Bibliography', 'back-matter-type', array( 'slug' => 'bibliography' ) );
		wp_insert_term( 'Biographical Note', 'back-matter-type', array( 'slug' => 'biographical-note' ) );
		wp_insert_term( 'Colophon', 'back-matter-type', array( 'slug' => 'colophon' ) );
		wp_insert_term( 'Conclusion', 'back-matter-type', array( 'slug' => 'conclusion' ) );
		wp_insert_term( 'Credits', 'back-matter-type', array( 'slug' => 'credits' ) );
		wp_insert_term( 'Dedication', 'back-matter-type', array( 'slug' => 'dedication' ) );
		wp_insert_term( 'Epilogue', 'back-matter-type', array( 'slug' => 'epilogue' ) );
		wp_insert_term( 'Glossary', 'back-matter-type', array( 'slug' => 'glossary' ) );
		wp_insert_term( 'Index', 'back-matter-type', array( 'slug' => 'index' ) );
		wp_insert_term( 'Miscellaneous', 'back-matter-type', array( 'slug' => 'miscellaneous' ) );
		wp_insert_term( 'Notes', 'back-matter-type', array( 'slug' => 'notes' ) );
		wp_insert_term( 'Other Books by Author', 'back-matter-type', array( 'slug' => 'other-books' ) );
		wp_insert_term( 'Permissions', 'back-matter-type', array( 'slug' => 'permissions' ) );
		wp_insert_term( 'Reading Group Guide', 'back-matter-type', array( 'slug' => 'reading-group-guide' ) );
		wp_insert_term( 'Resources', 'back-matter-type', array( 'slug', 'resources' ) );
		wp_insert_term( 'Sources', 'back-matter-type', array( 'slug' => 'sources' ) );
		wp_insert_term( 'Suggested Reading', 'back-matter-type', array( 'slug' => 'suggested-reading' ) );

		// Chapter
		wp_insert_term( 'Standard', 'chapter-type', array( 'slug' => 'standard' ) );
		wp_insert_term( 'Numberless', 'chapter-type', array( 'slug' => 'numberless' ) );
	}

	/**
	 * Return the first (and only) front-matter-type for a specific post
	 *
	 * @param $id
	 *
	 * @return string
	 */
	static function getFrontMatterType( $id ) {

		$terms = get_the_terms( $id, 'front-matter-type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				return $term->slug;
				break;
			}
		}

		return 'miscellaneous';
	}

	/**
	 * Return the first (and only) back-matter-type for a specific post
	 *
	 * @param $id
	 *
	 * @return string
	 */
	static function getBackMatterType( $id ) {

		$terms = get_the_terms( $id, 'back-matter-type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				return $term->slug;
				break;
			}
		}

		return 'miscellaneous';
	}

	/**
	 * Return the first (and only) chapter-type for a specific post
	 *
	 * @param $id
	 *
	 * @return string
	 */
	static function getChapterType( $id ) {

		$terms = get_the_terms( $id, 'chapter-type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term->slug == 'type-1' ) {
					return 'standard';
				} else {
					return $term->slug;
				}
				break;
			}
		}

		return 'standard';
	}

	// ----------------------------------------------------------------------------------------------------------------
	// Upgrades
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * Upgrade metadata.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {

		if ( $version < 1 ) {
			// Upgrade from version 0 (prior to Pressbooks\Taxonomy class) to version 1 (simplified chapter types)
			$this->upgradeChapterTypes();
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Upgrade Chapter Types.
	 */
	function upgradeChapterTypes() {
		$type_1 = get_term_by( 'slug', 'type-1', 'chapter-type' );
		$type_2 = get_term_by( 'slug', 'type-2', 'chapter-type' );
		$type_3 = get_term_by( 'slug', 'type-3', 'chapter-type' );
		$type_4 = get_term_by( 'slug', 'type-4', 'chapter-type' );
		$type_5 = get_term_by( 'slug', 'type-5', 'chapter-type' );

		if ( $type_1 )
			wp_update_term($type_1->term_id, 'chapter-type', array( 'name' => 'Standard', 'slug' => 'standard' ));

		if ( $type_2 )
			wp_delete_term($type_2->term_id, 'chapter-type');

		if ( $type_3 )
			wp_delete_term($type_3->term_id, 'chapter-type');

		if ( $type_4 )
			wp_delete_term($type_4->term_id, 'chapter-type');

		if ( $type_5 )
			wp_delete_term($type_5->term_id, 'chapter-type');
	}
}
