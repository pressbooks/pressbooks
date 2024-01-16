<div class="form-field">
	<label for="{{ $field->id ?? $field->name }}">
		{{ $field->label }}
	</label>
	<textarea
		id="{{ $field->id ?? $field->name }}"
		name="{{ $field->name }}"
		type="{{ $field->type ?? 'text' }}"
		@if($field->disabled) disabled @endif
		@if($field->readonly) readonly @endif
		@if(isset($field->description)) aria-describedby="{{ $field->id ?? $field->name . '-description' }}" @endif
	>{{ $field->value ?? '' }}</textarea>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id ?? $field->name . '-description' }}">
		{{ $field->description }}
	</p>
	@endif
</div>
