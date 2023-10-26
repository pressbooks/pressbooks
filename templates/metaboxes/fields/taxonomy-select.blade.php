<div class="form-field">
	@if($field->multiple)
	<pressbooks-multiselect>
	@endif
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
			<option value="{{ $value }}" {{ selected($field->multiple ? in_array($value, $field->value) : $value === $field->value) }}>{!! $label !!}</option>
			@endforeach
		</select>
		@if(isset($field->description))
		<p class="description" id="{{ $field->id . '-description' }}">
			{!! $field->description !!}
		</p>
		@endif
	@if($field->multiple)
	</pressbooks-multiselect>
	@endif
</div>
