<div class="form-field">
	<pressbooks-reorderable-multiselect>
		<label>
			{{ $field->label }}
		</label>
		@if ($field->description)
		<hint>
		</hint>
		@endif
		<input type="hidden" name="{{ $field->name }}" value="{{ $field->value ?? '' }}" />
		<select
			name="{{ $field->name }}_options"
		>
			@foreach($field->options as $value => $label)
			<option value="{{ $value }}">{!! $label !!}</option>
			@endforeach
		</select>
	</pressbooks-reorderable-multiselect>
</div>
