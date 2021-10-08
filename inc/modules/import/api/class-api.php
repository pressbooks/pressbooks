<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\Api;

use Pressbooks\Cloner\Cloner;
use Pressbooks\Modules\Import\ImportGenerator;
use Pressbooks\Utility\PercentageYield;

class Api extends ImportGenerator {

	const TYPE_OF = 'api';

	/**
	 * @var Cloner
	 */
	protected $cloner;

	/**
	 *
	 * @param array $upload
	 *
	 * @return bool
	 */
	public function setCurrentImportOption( array $upload ) {

		if ( empty( $upload['url'] ) ) {
			return false;
		}
		$this->cloner = new Cloner( $upload['url'] );
		$this->cloner->isImporting = true;
		if ( ! $this->cloner->setupSource( false ) ) {
			return false;
		}

		$option = [
			'file' => $upload['file'],
			'url' => $upload['url'] ?? null,
			'file_type' => $upload['type'],
			'type_of' => self::TYPE_OF,
			'chapters' => [],
			'post_types' => [],
			'allow_parts' => true,
		];

		foreach ( $this->cloner->getSourceBookStructure()['front-matter'] as $frontmatter ) {
			if ( $this->isSourceCloneable( $frontmatter['id'], 'front-matter' ) ) {
				$option['chapters'][ $frontmatter['id'] ] = $frontmatter['title'];
				$option['post_types'][ $frontmatter['id'] ] = 'front-matter';
			}
		}

		foreach ( $this->cloner->getSourceBookStructure()['parts'] as $key => $part ) {
			if ( $this->isSourceCloneable( $part['id'], 'part' ) ) {
				$option['chapters'][ $part['id'] ] = $part['title'];
				$option['post_types'][ $part['id'] ] = 'part';
			}
			foreach ( $this->cloner->getSourceBookStructure()['parts'][ $key ]['chapters'] as $chapter ) {
				if ( $this->isSourceCloneable( $chapter['id'], 'chapter' ) ) {
					$option['chapters'][ $chapter['id'] ] = $chapter['title'];
					$option['post_types'][ $chapter['id'] ] = 'chapter';
				}
			}
		}

		foreach ( $this->cloner->getSourceBookStructure()['back-matter'] as $backmatter ) {
			if ( $this->isSourceCloneable( $backmatter['id'], 'back-matter' ) ) {
				$option['chapters'][ $backmatter['id'] ] = $backmatter['title'];
				$option['post_types'][ $backmatter['id'] ] = 'back-matter';
			}
		}

		foreach ( $this->cloner->getSourceBookGlossary() as $glossary ) {
			$option['chapters'][ $glossary['id'] ] = $glossary['title']['rendered'];
			$option['post_types'][ $glossary['id'] ] = 'glossary';
		}

		if ( empty( $option['chapters'] ) ) {
			$_SESSION['pb_errors'][] = sprintf( __( 'No chapters in %s are licensed for cloning.', 'pressbooks' ), sprintf( '<em>%s</em>', $this->cloner->getSourceBookMetadata()['name'] ) );
			return false;
		}

		return update_option( 'pressbooks_current_import', $option );
	}

	/**
	 *
	 * @param array $current_import
	 *
	 * @return bool
	 */
	public function import( array $current_import ) {
		try {
			foreach ( $this->importGenerator( $current_import ) as $percentage => $info ) {
				// Do nothing, this is a compatibility wrapper that makes the generator work like a regular function
			}
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param array $current_import
	 *
	 * @throws \Exception
	 * @return \Generator
	 */
	public function importGenerator( array $current_import ) : \Generator {
		yield 1 => __( 'Looking up the source book', 'pressbooks' );

		if ( empty( $current_import['url'] ) ) {
			throw new \Exception();
		}

		$post_status = $current_import['default_post_status'];

		$this->cloner = new Cloner( $current_import['url'] );
		$this->cloner->isImporting = true;
		if ( ! $this->cloner->setupSource( false ) ) {
			throw new \Exception();
		}

		// Pre-processor
		$this->cloner->clonePreProcess();

		// Front matter
		$fm_selected_for_import = [];
		foreach ( $this->cloner->getSourceBookStructure()['front-matter'] as $frontmatter ) {
			if ( $this->flaggedForImport( $frontmatter['id'] ) ) {
				$fm_selected_for_import[] = $frontmatter;
			}
		}
		$y = new PercentageYield( 40, 50, count( $fm_selected_for_import ) );
		foreach ( $fm_selected_for_import as $frontmatter ) {
			yield from $y->tick( __( 'Importing front matter', 'pressbooks' ) );
			$fm_id = $this->cloner->cloneFrontMatter( $frontmatter['id'] );
			$this->updatePost( $fm_id, $post_status );
		}
		unset( $fm_selected_for_import );

		// Parts & Chapters
		// Code is a bit funky here because user can import chapter with our without a part
		// Hard to count chapters before we know (or don't know) the $part_id
		$ch_ticks = 0;
		foreach ( $this->cloner->getSourceBookStructure()['parts'] as $key => $part ) {
			if ( $this->flaggedForImport( $part['id'] ) ) {
				$ch_ticks++;
			}
			foreach ( $this->cloner->getSourceBookStructure()['parts'][ $key ]['chapters'] as $chapter ) {
				if ( $this->flaggedForImport( $chapter['id'] ) ) {
					$ch_ticks++;
				}
			}
		}
		$y = new PercentageYield( 50, 80, $ch_ticks );
		$parent_id = $this->getChapterParent();
		foreach ( $this->cloner->getSourceBookStructure()['parts'] as $key => $part ) {
			$ch_emit_msg = __( 'Importing parts and chapters', 'pressbooks' );
			$part_id = false;
			if ( $this->flaggedForImport( $part['id'] ) ) {
				yield from $y->tick( $ch_emit_msg );
				$part_id = $this->cloner->clonePart( $part['id'] );
				$this->updatePost( $part_id, $post_status );
			}
			foreach ( $this->cloner->getSourceBookStructure()['parts'][ $key ]['chapters'] as $chapter ) {
				if ( $this->flaggedForImport( $chapter['id'] ) ) {
					yield from $y->tick( $ch_emit_msg );
					$ch_id = $this->cloner->cloneChapter( $chapter['id'], ( $part_id ? $part_id : $parent_id ) );
					$this->updatePost( $ch_id, $post_status );
				}
			}
		}

		// Back matter
		$bm_selected_for_import = [];
		foreach ( $this->cloner->getSourceBookStructure()['back-matter'] as $backmatter ) {
			if ( $this->flaggedForImport( $backmatter['id'] ) ) {
				$bm_selected_for_import[] = $backmatter;
			}
		}
		$y = new PercentageYield( 80, 90, count( $bm_selected_for_import ) );
		foreach ( $bm_selected_for_import as $backmatter ) {
			yield from $y->tick( __( 'Importing back matter', 'pressbooks' ) );
			$bm_id = $this->cloner->cloneBackMatter( $backmatter['id'] );
			$this->updatePost( $bm_id, $post_status );

		}
		unset( $bm_selected_for_import );

		// Glossary
		$gl_selected_for_import = [];
		foreach ( $this->cloner->getSourceBookGlossary() as $glossary ) {
			if ( $this->flaggedForImport( $glossary['id'] ) ) {
				$gl_selected_for_import[] = $glossary;
			}
		}
		$y = new PercentageYield( 90, 100, count( $gl_selected_for_import ) );
		foreach ( $gl_selected_for_import as $glossary ) {
			yield from $y->tick( __( 'Importing glossary terms', 'pressbooks' ) );
			$gl_id = $this->cloner->cloneGlossary( $glossary['id'] );
			$this->updatePost( $gl_id, $post_status );
		}
		unset( $gl_selected_for_import );

		// Post-processor
		$this->cloner->clonePostProcess();

		// Done
		return $this->revokeCurrentImport();
	}

	/**
	 * Is the section license OK?
	 * The global license is for the 'collection' and within that collection you have stuff with licenses that differ from the global one...
	 *
	 * @param int $section_id The ID of the section within the source book.
	 * @param string $post_type The post type of the section.
	 *
	 * @return bool
	 */
	protected function isSourceCloneable( $section_id, $post_type ) {
		$metadata = $this->cloner->retrieveSectionMetadata( $section_id, $post_type );
		$is_source_clonable = $this->cloner->isSourceCloneable( $metadata['license'] ?? $this->cloner->getSourceBookMetadata()['license'] );
		return $is_source_clonable;
	}

	/**
	 * Update post status
	 *
	 * @param int $post_id
	 * @param string $status
	 */
	protected function updatePost( $post_id, $status ) {
		// wp_update_post: arrays are expected to be escaped, objects are not.
		// We want to keep \latex backslashes and other weirdness, so use post objects!
		/** @var \WP_Post $post */
		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		global $wpdb;
		$menu_order = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d AND ID != %d ",
				$post->post_type,
				$post->post_parent,
				$post_id
			)
		);
		if ( $menu_order !== null ) {
			$post->menu_order = $menu_order + 1;
		}

		$post->post_status = $status;

		wp_update_post( $post );
	}
}
