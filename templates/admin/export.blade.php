{!! $dependency_errors_msg or '' !!}
<div class="wrap">
	<?php
	/**
	 * @since 4.3.0
	 */
	?>
    {!! do_action( 'pb_top_of_export_page' ) !!}
    <h1>{{ __( 'Export', 'pressbooks') }}</h1>
    <p>{{ __( 'You can select multiple formats below. Pressbooks keeps your last three exports in each export format. You can pin specific files to make sure they don\'t get deleted', 'pressbooks') }}</p>
    <div id="export-options" class="postbox">
		<button type="button" class="handlediv" aria-expanded="true">
			<span class="screen-reader-text">{{ __( 'Toggle panel: Export Options', 'pressbooks') }}</span>
			<span class="toggle-indicator" aria-hidden="true"></span>
		</button>
        <h2>
			<span>{{ __( 'Export Options', 'pressbooks') }}</span>
		</h2>
        <div class="inside">
            <form id="pb-export-form" action="{{ $export_form_url }}" method="POST">
                <div class="grid">
					<div class="formats">
						{{-- Supported Formats --}}
						<div class="supported-formats">
							<fieldset class="standard">
								<legend>{{ __( 'Supported formats', 'pressbooks' ) }}:</legend>
								@foreach($formats['standard'] as $key => $value)
									<input type="checkbox" id="{{$key}}" name="export_formats[{{$key}}]" value="1" {{isset( $dependency_errors[ $key ] ) ? 'disabled' : ''}}/><label
											for="{{$key}}"> {{$value}}</label><br/>
								@endforeach
							</fieldset>
						</div>
						{{-- Other Formats --}}
						<div class="other-formats">
							<fieldset class="exotic">
								<legend>{{ __( 'Other formats', 'pressbooks' ) }}:</legend>
								@foreach($formats['exotic'] as $key => $value)
									<input type="checkbox" id="{{$key}}" name="export_formats[{{$key}}]" value="1" {{isset( $dependency_errors[ $key ] ) ? 'disabled' : ''}}/><label
											for="{{$key}}"> {{$value}}</label><br/>
								@endforeach
							</fieldset>
						</div>
					</div>
                    {{-- Theme --}}
                    <div class="themes">
                    	{{-- Theme --}}
                        <div class="theme">
                            <div class="theme-screenshot">
                                <img src="{{ apply_filters( 'pb_stylesheet_directory_uri', get_stylesheet_directory_uri() ) }}/screenshot.png" alt="">
                            </div>
                        </div>
						{{-- Theme Controls --}}
						<div class="theme-controls">
							<p><b>{{  __( 'Your Theme', 'pressbooks' ) }}:</b> {!! $theme_name !!}</p>
							<p><a class="button button-primary" href="{{ get_bloginfo( 'url' ) }}/wp-admin/themes.php">{{  __( 'Change Theme', 'pressbooks' ) }}</a></p>
							<p><a class="" href="{{ get_bloginfo( 'url' ) }}/wp-admin/themes.php?page=pressbooks_theme_options">{{ __( 'Theme Options', 'pressbooks' ) }}</a></p>
						</div>
                    </div>
                </div>
				<?php
				/**
				 * @since 5.3.0
				 *
				 * Fires just before the export html form ends
				 * Use this hook to add additional input UI to the Pressbooks export admin page.
				 */
				?>
                {!! do_action( 'pb_export_form_end' ) !!}
            </form>
        </div>
    </div>
    <div class="export-control">
        <p><input id="pb-export-button" type="button" class="button button-hero button-primary generate" value="{{ __( 'Export Your Book', 'pressbooks' ) }}"/></p>
		<div id="pb-sse-progressbar"></div>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info"></span></p>
    </div>
    <div class="clear"></div>
    <h1>{{ __( 'Latest Exports', 'pressbooks') }}</h1>
    <div id="pin-notifications" class="screen-reader-text" aria-live="assertive"></div>
	<?php
	/**
	 * @since 5.3.0
	 *
	 * Filters whether to show the default export file list.
	 * Use this hook to disable the default export file list and add your own.
	 *
	 * @param bool $value Whether to show the default export file list.
	 *                    Returning false to the filter will disable the output. Default true.
	 */
	?>
    @if ( apply_filters( 'pb_export_show_files', true ) )
        @inject('table', '\Pressbooks\Modules\Export\Table')
		<?php /** @var \Pressbooks\Modules\Export\Table $table */ ?>
        <script type='text/javascript'>
			/* <![CDATA[ */
            {!! $table->inlineJs() !!}
			/* ]]> */
        </script>
        {!! $table->prepare_items() !!}
        <form method="POST">
            {!! $table->display() !!}
        </form>
    @endif
</div>
