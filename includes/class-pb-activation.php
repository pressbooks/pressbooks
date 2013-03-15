<?php
/**
 * Contains all procedures which will be run on 'wpmu_new_blog' hook, register_activation_hook, and
 * register_deactivation_hook
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Activation {

	/**
	 * @var int Current blog id (defaults to 1, main blog)
	 */
	private $blog_id = 1;

	/**
	 * @var int Current user id (defaults to 1, admin)
	 */
	private $user_id = 1;

	/**
	 * @var array The set of default WP options to set up on activation
	 */
	private $opts = array(
		'template' => 'pressbooks-book',
		'stylesheet' => 'pressbooks-book',
		'current_theme' => 'pressbooks-book',
		'show_on_front' => 'page',
		'rewrite_rules' => ''
	);


	/**
	 * Constructor
	 */
	function __construct() {
	}


	/**
	 * Activation hook
	 *
	 * @see register_activation_hook()
	 */
	function registerActivationHook() {

		// Prevent overwriting customizations if PressBooks has been disabled
		if ( ! get_site_option( 'pressbooks-activated' ) ) {

			// Insert PressBooks description on root blog
			update_blog_option( 1, 'blogdescription', 'Simple Book Publishing' );

			// Configure theme and remove widgets from root blog
			update_blog_option( 1, 'template', 'pressbooks-publisher-one' );
			update_blog_option( 1, 'stylesheet', 'pressbooks-publisher-one' );
			delete_blog_option( 1, 'sidebars_widgets' );

			// Add "activated" key to enable check above
			add_site_option( 'pressbooks-activated', true );

		}
	}


	/**
	 * Runs activation function and sets up default WP options for new blog,
	 * a.k.a. when a registered user creates a new blog
	 *
	 * @param int $blog_id
	 * @param int $user_id
	 *
	 * @see add_action( 'wpmu_new_blog', ... )
	 */
	function wpmuNewBlog( $blog_id, $user_id ) {

		$this->blog_id = (int) $blog_id;
		$this->user_id = (int) $user_id;

		switch_to_blog( $this->blog_id );
		if ( ! $this->isBookSetup() ) {
			$this->wpmuActivate();
			array_walk( $this->opts, function ( $v, $k ) {
				if ( empty( $v ) ) delete_option( $k );
				else update_option( $k, $v );
			} );
		}
		restore_current_blog();

		if ( is_user_logged_in() )
			\PressBooks\Redirect\location( get_admin_url( $this->blog_id ) );

	}


	/**
	 * Determine if book is set up or not to avoid duplication
	 * (i.e. if activation functions have run and default options set)
	 *
	 * @return bool
	 */
	private function isBookSetup() {

		$act = get_option( 'pb_activated' );
		$pof = get_option( 'page_on_front' );
		$pop = get_option( 'page_for_posts' );
		if ( empty( $act ) ) return false;
		if ( ( get_option( 'template' ) != 'pressbooks-book' ) || ( get_option( 'stylesheet' ) != 'pressbooks-book' ) ) return false;
		if ( ( get_option( 'show_on_front' ) != 'page' ) || ( ( ! is_int( $pof ) ) || ( ! get_post( $pof ) ) ) || ( ( ! is_int( $pop ) ) || ( ! get_page( $pop ) ) ) ) return false;
		if ( ( count( get_all_category_ids() ) < 3 ) || ( wp_count_posts()->publish < 3 ) || ( wp_count_posts( 'page' )->publish < 3 ) ) return false;

		return true;
	}


	/**
	 * Set up default terms for Front Matter and Back Matter
	 * Insert default part, chapter, front matter, and back matter
	 * Insert default pages (Authors, Cover, TOC, About, Buy, and Access Denied)
	 * Anything which needs to run on blog activation must go in this function
	 */
	private function wpmuActivate() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		\PressBooks\Taxonomy\insert_terms();

		$posts = array(
			array(
				'post_title' => __( 'Main Body', 'pressbooks' ),
				'post_name' => __( 'main-body', 'pressbooks' ),
				'post_type' => 'part',
				'menu_order' => 1 ),
			array(
				'post_title' => __( 'Introduction', 'pressbooks' ),
				'post_name' => __( 'introduction', 'pressbooks' ),
				'post_content' => __( 'This is where you can write your introduction.', 'pressbooks' ),
				'post_type' => 'front-matter',
				'menu_order' => 1 ),
			array(
				'post_title' => __( 'Chapter 1', 'pressbooks' ),
				'post_name' => __( 'chapter-1', 'pressbooks' ),
				'post_content' => __( 'This is the first chapter in the main body of the text. You can change the text, rename the chapter, add new chapters, and add new parts.', 'pressbooks' ),
				'post_type' => 'chapter',
				'menu_order' => 1 ),
			array(
				'post_title' => __( 'Appendix', 'pressbooks' ),
				'post_name' => __( 'appendix', 'pressbooks' ),
				'post_content' => __( 'This is where you can add appendices or other back matter.', 'pressbooks' ),
				'post_type' => 'back-matter',
				'menu_order' => 1 ),
			array(
				'post_title' => __( 'Authors', 'pressbooks' ),
				'post_name' => __( 'authors', 'pressbooks' ),
				'post_type' => 'page' ),
			array(
				'post_title' => __( 'Cover', 'pressbooks' ),
				'post_name' => __( 'cover', 'pressbooks' ),
				'post_type' => 'page' ),
			array(
				'post_title' => __( 'Table of Contents', 'pressbooks' ),
				'post_name' => __( 'table-of-contents', 'pressbooks' ),
				'post_type' => 'page' ),
			array(
				'post_title' => __( 'About', 'pressbooks' ),
				'post_name' => __( 'about', 'pressbooks' ),
				'post_type' => 'page' ),
			array(
				'post_title' => __( 'Buy', 'pressbooks' ),
				'post_name' => __( 'buy', 'pressbooks' ),
				'post_type' => 'page' ),
			array(
				'post_title' => __( 'Access Denied', 'pressbooks' ),
				'post_name' => __( 'access-denied', 'pressbooks' ),
				'post_content' => __( 'This book is private, and accessible only to registered users. If you have an account you can login <a href="/wp-login.php">here</a>.  You can also set up your own PressBooks book at: <a href="http://pressbooks.com">PressBooks.com</a>.', 'pressbooks' ),
				'post_type' => 'page' ),
		);

		$post = array( 'post_status' => 'publish', 'comment_status' => 'open', 'post_author' => $this->user_id, );
		$page = array( 'post_status' => 'publish', 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_content' => '<!-- Here be dragons.-->', 'post_author' => $this->user_id, 'tags_input' => __( 'Default Data', 'pressbooks' ) );

		update_option( 'blogdescription', __( 'Simple Book Production', 'pressbooks' ) );

		$parent_part = 0;
		$intro = 0;
		$appendix = 0;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_name = %s AND post_status = 'publish' ";

		foreach ( $posts as $item ) {

			$exists = $wpdb->get_var( $wpdb->prepare( $query, array( $item['post_title'], $item['post_type'], $item['post_name'] ) ) );
			if ( empty( $exists ) ) {
				if ( $item['post_type'] == 'page' ) {
					$data = array_merge( $item, $page );
				} else {
					$data = array_merge( $item, $post );
				}

				$newpost = wp_insert_post( $data, true );
				if ( ! is_wp_error( $newpost ) ) {
					switch ( $item['post_name'] ) {
						case __( 'cover', 'pressbooks' ):
							$this->opts['page_on_front'] = (int) $newpost;
							break;
						case __( 'table-of-contents', 'pressbooks' ):
							$this->opts['page_for_posts'] = (int) $newpost;
							break;
					}

					if ( $item['post_type'] == 'part' ) {
						$parent_part = $newpost;
					} elseif ( $item['post_type'] == 'chapter' ) {
						$my_post = array();
						$my_post['ID'] = $newpost;
						$my_post['post_parent'] = $parent_part;
						wp_update_post( $my_post );
					} elseif ( $item['post_type'] == 'front-matter' ) {
						$intro = $newpost;
					} elseif ( $item['post_type'] == 'back-matter' ) {
						$appendix = $newpost;
					}
				} else {
					trigger_error( $newpost->get_error_message(), E_USER_ERROR );
				}
			}
		}

		// Apply 'introduction' front matter type to 'introduction' post
		wp_set_object_terms( $intro, 'introduction', 'front-matter-type' );
		// Apply 'appendix' front matter type to 'appendix' post
		wp_set_object_terms( $appendix, 'appendix', 'back-matter-type' );

		if ( ! wp_delete_comment( 1, true ) )
			return;

		$this->opts['pb_activated'] = time();
		refresh_blog_details( $this->blog_id );
	}

}
