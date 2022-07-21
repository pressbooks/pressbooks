<?php if ( ! defined( 'ABSPATH' ) ) { die();
} ?>
<div class="wrap">
	<h1><?php _e( 'Results', 'pressbooks' ) ?></h1>
	<?php if ( (is_countable($results) ? count( $results ) : 0) > 0 ) : ?>
	  <p><?php printf( __( '%1$s result(s) found.', 'pressbooks' ), is_countable($results) ? count( $results ) : 0 ); ?></p>

		<ol class="results">
		<?php foreach ( $results as $pos => $result ) : ?>
			<li<?php if ( $result->replace ) : ?> class="diff"<?php endif; ?>>
				<h3 class="title"><?php $search->show( $result ); ?></h3>
				<div class="options"><?php echo implode( ' | ', $search->getOptions( $result ) ); ?></div>
				<div class="content original"><?php echo $result->search ?></div>
				<?php if ( $result->replace ) : ?>
				<div class="content replacement"><?php echo $result->replace ?></div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ol>
	<?php else : ?>
	<p><?php _e( 'There are no results.', 'pressbooks' ) ?></p>
	<?php endif; ?>
</div>
