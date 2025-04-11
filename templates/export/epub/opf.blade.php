{!! '<'.'?xml version="1.0" encoding="UTF-8" ?>' !!}
<package version="3.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="pub-identifier">
	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
		<dc:title id="pub-title">{!!  $meta['pb_title'] ?? get_bloginfo('name') !!}</dc:title>
		<dc:language id="pub-language">{{ $meta['pb_language'] ?? $lang }}</dc:language>
		<meta property="dcterms:modified">{{ date( 'Y-m-d\TH:i:s\Z' ) }}</meta>
		<dc:identifier id="pub-identifier">
			@if( ! empty( $meta['pb_ebook_isbn'] ) )
				{{ trim( $meta['pb_ebook_isbn'] ) }}
			@elseif( ! empty( $meta['pb_book_doi'] ) )
				{{ trim( $meta['pb_book_doi'] ) }}
			@else
				{{ trim( get_bloginfo( 'url' ) ) }}
			@endif
		</dc:identifier>
		@if( ! empty( $meta['pb_about_50'] ) )
			<dc:description>{{ $meta['pb_about_50'] }}</dc:description>
		@elseif( ! empty( $meta['pb_about_140'] ) )
			<dc:description>{{ $meta['pb_about_140'] }}</dc:description>
		@endif

		@php
		  $index = 1;
		@endphp

		@if( ! \Pressbooks\Utility\empty_space( $meta['pb_editors'] ) )
			@foreach( $meta['pb_editors'] as $editor )
				@include('export/epub/contributor-partial', ['contributor' => $editor, 'role' => 'edt', 'index' => $index])

				@php
					$index++;
				@endphp
			@endforeach
		@endif

		@if( ! \Pressbooks\Utility\empty_space( $meta['pb_authors'] ) )
			@foreach( $meta['pb_authors'] as $author )
				@include('export/epub/contributor-partial', ['contributor' => $author, 'role' => 'aut', 'index' => $index])

				@php
					$index++;
				@endphp
			@endforeach
		@endif

		@if( ! \Pressbooks\Utility\empty_space( $meta['pb_translators'] ) )
			@foreach( $meta['pb_translators'] as $translator )
				@include('export/epub/contributor-partial', ['contributor' => $translator, 'role' => 'trl', 'index' => $index])

				@php
					$index++;
				@endphp
			@endforeach
		@endif

		@if( ! \Pressbooks\Utility\empty_space( $meta['pb_illustrators'] ) )
			@foreach( $meta['pb_illustrators'] as $illustrator )
				@include('export/epub/contributor-partial', ['contributor' => $illustrator, 'role' => 'ill', 'index' => $index])

				@php
					$index++;
				@endphp
			@endforeach
		@endif

		@if( $index === 1 )
			<dc:creator id="creator">Pressbooks</dc:creator>
		@endif

		@if( ! \Pressbooks\Utility\empty_space( $meta['pb_contributors'] ) )
			@foreach( $meta['pb_contributors'] as $index => $contributor )
				@include('export/epub/contributor-partial', ['contributor' => $contributor, 'role' => 'ctb', 'index' => $index + 1])
			@endforeach
		@endif

		@if ( ! empty( $meta['pb_copyright_year'] ) || ! empty( $meta['pb_copyright_holder'] ) )
			<dc:rights>
				{{ \Pressbooks\Sanitize\sanitize_xml_attribute( 'Copyright', 'pressbooks') }} &#169;
				@if( ! empty( $meta['pb_copyright_year'] ) )
					{{ $meta['pb_copyright_year'] }}
				@elseif( ! empty( $meta['pb_publication_date'] ) )
					{{ date( 'Y', $meta['pb_publication_date'] ) }}
				@else
					{{ date( 'Y' ) }}
				@endif

				@if( ! empty( $meta['pb_copyright_holder'] ) )
					{!! \Pressbooks\Sanitize\sanitize_xml_attribute( __('by', 'pressbooks') ) . ' ' . $meta['pb_copyright_holder'] !!}
				@endif

				{!! $do_copyright_license ?? '' !!}
			</dc:rights>
		@endif

		@foreach( $meta as $key => $value )
			@if( 'pb_publisher' === $key )
				<dc:publisher>{{ $value }}</dc:publisher>
			@endif
			@if( 'pb_publication_date' === $key )
				<dc:date>{{ date( 'Y-m-d', (int) $value ) }}</dc:date>
			@endif
			@if( 'pb_bisac_subject' === $key )
				@foreach( explode( ',', $value ) as $subject )
					<dc:subject>{{ trim( $subject ) }}</dc:subject>
				@endforeach
			@endif
		@endforeach

		<meta name="cover" content="cover-image"/>
		<!-- TODO: figure out way to add visual, auditory access mode details if book content includes images/audio -->
		<meta property="schema:accessMode">textual</meta>
		<meta property="schema:accessModeSufficient">textual, visual</meta>
		<meta property="schema:accessibilityFeature">structuralNavigation</meta>
		<meta property="schema:accessibilityFeature">alternativeText</meta>
		<!-- TODO: if book has long description or MathML, display extra info in meta -->
		<meta property="schema:accessibilityFeature">MathML</meta>
		<meta property="schema:accessibilityFeature">longDescription</meta>
		<meta property="schema:accessibilityHazard">noFlashingHazard</meta>
		<meta property="schema:accessibilityHazard">noMotionSimulationHazard</meta>
		<meta property="schema:accessibilityHazard">noSoundHazard</meta>
		<!-- TODO: Allow creators to add accessibility info/summary in book info and display it here -->
		<meta property="schema:accessibilitySummary">This publication conforms to the EPUB Accessibility specification at WCAG level A.</meta>
	</metadata>

	<manifest>
		{!! $manifest_filelist !!}
		{!! $manifest_assets !!}
		<item id="toc" properties="nav" href="toc.xhtml" media-type="application/xhtml+xml"></item>
		@if( ! empty( $stylesheet ) )
			<item id="stylesheet" href="{{ $stylesheet }}"  media-type="text/css"></item>
		@endif
	</manifest>

	<spine>
		@foreach( $manifest as $key => $value )
			<itemref idref="{{ $key }}" linear="yes" />
		@endforeach
	</spine>
</package>
