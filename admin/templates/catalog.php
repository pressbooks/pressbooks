<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

$user_catalog_form_url = wp_nonce_url( get_bloginfo( 'url' ) . '/wp-admin/index.php?page=catalog', 'pb-user-catalog' );
$current_user_id = ! empty( $user_id ) ? $user_id : get_current_user_id();
$userblogs = get_blogs_of_user( $current_user_id );

$wip = new \PressBooks\Catalog();
$user_catalog = array_flip( $wip->getBookIds( $current_user_id ) );
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2><?php echo __( 'PressBooks Catalog', 'pressbooks' ); ?></h2>
	<?php echo '<p>' . __( 'Choose from the following books for inclusion in your catalog', 'pressbooks' ) . '.</p>'; ?>
	<form method="post" action="<?php echo $user_catalog_form_url; ?>">
		<input type="hidden" name="user_id" value="<?php echo $current_user_id; ?>" />
		<?php
		$books = array();
		foreach ( $userblogs as $book ) {
			if ( ! is_main_site( $book->userblog_id ) ) {
				$books[$book->blogname] = $book;
			}
		}
		ksort( $books );
		?>
		<table class="widefat fixed">
			<?php
			$rows = array();
			$num = count( $books );
			$cols = 1;
			if ( $num >= 20 )
				$cols = 4;
			elseif ( $num >= 10 )
				$cols = 2;
			$num_rows = ceil( $num / $cols );
			$split = 0;
			for ( $i = 1; $i <= $num_rows; $i ++ ) {
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
					echo "<h3><label><input type=\"checkbox\" name=\"pressbooks_user_catalog[{$book->userblog_id}]\" id=\"{$book->userblog_id}\" value=\"1\" " . checked( isset( $user_catalog[$book->userblog_id] ), true, false ) . "/> {$book->blogname}</label></h3>";
					echo "<p>" . apply_filters( 'myblogs_blog_actions', "<a href='" . esc_url( get_home_url( $book->userblog_id ) ) . "'>" . __( 'Visit' ) . "</a> | <a href='" . esc_url( get_admin_url( $book->userblog_id ) ) . "'>" . __( 'Dashboard' ) . "</a>", $book ) . "</p>";
					echo apply_filters( 'myblogs_options', '', $book );
					echo "</td>";
					$i ++;
				}
				echo "</tr>";
			}?>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
