<?php

namespace Pressbooks\Admin\Dashboard;

use Illuminate\Support\Collection;

class Invitations {
	public static function getPendingInvitations(): Collection {
		global $wpdb;

		$user_id = get_current_user_id();
		$current_blog_id = get_current_blog_id();

		$invitations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d",
				'new_user_%',
				$user_id
			)
		);

		return collect( $invitations )
			->map(function( $invitation ) use ( $current_blog_id ) {
				$metadata = maybe_unserialize( $invitation->meta_value );

				switch_to_blog( $metadata['blog_id'] );

				$article = preg_match( '/^[aeiou]/i', $metadata['role'] )
					? __( 'an', 'pressbooks' )
					: __( 'a', 'pressbooks' );

				$url = home_url();
				$title = get_site_meta( $metadata['blog_id'], 'pb_title', true );
				$accept_link = home_url( "/newbloguser/{$metadata['key']}" );

				switch_to_blog( $current_blog_id );

				return [
					'accept_link' => $accept_link,
					'role' => "{$article} {$metadata['role']}",
					'book_url' => sprintf( '<a href="%1$s">%2$s</a>', $url, $title ),
				];
			});
	}
}
