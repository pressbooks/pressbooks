<div class="form-field">
	<label for="{{ $field->id }}">
		{{ $field->label }}
	</label>
	<select
		class="widefat"
		@if($field->multiple) multiple @endif
		name="{{ $field->name }}"
		id="{{ $field->id }}"
		@if ($field->description)
		aria-describedby="{{ $field->id . '-description' }}"
		@endif
	>
		{{-- TODO: Support <optgroup> elements --}}
		@foreach($field->options as $value => $label)
		<option value="{{ $value }}" {{ $field->multiple ? in_array($value, $field->value) : selected($field->value, $value) }}>{!! $label !!}</option>
		@endforeach
	</select>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id . '-description' }}">
		{!! $field->description !!}
	</p>
	@endif
</div>
