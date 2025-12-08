@foreach ($columns as $column)
	<th scope="col" class="{{ $column['class'] }} pb-table-header">
		@if ($column['sortable'])
			<a href="{{ $column['url'] }}" class="sorting-button">
				<span>{!! $column['label'] !!}</span>
				<span class="sorting-indicators">
                    <span class="sorting-indicator asc" aria-hidden="true"></span>
                    <span class="sorting-indicator desc" aria-hidden="true"></span>
                </span>
				<span class="screen-reader-text">{{ $column['screen_reader_text'] }}</span>
			</a>
		@else
			{!! $column['label'] !!}
		@endif
	</th>
@endforeach
