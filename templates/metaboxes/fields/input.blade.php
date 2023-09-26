<div class="form-field">
	<label for="{{ $field->id }}">
		{!! $field->label !!}
	</label>
	<input
		id="{{ $field->id }}"
		name="{{ $field->name }}"
		type="{{ $field->type ?? 'text' }}"
		value="{{ $field->value }}"
		@if(isset($field->disabled)) disabled @endif
		@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
	/>
	@if(isset($field->description))
	<p class="description" id="{{ $field->id }}-description">
		{!! $field->description !!}
	</p>
	@endif
</div>
