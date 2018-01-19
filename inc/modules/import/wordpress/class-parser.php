<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import\WordPress;

/**
 * This class forked from, inspired by \WXR_Parser_SimpleXML
 *
 * @see http://plugins.svn.wordpress.org/wordpress-importer/trunk/parsers.php
 */
class Parser {

	/**
	 * @param string $file
	 *
	 * @return array
	 * @throws \Exception
	 */
	function parse( $file ) {

		// ------------------------------------------------------------------------------------------------------------
		// Setup & sanity check
		// ------------------------------------------------------------------------------------------------------------

		$authors = [];
		$posts = [];
		$categories = [];
		$tags = [];
		$terms = [];

		libxml_use_internal_errors( true );

		$old_value = libxml_disable_entity_loader( true );
		$dom = new \DOMDocument;
		$dom->recover = true; // Try to parse non-well formed documents
		$success = $dom->loadXML( \Pressbooks\Utility\get_contents( $file ) );
		foreach ( $dom->childNodes as $child ) {
			if ( XML_DOCUMENT_TYPE_NODE === $child->nodeType ) {
				// Invalid XML: Detected use of disallowed DOCTYPE
				$success = false;
				break;
			}
		}
		libxml_disable_entity_loader( $old_value );

		// @codingStandardsIgnoreStart
		if ( ! $success || isset( $dom->doctype ) ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}

		$xml = @simplexml_import_dom( $dom );
		unset( $dom );

		// halt if loading produces an error
		if ( ! $xml ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}
		// @codingStandardsIgnoreEnd

		$wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' );
		if ( ! $wxr_version ) {
			throw new \Exception( __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'pressbooks' ) );
		}

		$wxr_version = (string) trim( $wxr_version[0] );
		// confirm that we are dealing with the correct file format
		if ( ! preg_match( '/^\d+\.\d+$/', $wxr_version ) ) {
			throw new \Exception( __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'pressbooks' ) );
		}

		// ------------------------------------------------------------------------------------------------------------
		// Ladies and gentlemen, start your parsing
		// ------------------------------------------------------------------------------------------------------------

		$base_url = $xml->xpath( '/rss/channel/wp:base_site_url' );
		$base_url = (string) trim( $base_url[0] );

		$namespaces = $xml->getDocNamespaces();
		if ( ! isset( $namespaces['wp'] ) ) {
			$namespaces['wp'] = 'http://wordpress.org/export/1.1/';
		}
		if ( ! isset( $namespaces['excerpt'] ) ) {
			$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
		}

		// grab authors
		foreach ( $xml->xpath( '/rss/channel/wp:author' ) as $author_arr ) {
			$a = $author_arr->children( $namespaces['wp'] );
			$login = (string) $a->author_login;
			$authors[ $login ] = [
				'author_id' => (int) $a->author_id,
				'author_login' => $login,
				'author_email' => (string) $a->author_email,
				'author_display_name' => (string) $a->author_display_name,
				'author_first_name' => (string) $a->author_first_name,
				'author_last_name' => (string) $a->author_last_name,
			];
		}

		// grab cats, tags and terms
		foreach ( $xml->xpath( '/rss/channel/wp:category' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$category = [
				'term_id' => (int) $t->term_id,
				'category_nicename' => (string) $t->category_nicename,
				'category_parent' => (string) $t->category_parent,
				'cat_name' => (string) $t->cat_name,
				'category_description' => (string) $t->category_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$category['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$categories[] = $category;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:tag' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$tag = [
				'term_id' => (int) $t->term_id,
				'tag_slug' => (string) $t->tag_slug,
				'tag_name' => (string) $t->tag_name,
				'tag_description' => (string) $t->tag_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$tag['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$tags[] = $tag;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:term' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$term = [
				'term_id' => (int) $t->term_id,
				'term_taxonomy' => (string) $t->term_taxonomy,
				'slug' => (string) $t->term_slug,
				'term_parent' => (string) $t->term_parent,
				'term_name' => (string) $t->term_name,
				'term_description' => (string) $t->term_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$term['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$terms[] = $term;
		}

		// grab posts
		foreach ( $xml->channel->item as $item ) {
			$post = [
				'post_title' => (string) $item->title,
				'guid' => (string) $item->guid,
			];

			$dc = $item->children( 'http://purl.org/dc/elements/1.1/' );
			$post['post_author'] = (string) $dc->creator;

			$content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
			$excerpt = $item->children( $namespaces['excerpt'] );
			$post['post_content'] = (string) $content->encoded;
			$post['post_excerpt'] = (string) $excerpt->encoded;

			$wp = $item->children( $namespaces['wp'] );
			$post['post_id'] = (int) $wp->post_id;
			$post['post_date'] = (string) $wp->post_date;
			$post['post_date_gmt'] = (string) $wp->post_date_gmt;
			$post['comment_status'] = (string) $wp->comment_status;
			$post['ping_status'] = (string) $wp->ping_status;
			$post['post_name'] = (string) $wp->post_name;
			$post['status'] = (string) $wp->status;
			$post['post_parent'] = (int) $wp->post_parent;
			$post['menu_order'] = (int) $wp->menu_order;
			$post['post_type'] = (string) $wp->post_type;
			$post['post_password'] = (string) $wp->post_password;
			$post['is_sticky'] = (int) $wp->is_sticky;

			if ( isset( $wp->attachment_url ) ) {
				$post['attachment_url'] = (string) $wp->attachment_url;
			}

			foreach ( $item->category as $c ) {
				$att = $c->attributes();
				if ( isset( $att['nicename'] ) ) {
					$post['terms'][] = [
						'name' => (string) $c,
						'slug' => (string) $att['nicename'],
						'domain' => (string) $att['domain'],
					];
				}
			}

			foreach ( $wp->postmeta as $meta ) {
				$post['postmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			foreach ( $wp->comment as $comment ) {
				$meta = [];
				if ( isset( $comment->commentmeta ) ) {
					foreach ( $comment->commentmeta as $m ) {
						$meta[] = [
							'key' => (string) $m->meta_key,
							'value' => (string) $m->meta_value,
						];
					}
				}

				$post['comments'][] = [
					'comment_id' => (int) $comment->comment_id,
					'comment_author' => (string) $comment->comment_author,
					'comment_author_email' => (string) $comment->comment_author_email,
					'comment_author_IP' => (string) $comment->comment_author_IP,
					'comment_author_url' => (string) $comment->comment_author_url,
					'comment_date' => (string) $comment->comment_date,
					'comment_date_gmt' => (string) $comment->comment_date_gmt,
					'comment_content' => (string) $comment->comment_content,
					'comment_approved' => (string) $comment->comment_approved,
					'comment_type' => (string) $comment->comment_type,
					'comment_parent' => (string) $comment->comment_parent,
					'comment_user_id' => (int) $comment->comment_user_id,
					'commentmeta' => $meta,
				];
			}

			$posts[] = $post;
		}

		return [
			'authors' => $authors,
			'posts' => $posts,
			'categories' => $categories,
			'tags' => $tags,
			'terms' => $terms,
			'base_url' => $base_url,
			'version' => $wxr_version,
		];
	}

}
