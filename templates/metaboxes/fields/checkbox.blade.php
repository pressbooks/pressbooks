<div class="form-field">
	<input
		id="{{ $field->id }}"
		name="{{ $field->name }}"
		type="{{ $field->type }}"
		{{ checked( $field->value, 'on', false ) }}
		@if(isset($field->disabled)) disabled @endif
		@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
	/>
	<label for="{{ $field->id }}">
		{{ $field->label }}
	</label>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id . '-description' }}">
		{{ $field->description }}
	</p>
	@endif
</div>
