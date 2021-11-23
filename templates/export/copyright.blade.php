<div id="copyright-page">
	<div class="ugc">
		@if ( isset( $license_copyright ) )
			{!! $license_copyright !!}
		@endif
		@if ( isset( $custom_copyright ) )
			{!! $custom_copyright !!}
		@endif
		@if ( isset( $has_default ) )
			<p>
				@if ( isset( $default_copyright_name ) )
					{{ $default_copyright_name }}
					{{ $default_copyright_date }}
				@endif
				@if ( isset( $default_copyright_holder ) )
					{{ $default_copyright_holder }}
				@endif
			</p>
		@endif
		@if ( isset( $freebie_notice ) )
			<p>
				{!! $freebie_notice !!}
			</p>
		@endif
	</div>
</div>
