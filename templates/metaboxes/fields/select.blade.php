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
		@foreach($field->options as $value => $label)
		<option value="{{ $value }}" {{ selected($field->value, $value) }}>{!! $label !!}</option>
		@endforeach
	</select>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id . '-description' }}">
		{!! $field->description !!}
	</p>
	@endif
</div>
