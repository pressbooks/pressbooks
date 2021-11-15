<div class="textbox interactive-content interactive-content--{{ $tag }}">
	<span class="interactive-content__icon"></span>
	<p>
		{{ __( 'One or more interactive elements has been excluded from this version of the text. You can view them online here: ', 'pressbooks' ) }}
		@if( isset( $id ) )
			<a href="{{ $url }}#{{ $id }}" title="{{ $title }}">{{ $url }}#{{ $id }}</a>
		@else
			<a href="{{ $url }}" title="{{ $title }}">{{ $url }}</a>
		@endif
	</p>
</div>
