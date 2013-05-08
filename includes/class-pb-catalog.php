<?php
/**
 * Contains functions for creating and managing a user's PressBooks Catalog.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks;

class Catalog {

	/**
	 * Adds catalog administration menu page.
	 */
	function addCatalogPage() {
		add_submenu_page( 'index.php', 'My Catalog', 'My Catalog', 'read', 'catalog', __NAMESPACE__ .'\Catalog::displayCatalogPage' );
	}
	
	/**
	 * Displays catalog administration menu page.
	 */
	function displayCatalogPage() {
		$user_catalog_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/index.php?page=catalog', 'user_catalog' );
		//print_r($_POST['user_catalog']);
		Catalog::user_catalog_save( get_current_user_id() ); ?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php echo __( 'PressBooks Catalog', 'pressbooks' ); ?></h2>
			<?php if ( $_POST) {
				$nonce = $_REQUEST['_wpnonce'];
				if ( !wp_verify_nonce( $nonce, 'user_catalog' ) ) { ?>
			<div id="message" class="error below-h2"><p><?php echo __( 'Nonce verification failed.', 'pressbooks' ); ?></p></div>
				<?php } else { ?>
			<div id="message" class="updated below-h2"><p><?php echo __( 'Catalog saved.', 'pressbooks' ); ?></p></div>
				<?php }
			} ?>
			<?php echo '<p>' . __( 'Choose from the following books for inclusion in your catalog', 'pressbooks' ) . '.</p>'; ?>
			<form method="post" action="<?php echo $user_catalog_form_url; ?>">
				<?php $user_catalog = get_user_meta( get_current_user_id(), 'user_catalog', true ); ?>
				<?php $userblogs = get_blogs_of_user( get_current_user_id() ); 
				$books = array(); 
				foreach ($userblogs as $book) {
					if ( !is_main_site( $book->userblog_id ) ) {
						$books[$book->blogname] = $book;
					}
				}
				ksort($books);
				foreach ($books as $book) {
					if ( ! isset( $user_catalog[$book->userblog_id] ) ) { $user_catalog[$book->userblog_id] = 0; }
				}
				?>
				<table class="widefat fixed">
				<?php $num = count( $books );
				$cols = 1;
				if ( $num >= 20 )
					$cols = 4;
				elseif ( $num >= 10 )
					$cols = 2;
				$num_rows = ceil( $num / $cols );
				$split = 0;
				for ( $i = 1; $i <= $num_rows; $i++ ) {
					$rows[] = array_slice( $books, $split, $cols );
					$split = $split + $cols;
				}
			
				$c = '';
				foreach ( $rows as $row ) {
					$c = $c == 'alternate' ? '' : 'alternate';
					echo "<tr class='$c'>";
					$i = 0;
					foreach ( $row as $book ) {
						$s = $i == 3 ? '' : 'border-right: 1px solid #ccc;';
						echo "<td valign='top' style='$s'>";
						echo "<h3><label><input type=\"checkbox\" name=\"user_catalog[{$book->userblog_id}]\" id=\"{$book->userblog_id}\" value=\"1\" " . checked(1, $user_catalog[$book->userblog_id], false) . "/> {$book->blogname}</label></h3>";
						echo "<p>" . apply_filters( 'myblogs_blog_actions', "<a href='" . esc_url( get_home_url( $book->userblog_id ) ). "'>" . __( 'Visit' ) . "</a> | <a href='" . esc_url( get_admin_url( $book->userblog_id ) ) . "'>" . __( 'Dashboard' ) . "</a>", $book ) . "</p>";
						echo apply_filters( 'myblogs_options', '', $book );
						echo "</td>";
						$i++;
					}
					echo "</tr>";
				}?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
	
	<?php }
	
	/**
	 * Saves user catalog.
	 *
	 * @param $user_id
	 */
	function user_catalog_save( $user_id ) {
		if ( $_POST) {
			$nonce = $_REQUEST['_wpnonce'];
			if ( !wp_verify_nonce( $nonce, 'user_catalog' ) ) { return false; }
			if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
			if ( isset( $_POST['user_catalog'] ) ) {
				update_user_meta( $user_id, 'user_catalog', $_POST['user_catalog'] );  
		    } else {
			    delete_user_meta( $user_id, 'user_catalog' );
		    }
		}
	}


}
