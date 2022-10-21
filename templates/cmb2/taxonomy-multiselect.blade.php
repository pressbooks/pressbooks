<select
	class="widefat"
	multiple
	name="{{ $args['_name'] }}[]"
	id="{{ $args['_id'] }}"
	@foreach($args['attributes'] as $attr => $val)
	{{ $attr }}="{{ $val }}"
	@endforeach
>
	@foreach($options as $term)
	<option class="cmb2-option" value="{{ esc_attr( $term->slug ) }}" @if(in_array( $term->slug, $selections )) selected @endif>{{ esc_html( $term->name ) }}</option>
	@endforeach
</select>
