<div class="form-field">
	<label for="{{ $field->id }}">
		{{ $field->label }}
	</label>
	<duet-date-picker identifier="{{ $field->id }}" name="{{ $field->name }}" value="{{ $field->value ? esc_attr( date( 'Y-m-d', (int) $field->value ) ) : '' }}"></duet-date-picker>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id . '-description' }}">
		{{ $field->description }}
	</p>
	@endif
</div>
