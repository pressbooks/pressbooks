<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use function Pressbooks\Admin\Laf\book_info_slug;
use function Pressbooks\Image\thumbnail_from_url;
use Illuminate\Support\Str;
use Pressbooks\Container;
use Pressbooks\Metadata;

class BookDashboard extends Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $page_name = 'book_dashboard';

	/**
	 * @throws ContainerExceptionInterface
	 * @throws Throwable
	 * @throws NotFoundExceptionInterface
	 */
	public function render(): void {
		$blade = Container::get( 'Blade' );

		$current_user = wp_get_current_user();

		$permissions = $this->getUserPermissions();

		echo $blade->render( 'admin.dashboard.book', [
			'is_current_user_subscriber' => count( $current_user->roles ) === 1 && $current_user->roles[0] === 'subscriber',
			'site_name' => get_bloginfo( 'name' ),
			'book_cover' => $this->getBookCover(),
			'book_url' => get_home_url(),
			'book_info_url' => $permissions['edit_post'] ? book_info_slug() : false,
			'organize_url' => $permissions['edit_posts'] ? admin_url( 'admin.php?page=pb_organize' ) : false,
			'themes_url' => $permissions['switch_themes'] ? admin_url( 'themes.php' ) : false,
			'users_url' => $permissions['list_users'] ? admin_url( 'users.php' ) : false,
			'analytics_url' => $permissions['view_koko_analytics'] ? admin_url( 'index.php?page=koko-analytics' ) : false,
			'delete_book_url' => $permissions['delete_site'] ? admin_url( 'ms-delete-site.php' ) : false,
			'write_chapter_url' => $permissions['edit_posts'] ? admin_url( 'post-new.php?post_type=chapter' ) : false,
			'import_content_url' => $permissions['edit_posts'] ? admin_url( 'admin.php?page=pb_import' ) : false,
		] );
	}

	protected function shouldRedirect(): bool {
		$screen = get_current_screen();

		return $screen->base === 'dashboard';
	}

	/**
	 * Get user permissions according his capabilities and super admin status.
	 *
	 * @return array
	 */
	protected function getUserPermissions(): array {
		$is_super_admin = is_super_admin();
		$post_meta_id = ( new Metadata() )->getMetaPostId();

		return [
			'edit_post' => $is_super_admin || current_user_can( 'edit_post', $post_meta_id ),
			'edit_posts' => $is_super_admin || current_user_can( 'edit_posts' ),
			'switch_themes' => $is_super_admin || current_user_can( 'switch_themes' ),
			'list_users' => $is_super_admin || current_user_can( 'list_users' ),
			'view_koko_analytics' => is_plugin_active( 'koko-analytics/koko-analytics.php' ) && current_user_can( 'view_koko_analytics' ),
			'delete_site' => $is_super_admin || current_user_can( 'delete_site' ),
		];
	}

	/**
	 * Get the current book cover.
	 *
	 * @return string
	 */
	protected function getBookCover(): string {
		$cover_image = get_post_meta(
			( new Metadata )->getMetaPostId(), 'pb_cover_image', true
		);

		$cover_image = Str::of( $cover_image );

		if ( $cover_image->endsWith( 'default-book-cover.jpg' ) ) {
			return $cover_image->replace( 'default-book-cover.jpg', 'default-book-cover-225x0@2x.jpg' );
		}

		return thumbnail_from_url( $cover_image, 'pb_cover_medium' );
	}
}
