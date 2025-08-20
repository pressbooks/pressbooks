<?php
/**
 * Integración de Editoria11y dentro de TinyMCE sin modificar el plugin original.
 * Solo se carga en pantallas de edición de contenido de libros Pressbooks.
 */

namespace Pressbooks\Editor;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola el script integrador para inyectar Editoria11y dentro del iframe de TinyMCE.
 */
function enqueue_editoria11y_tinymce_adapter(): void {
    // Condiciones básicas: pantalla admin de edición de post y plugin Editoria11y activo.
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || $screen->base !== 'post' ) {
        return;
    }
    if ( ! function_exists( 'ed11y_get_plugin_settings' ) ) { // Plugin editoria11y no activo.
        return;
    }

    // TinyMCE está forzado por Pressbooks; aseguramos no cargar en block editor.
    $post_type = get_post_type();
    if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $post_type ) ) {
        return; // Evitar Gutenberg (por si acaso en el futuro cambia configuración).
    }

    $src_path = PB_PLUGIN_DIR . 'assets/dist/scripts/editoria11y-tinymce.js';
    $src_url  = PB_PLUGIN_URL . 'assets/dist/scripts/editoria11y-tinymce.js';
    $ver      = file_exists( $src_path ) ? (string) filemtime( $src_path ) : '1.0.0';

    // Intentamos obtener ajustes del plugin para derivar rutas de assets si éste no los ha encolado debido al editor clásico.
    $settings = function_exists( 'ed11y_get_plugin_settings' ) ? (array) ed11y_get_plugin_settings() : [];

    // Si el plugin no encoló sus scripts (clásico) tratamos de forzarlos usando rutas conocidas.
    if ( ! wp_script_is( 'editoria11y-js', 'enqueued' ) ) {
        $candidates      = [];
        $css_candidates  = [];
        if ( isset( $settings['assetsUrl'] ) ) {
            $candidates[]     = trailingslashit( $settings['assetsUrl'] ) . 'editoria11y.min.js';
            $css_candidates[] = trailingslashit( $settings['assetsUrl'] ) . 'editoria11y.min.css';
        }
        if ( isset( $settings['pluginUrl'] ) ) {
            $base             = trailingslashit( $settings['pluginUrl'] );
            $candidates[]     = $base . 'dist/editoria11y.min.js';
            $candidates[]     = $base . 'build/editoria11y.min.js';
            $css_candidates[] = $base . 'dist/editoria11y.min.css';
            $css_candidates[] = $base . 'build/editoria11y.min.css';
        }
        // Último recurso: asunción de estructura estándar del plugin (slug editoria11y-wp).
        $candidates[]     = plugins_url( 'editoria11y-wp/dist/editoria11y.min.js' );
        $css_candidates[] = plugins_url( 'editoria11y-wp/dist/editoria11y.min.css' );

        $script_url = reset( $candidates );
        $style_url  = reset( $css_candidates );
        if ( $script_url ) {
            wp_enqueue_script( 'editoria11y-js', $script_url, [], $ver, true );
        }
        if ( $style_url ) {
            wp_enqueue_style( 'editoria11y-css', $style_url, [], $ver );
        }
        if ( $script_url || $style_url ) {
            wp_add_inline_script( 'pressbooks-editoria11y-tinymce', 'window._pbEd11yForcedAssets = ' . wp_json_encode( [ 'js' => $script_url, 'css' => $style_url ] ) . ';', 'before' );
        }
    }

    wp_enqueue_script( 'pressbooks-editoria11y-tinymce', $src_url, [ 'editoria11y-js' ], $ver, true );
    if ( $settings ) {
        wp_add_inline_script( 'pressbooks-editoria11y-tinymce', 'window._pbEd11ySettings = ' . wp_json_encode( $settings ) . '; window.console && console.debug("[PB Ed11y] settings", window._pbEd11ySettings);', 'before' );
    } else {
        wp_add_inline_script( 'pressbooks-editoria11y-tinymce', 'window.console && console.debug("[PB Ed11y] No ed11y settings detected");', 'before' );
    }
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_editoria11y_tinymce_adapter', 60 );
