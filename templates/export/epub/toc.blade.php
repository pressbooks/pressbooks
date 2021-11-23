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
			@foreach( $manifest as $key => $value )
				@if( $part_open && 0 !== strpos( $key, 'chapter-') )
					@php( $part_open = false )
					</ol></li>
				@endif

				@if( get_post_meta( $value['ID'], 'pb_part_invisible', true ) !== 'on' )
					@php( $text = wp_strip_all_tags( \Pressbooks\Sanitize\decode( $value['post_title'] ) ) ?? ' ' )

					@if( 0 === strpos( $key, 'part-' ) )
						<li><a href="{{ $value['filename'] }}">{{ $text }}</a>
					@else
						<li>
							<a href="{{ $value['filename'] }}">{{ $text }}</a>
						</li>
					@endif

					@if( 0 === strpos($key, 'part-') )
						@php( $part_open = true )
						<ol>
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
