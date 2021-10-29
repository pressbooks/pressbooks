<div class="{{$post_type_class}} {{ $subclass }}" id="{{ $slug }}" title="{{ $short_title }}">
	<div class="{{$post_type_class}}-title-wrap">
		<p class="{{$post_type_class}}-number">{{ $post_number }}</p>
		<h1 class="{{$post_type_class}}-title">{!! $title !!}</h1>
		@if(  isset( $is_new_buckram ) && $is_new_buckram )
			@include('export/sub-author-partial')
		@endif
	</div>
	<div class="ugc {{$post_type_class}}-ugc">
		@if( isset( $is_new_buckram ) && ! $is_new_buckram )
			@include('export/sub-author-partial')
		@endif
		{!! $content !!}
	</div>
	@if( isset( $append_post_content ) )
		{!! $append_post_content !!}
	@endif
	@if( isset( $endnotes ) )
		{!! $endnotes !!}
	@endif
	@if( isset ( $footnotes ) )
		{!! $footnotes !!}
	@endif
</div>
