<div class="form-field">
	<pressbooks-select>
		<label for="{{ $field->id }}">
			{{ $field->label }}
		</label>
		<select
			class="widefat"
			@if($field->multiple) multiple @endif
			name="{{ $field->multiple ? $field->name . '[]' : $field->name }}"
			id="{{ $field->id }}"
			@if ($field->description)
			aria-describedby="{{ $field->id . '-hint' }}"
			@endif
		>
			@foreach($field->options as $key => $value)
			@if(is_array($value))
			<optgroup label="{{ $key }}">
				@foreach($value as $option => $label)
				<option value="{{ $option }}" {{ selected($field->multiple ? in_array($option, $field->value) : $option === $field->value) }}>{!! $label !!}</option>
				@endforeach
			</optgroup>
			@else
			<option value="{{ $key }}" {{ selected($field->multiple ? in_array($key, $field->value) : $key === $field->value) }}>{!! $value !!}</option>
			@endif
			@endforeach
		</select>
		@if(isset($field->description))
		<p class="description" id="{{ $field->id . '-hint' }}" slot="after">
			{!! $field->description !!}
		</p>
		@endif
	</pressbooks-select>
</div>
