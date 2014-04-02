<?php
/**
 * Copyright 2014 Brad Payne. GPL v2, of course.
 * 
 * This plugin is forked from the original WP Latex v1.8 http://wordpress.org/plugins/wp-latex/ (c) Sidney Markowitz, Automattic, Inc.
 * It modifies the plugin to work with PressBooks, strips unwanted features, adds others — activated at the network level
 *
 */

if ( !defined('ABSPATH') ) exit;

class PBLatexAdmin extends PBLatex {
	var $errors;

	function init() {
		parent::init();
		$this->errors = new WP_Error;
		// since we're activating at the network level, this needs to be called in the constructor
		$this->addOptions();

		add_action( 'admin_menu', array( &$this, 'adminMenu' ) );
	}

	function adminMenu() {
		$hook = add_options_page( 'PB LaTeX', 'PB LaTeX', 'manage_options', 'pb-latex', array( &$this, 'adminPage' ) );
		add_action( "load-$hook", array( &$this, 'adminPageLoad' ) );


		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __FILE__ ) . '/pb-latex.php' ), array( &$this, 'pluginActionLinks' ) );
	}

	function pluginActionLinks( $links ) {
		array_unshift( $links, '<a href="options-general.php?page=pb-latex">' . __( 'Settings' ) . "</a>" );
		return $links;
	}

	function adminPageLoad() {
		if ( ! current_user_can( 'manage_options' ) )
				wp_die( __( 'Insufficient LaTeX-fu', 'pb-latex' ) );

		add_action( 'admin_head', array( &$this, 'adminHead' ) );

		if ( empty( $_POST['pb_latex'] ) ) {
			if ( $this->options['wrapper'] && ( false !== strpos( $this->options['wrapper'], '%BG_COLOR_RGB%' ) || false !== strpos( $this->options['wrapper'], '%FG_COLOR_RGB%' ) ) )
					$this->errors->add( 'wrapper', __( 'PB LaTeX no longer supports ><code>%BG_COLOR_RGB%</code> or <code>%FG_COLOR_RGB</code> in the LaTeX preamble.  Please remove them.' ), $this->options['wrapper'] );
			return;
		}

		check_admin_referer( 'pb-latex' );

		if ( $this->update( stripslashes_deep( $_POST['pb_latex'] ) ) ) {
			wp_safe_redirect( add_query_arg( 'updated', '', wp_get_referer() ) );
			exit;
		}
	}

	function update( $new ) {
		if ( ! is_array( $this->options ) ) $this->options = array();
		extract( $this->options, EXTR_SKIP );

		if ( isset( $new['method'] ) ) {
			if ( empty( $this->methods[$new['method']] ) ) {
				$this->errors->add( 'method', __( 'Invalid LaTeX generation method', 'pb-latex' ), $new['method'] );
			} else {
				$method = $new['method'];
			}
		}

		if ( isset( $new['fg'] ) ) {
			$fg = strtolower( substr( preg_replace( '/[^0-9a-f]/i', '', $new['fg'] ), 0, 6 ) );
			if ( 6 > $l = strlen( $fg ) ) {
				$this->errors->add( 'fg', __( 'Invalid text color', 'pb-latex' ), $new['fg'] );
				$fg .= str_repeat( '0', 6 - $l );
			}
		}

		if ( isset( $new['bg'] ) ) {
			if ( 'transparent' == trim( $new['bg'] ) ) {
				$bg = 'transparent';
			} else {
				$bg = substr( preg_replace( '/[^0-9a-f]/i', '', $new['bg'] ), 0, 6 );
				if ( 6 > $l = strlen( $bg ) ) {
					$this->errors->add( 'bg', __( 'Invalid background color', 'pb-latex' ), $new['bg'] );
					$bg .= str_repeat( '0', 6 - $l );
				}
			}
		}

		if ( isset( $new['css'] ) ) {
			$css = str_replace( array( "\n", "\r" ), "\n", $new['css'] );
			$css = trim( preg_replace( '/[\n]+/', "\n", $css ) );
		}

		if ( isset( $new['wrapper'] ) ) {
			$wrapper = str_replace( array( "\n", "\r" ), "\n", $new['wrapper'] );
			if ( ! $wrapper = trim( preg_replace( '/[\n]+/', "\n", $new['wrapper'] ) ) )
					$wrapper = false;
		}
		if ( $wrapper && ( false !== strpos( $wrapper, '%BG_COLOR_RGB%' ) || false !== strpos( $wrapper, '%FG_COLOR_RGB%' ) ) )
				$this->errors->add( 'wrapper', __( 'PB LaTeX no longer supports ><code>%BG_COLOR_RGB%</code> or <code>%FG_COLOR_RGB</code> in the LaTeX preamble.  Please remove them.' ), $new['wrapper'] );

		if ( isset( $new['latex_path'] ) ) {
			$new['latex_path'] = trim( $new['latex_path'] );
			if ( ( ! $new['latex_path'] || ! file_exists( $new['latex_path'] ) ) && 'Automattic_Latex_WPCOM' != $method )
					$this->errors->add( 'latex_path', __( '<code>latex</code> path not found.', 'pb-latex' ), $new['latex_path'] );
			else $latex_path = $new['latex_path'];
		}

		$this->options = compact( 'bg', 'fg', 'css', 'latex_path', 'wrapper', 'method' );
		update_option( 'pb_latex', $this->options );
		return ! count( $this->errors->get_error_codes() );
	}

	// Attempts to use current settings to generate a temporory image (new with every page load)
	function testImage() {
		if ( 'Automattic_Latex_WPCOM' != $this->options['method'] ) return false;

		if ( is_array( $this->options ) ) extract( $this->options, EXTR_SKIP );

		$latex_object = $this->latex( '\displaystyle P_\nu^{-\mu}(z)=\frac{\left(z^2-1\right)^{\frac{\mu}{2}}}{2^\mu \sqrt{\pi}\Gamma\left(\mu+\frac{1}{2}\right)}\int_{-1}^1\frac{\left(1-t^2\right)^{\mu -\frac{1}{2}}}{\left(z+t\sqrt{z^2-1}\right)^{\mu-\nu}}dt', $bg, $fg, 3 );
		if ( ! empty( $wrapper ) ) $latex_object->wrapper( $wrapper );

		$message = '';

		$r = false;

		$url = $latex_object->url();

		if ( is_wp_error( $url ) ) {
			$code = $url->get_error_code();
			$message = '<div class="error"><p>' . $url->get_error_message() . "</p></div>\n";
			echo $message;
		} else {
			$alt = esc_attr( __( 'Test Image', 'pb-latex' ) );
			echo "<img class='test-image' src='" . esc_url( $url ) . "' alt='$alt' />\n";
			echo "<p class='test-image'>" . __( 'If you can see a big integral, all is well.', 'pb-latex' ) . '</p>';
			$r = true;
		}
		return $r;
	}

	function adminHead() {
		$current_method = $this->methods[$this->options['method']] ? $this->methods[$this->options['method']] : 'wpcom';
?>
<script type="text/javascript">
/* <![CDATA[ */
jQuery( function($) {
	$( '#pb-latex-method-switch :radio' ).change( function() {
		$( '.pb-latex-method' ).hide().css( 'background-color', '' );
		$( '.' + this.id ).show().css( 'background-color', '#ffffcc' );
	} );
} );
/* ]]> */
</script>
<style type="text/css">
/* <![CDATA[ */
p.test-image {
	text-align: center;
	font-size: 1.4em;
}
img.test-image {
	display: block;
	margin: 0 auto 1em;
}
.syntax p {
	margin-top: 0;
}
.syntax code {
	white-space: nowrap;
}
.pb-latex-method {
	display: none;
}
tr.pb-latex-method-<?php echo $current_method; ?> {
	display: block;
}
tr.pb-latex-method-<?php echo $current_method; ?> {
	display: table-row;
}
/* ]]> */
</style>
<?php
	}

	function adminPage() {
		if ( !current_user_can( 'manage_options' ) )
			wp_die( __( 'Insufficient LaTeX-fu', 'pb-latex' ) );
	
		$default_wrappers = array();
		foreach ( $this->methods as $class => $method ) {
			if ( 'Automattic_Latex_WPCOM' == $class )
				continue;
			require_once( dirname( __FILE__ ) . "/automattic-latex-$method.php" );
			$latex_object = new $class( '\LaTeX' );
			$default_wrappers[$method] = $latex_object->wrapper();
		}
		unset( $class, $method, $latex_object );
		
		if ( !is_array( $this->options ) )
			$this->options = array();

		$values = $this->options;
	
		$errors = array();
		if ( $errors = $this->errors->get_error_codes() ) :
			foreach ( $errors as $e )
				$values[$e] = $this->errors->get_error_data( $e );
	?>
	<div id='latex-config-errors' class='error'>
		<ul>
		<?php foreach ( $this->errors->get_error_messages() as $m ) : ?>
			<li><?php echo $m; ?></li>
		<?php endforeach; ?>
		</ul>
	</div>
	<?php	endif; ?>
	
	<div class='wrap'>
	<h2><?php _e( 'PB LaTeX Options', 'pb-latex' ); ?></h2>
	
	<?php if ( empty( $errors ) ) $this->testImage(); ?>
	
	<form action="<?php echo esc_url( remove_query_arg( 'updated' ) ); ?>" method="post">

	<table class="form-table">
	<tbody>
		<?php if ( empty( $errors ) ): ?>
		<tr>
			<th scope="row"><?php _e( 'Syntax' ); ?></th>
			<td class="syntax">
				<p><?php printf( __( 'You may use either the shortcode syntax %s<br /> or the &#8220;inline&#8221; syntax %s OR %s<br /> to insert LaTeX into your posts.', 'pb-latex' ),
					'<code>[latex]e^{\i \pi} + 1 = 0[/latex]</code>',
					'<code>$latex e^{\i \pi} + 1 = 0$</code>',
					'<code>$$ e^{\i \pi} + 1 = 0 $$</code>'
				); ?></p>
				<p><?php _e( 'For more information, see the <a href="http://wordpress.org/extend/plugins/wp-latex/faq/">FAQ</a>' ); ?></p>
			</td>
		</tr>
		
		<?php endif; ?>
		
		<tr<?php if ( in_array( 'method', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><?php _e( 'LaTeX generation method', 'pb-latex' ); ?></th>
			<td>
				<ul id="pb-latex-method-switch">
					<li><label for="pb-latex-method-wpcom"><input type="radio" name="pb_latex[method]" id="pb-latex-method-wpcom" value='Automattic_Latex_WPCOM'<?php checked( 'Automattic_Latex_WPCOM', $values['method'] ); ?> /> <?php printf( _x( '%s LaTeX server (recommended)|WordPress.com LaTeX Server (recommended)', 'pb-latex' ), '<a href="http://wordpress.com/" target="_blank">WordPress.com</a>' ); ?></label></li>
				</ul>
			</td>
		</tr>
		
		<tr<?php if ( in_array( 'fg', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><label for="pb-latex-fg"><?php _e( 'Default text color', 'pb-latex' ); ?></label></th>
			<td>
				<input type='text' name='pb_latex[fg]' value='<?php echo esc_attr( $values['fg'] ); ?>' id='pb-latex-fg' />
				<?php _e( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>' ); ?>
			</td>
		</tr>
		
		<tr<?php if ( in_array( 'bg', $errors ) ) echo ' class="form-invalid"'; ?>>
			<th scope="row"><label for="pb-latex-bg"><?php _e( 'Default background color', 'pb-latex' ); ?></label></th>
			<td>
				<input type='text' name='pb_latex[bg]' value='<?php echo esc_attr( $values['bg'] ); ?>' id='pb-latex-bg' />
				<?php _e( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>, or <code>transparent</code>' ); ?>
			</td>
		</tr>
		
	<?php foreach ( $default_wrappers as $method => $default_wrapper ) : ?>
		<tr class="pb-latex-method pb-latex-method-<?php echo $method; ?>">
			<th></th>
			<td>
				<h4>Leaving the above blank will use the following default preamble.</h4>
				<div class="pre"><code><?php echo $default_wrapper; ?></code></div>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
	</table>
	
	
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php echo esc_attr( __( 'Update LaTeX Options', 'pb-latex' ) ); ?>" />
		<?php wp_nonce_field( 'pb-latex' ); ?>
	</p>
	</form>
	</div>
	<?php
	}
	
	// Sets up default options
	function addOptions() {
		if ( is_array( $this->options ) )
			extract( $this->options, EXTR_SKIP );
	
		global $themecolors;
	
		if ( empty($bg) )
			$bg = isset( $themecolors['bg'] ) ? $themecolors['bg'] : 'transparent';
		if ( empty($fg) )
			$fg = isset( $themecolors['text'] ) ? $themecolors['text'] : '000000';
	
		if ( empty( $method ) )
			$method = 'Automattic_Latex_WPCOM';
	
		if ( empty( $css ) )
			$css = 'img.latex { vertical-align: middle; border: none; background: none; }';
	
		if ( empty( $latex_path ) )
			$latex_path = trim( @exec( 'which latex' ) );
	
		$latex_path   = $latex_path   && @file_exists( $latex_path )   ? $latex_path   : false;
	
		if ( empty( $wrapper ) )
			$wrapper = false;
	
		$this->options = compact( 'bg', 'fg', 'method', 'css', 'latex_path', 'wrapper' );
		update_option( 'pb_latex', $this->options );
	}
}
