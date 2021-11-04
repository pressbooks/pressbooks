<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops" xml:lang="{{ $lang }}" lang="{{ $lang }}">
	<head>
		<title>{{ $post_title }} -- {{ get_bloginfo('name') }}</title>
		<meta name="EPB-UUID" content="{{ $isbn }}" />

        @if( $stylesheet )
			<link rel="stylesheet" href="{{ $stylesheet }}" type="text/css" />
        @endif
	</head>
	<body>
		<section>
			{!! $post_content !!}
		</section>
	</body>
</html>
