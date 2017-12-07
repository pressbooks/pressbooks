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
	const VERSION = 1;

	/**
	 * @var Taxonomy
	 */
	private static $instance = null;

	/**
	 * @var Licensing
	 */
	private $licensing;

	/**
	 * @return Taxonomy
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$licensing = new Licensing();
			self::$instance = new self( $licensing );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Taxonomy $obj
	 */
	static public function hooks( Taxonomy $obj ) {
		add_action( 'init', [ $obj, 'registerTaxonomies' ] );
		add_action( 'init', [ $obj, 'maybeUpgrade' ], 1000 );
	}

	/**
	 * @param Licensing $licensing
	 */
	public function __construct( $licensing ) {
		$this->licensing = $licensing;
	}

	/**
	 * Create a custom taxonomy for Chapter, Front Matter and Back Matter post types
	 */
	public function registerTaxonomies() {

		register_extended_taxonomy(
			'front-matter-type',
			'front-matter',
			[
				'meta_box' => 'dropdown',
				'capabilities' => [
					'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts',
				],
				'show_in_rest' => true,
			]
		);

		register_extended_taxonomy(
			'back-matter-type',
			'back-matter',
			[
				'meta_box' => 'dropdown',
				'capabilities' => [
					'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts',
				],
				'show_in_rest' => true,
			]
		);

		register_extended_taxonomy(
			'chapter-type',
			'chapter',
			[
				'meta_box' => 'dropdown',
				'capabilities' => [
					'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts',
				],
				'show_in_rest' => true,
			]
		);

		register_extended_taxonomy(
			'contributor',
			[ 'metadata', 'chapter', 'part', 'front-matter', 'back-matter' ],
			[
				'meta_box' => false,
				'hierarchical' => false,
				'capabilities' => [
					'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts',
				],
				'show_in_rest' => true,
			]
		);

		register_extended_taxonomy(
			'license',
			[ 'metadata', 'chapter', 'part', 'front-matter', 'back-matter' ],
			[
				'meta_box' => false,
				'hierarchical' => false,
				'capabilities' => [
					'manage_terms' => 'manage_sites',
					'edit_terms' => 'manage_sites',
					'delete_terms' => 'manage_sites',
					'assign_terms' => 'edit_posts',
				],
				'show_in_rest' => true,
			]
		);

	}

	/**
	 * Insert Front Matter, Back Matter terms and Chapter Terms
	 */
	public function insertTerms() {

		if ( ! taxonomy_exists( 'front-matter-type' ) ) {
			$this->registerTaxonomies();
		}

		// Front Matter
		wp_insert_term(
			'Abstract', 'front-matter-type', [
				'slug' => 'abstracts',
			]
		);
		wp_insert_term(
			'Acknowledgements', 'front-matter-type', [
				'slug' => 'acknowledgements',
			]
		);
		wp_insert_term(
			'Before Title Page', 'front-matter-type', [
				'slug' => 'before-title',
			]
		);
		wp_insert_term(
			'Chronology, Timeline', 'front-matter-type', [
				'slug' => 'chronology-timeline',
			]
		);
		wp_insert_term(
			'Dedication', 'front-matter-type', [
				'slug' => 'dedication',
			]
		);
		wp_insert_term(
			'Disclaimer', 'front-matter-type', [
				'slug' => 'disclaimer',
			]
		);
		wp_insert_term(
			'Epigraph', 'front-matter-type', [
				'slug' => 'epigraph',
			]
		);
		wp_insert_term(
			'Foreword', 'front-matter-type', [
				'slug' => 'foreword',
			]
		);
		wp_insert_term(
			'Genealogy, Family Tree', 'front-matter-type', [
				'slug' => 'genealogy-family-tree',
			]
		);
		wp_insert_term(
			'Image credits', 'front-matter-type', [
				'slug' => 'image-credits',
			]
		);
		wp_insert_term(
			'Introduction', 'front-matter-type', [
				'slug' => 'introduction',
			]
		);
		wp_insert_term(
			'List of Abbreviations', 'front-matter-type', [
				'slug' => 'list-of-abbreviations',
			]
		);
		wp_insert_term(
			'List of Characters', 'front-matter-type', [
				'slug' => 'list-of-characters',
			]
		);
		wp_insert_term(
			'List of Illustrations', 'front-matter-type', [
				'slug' => 'list-of-illustrations',
			]
		);
		wp_insert_term(
			'List of Tables', 'front-matter-type', [
				'slug' => 'list-of-tables',
			]
		);
		wp_insert_term(
			'Miscellaneous', 'front-matter-type', [
				'slug' => 'miscellaneous',
			]
		);
		wp_insert_term(
			'Other Books by Author', 'front-matter-type', [
				'slug' => 'other-books',
			]
		);
		wp_insert_term(
			'Preface', 'front-matter-type', [
				'slug' => 'preface',
			]
		);
		wp_insert_term(
			'Prologue', 'front-matter-type', [
				'slug' => 'prologue',
			]
		);
		wp_insert_term(
			'Recommended citation', 'front-matter-type', [
				'slug' => 'recommended-citation',
			]
		);
		wp_insert_term(
			'Title Page', 'front-matter-type', [
				'slug' => 'title-page',
			]
		);

		// Back Matter
		wp_insert_term(
			'About the Author', 'back-matter-type', [
				'slug' => 'about-the-author',
			]
		);
		wp_insert_term(
			'About the Publisher', 'back-matter-type', [
				'slug' => 'about-the-publisher',
			]
		);
		wp_insert_term(
			'Acknowledgements', 'back-matter-type', [
				'slug' => 'acknowledgements',
			]
		);
		wp_insert_term(
			'Afterword', 'back-matter-type', [
				'slug' => 'afterword',
			]
		);
		wp_insert_term(
			'Appendix', 'back-matter-type', [
				'slug' => 'appendix',
			]
		);
		wp_insert_term(
			"Author's Note", 'back-matter-type', [
				'slug' => 'authors-note',
			]
		);
		wp_insert_term(
			'Back of Book Ad', 'back-matter-type', [
				'slug' => 'back-of-book-ad',
			]
		);
		wp_insert_term(
			'Bibliography', 'back-matter-type', [
				'slug' => 'bibliography',
			]
		);
		wp_insert_term(
			'Biographical Note', 'back-matter-type', [
				'slug' => 'biographical-note',
			]
		);
		wp_insert_term(
			'Colophon', 'back-matter-type', [
				'slug' => 'colophon',
			]
		);
		wp_insert_term(
			'Conclusion', 'back-matter-type', [
				'slug' => 'conclusion',
			]
		);
		wp_insert_term(
			'Credits', 'back-matter-type', [
				'slug' => 'credits',
			]
		);
		wp_insert_term(
			'Dedication', 'back-matter-type', [
				'slug' => 'dedication',
			]
		);
		wp_insert_term(
			'Epilogue', 'back-matter-type', [
				'slug' => 'epilogue',
			]
		);
		wp_insert_term(
			'Glossary', 'back-matter-type', [
				'slug' => 'glossary',
			]
		);
		wp_insert_term(
			'Index', 'back-matter-type', [
				'slug' => 'index',
			]
		);
		wp_insert_term(
			'Miscellaneous', 'back-matter-type', [
				'slug' => 'miscellaneous',
			]
		);
		wp_insert_term(
			'Notes', 'back-matter-type', [
				'slug' => 'notes',
			]
		);
		wp_insert_term(
			'Other Books by Author', 'back-matter-type', [
				'slug' => 'other-books',
			]
		);
		wp_insert_term(
			'Permissions', 'back-matter-type', [
				'slug' => 'permissions',
			]
		);
		wp_insert_term(
			'Reading Group Guide', 'back-matter-type', [
				'slug' => 'reading-group-guide',
			]
		);
		wp_insert_term(
			'Resources', 'back-matter-type', [
				'slug' => 'resources',
			]
		);
		wp_insert_term(
			'Sources', 'back-matter-type', [
				'slug' => 'sources',
			]
		);
		wp_insert_term(
			'Suggested Reading', 'back-matter-type', [
				'slug' => 'suggested-reading',
			]
		);

		// Chapter
		wp_insert_term(
			'Standard', 'chapter-type', [
				'slug' => 'standard',
			]
		);
		wp_insert_term(
			'Numberless', 'chapter-type', [
				'slug' => 'numberless',
			]
		);

		foreach ( $this->licensing->getSupportedTypes( true ) as $key => $val ) {
			wp_insert_term(
				$val['desc'], 'license', [
					'slug' => $key,
				]
			);
		}
	}

	/**
	 * Return the first (and only) front-matter-type for a specific post
	 *
	 * @param $id
	 *
	 * @return string
	 */
	public function getFrontMatterType( $id ) {

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
	public function getBackMatterType( $id ) {

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
	public function getChapterType( $id ) {

		$terms = get_the_terms( $id, 'chapter-type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( 'type-1' === $term->slug ) {
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
	 * Is it time to upgrade?
	 */
	public function maybeUpgrade() {

		// TODO:
		// Once upon a time we were updating 'pressbooks_taxonomy_version' with Metadata::VERSION instead of Taxonomy::VERSION
		// Some books might be in a weird state (bug?)

		$taxonomy_version = get_option( 'pressbooks_taxonomy_version', 0 );
		if ( $taxonomy_version < self::VERSION ) {
			$this->upgrade( $taxonomy_version );
			update_option( 'pressbooks_taxonomy_version', self::VERSION );
		}
	}

	/**
	 * Upgrade
	 *
	 * @param int $version
	 */
	public function upgrade( $version ) {

		if ( $version < 1 ) {
			// Upgrade from version 0 (prior to Pressbooks\Taxonomy class) to version 1 (simplified chapter types)
			$this->upgradeChapterTypes();
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Upgrade Chapter Types.
	 */
	protected function upgradeChapterTypes() {
		$type_1 = get_term_by( 'slug', 'type-1', 'chapter-type' );
		$type_2 = get_term_by( 'slug', 'type-2', 'chapter-type' );
		$type_3 = get_term_by( 'slug', 'type-3', 'chapter-type' );
		$type_4 = get_term_by( 'slug', 'type-4', 'chapter-type' );
		$type_5 = get_term_by( 'slug', 'type-5', 'chapter-type' );

		if ( $type_1 ) {
			wp_update_term(
				$type_1->term_id, 'chapter-type', [
					'name' => 'Standard',
					'slug' => 'standard',
				]
			);
		}

		if ( $type_2 ) {
			wp_delete_term( $type_2->term_id, 'chapter-type' );
		}

		if ( $type_3 ) {
			wp_delete_term( $type_3->term_id, 'chapter-type' );
		}

		if ( $type_4 ) {
			wp_delete_term( $type_4->term_id, 'chapter-type' );
		}

		if ( $type_5 ) {
			wp_delete_term( $type_5->term_id, 'chapter-type' );
		}
	}
}
