<li class="{{$bullet_class}}{{ ! $should_be_displayed ? ' display-none' : '' }}">
	<a href="{{ $item['slug'] }}">
		@if($item['is_epub'])
			<span class="toc-chapter-title">{!! $item['title'] !!}</span>
		@else
			{!! $item['title'] !!}
		@endif
	</a>
</li>
