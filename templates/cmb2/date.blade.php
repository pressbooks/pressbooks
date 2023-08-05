<input
	type="date"
	name="{{ $args['_name'] }}[]"
	id="{{ $args['_id'] }}"
	value="{{ $value ? esc_attr( date( 'Y-m-d', (int) $value ) ) : '' }}"
	pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"
	@foreach($args['attributes'] as $attr => $val)
	{{ $attr }}="{{ $val }}"
	@endforeach
/>
@if(isset($args['desc']))
<p class="description">{{ $args['desc'] }}</p>
@endif
