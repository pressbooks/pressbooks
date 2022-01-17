{!! '<'.'?xml version="1.0" encoding="UTF-8" ?>' !!}
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">
	<head>
		<meta http-equiv="default-style" content="text/html; charset=utf-8"/>
		<title>{{ get_bloginfo('name') }}</title>
		@if( ! empty( $stylesheet ) )
			<link rel="stylesheet" href="{{ $stylesheet }}" type="text/css" />
		@endif
	</head>
	<body>
		<nav epub:type="toc">
			<h1 class="title">{{ __( 'Table of Contents', 'pressbooks' ) }}</h1>
			<ol epub:type="list">
			@php( $part_open = false )
			@foreach( $manifest_keys as $key )
				@php( $value = $manifest[ $key ] )
				@php( $next_key = next( $manifest_keys ) )

				@if( $part_open && 0 !== strpos( $key, 'chapter-' ) )
					@php( $part_open = false )
					</ol></li>
				@endif

				@if( get_post_meta( $value['ID'], 'pb_part_invisible', true ) !== 'on' )
					@php( $text = wp_strip_all_tags( \Pressbooks\Sanitize\decode( $value['post_title'] ) ) ?? ' ' )

					@if( 0 === strpos( $key, 'part-' ) )
						@if( $next_key && 0 === strpos( $next_key, 'chapter-' ) )
							@php( $part_open = true )
							<li><a href="{{ $value['filename'] }}">{{ $text }}</a><ol>
						@else
							<li><a href="{{ $value['filename'] }}">{{ $text }}</a></li>
						@endif
					@else
						<li><a href="{{ $value['filename'] }}">{{ $text }}</a></li>
					@endif
				@endif
			@endforeach
			@if( $part_open )
				</ol></li>
			@endif
			</ol>
		</nav>
	</body>
</html>
