<div class="form-field">
	<label @if($field->multiple) id="{{ $field->name }}-label" @else for="{{ $field->id }}" @endif>
		{!! $field->label !!}
	</label>
	@if($field->multiple)
		@forelse($field->value as $key => $value)
		<input
			id="{{ $field->id }}-{{ $key }}"
			name="{{ $field->name }}"
			type="{{ $field->type ?? 'text' }}"
			value="{{ $value }}"
			@if(isset($field->disabled)) disabled @endif
			aria-labelledby="{{ $field->name }}-label"
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
		@empty
		<input
			id="{{ $field->id }}-0"
			name="{{ $field->name }}"
			type="{{ $field->type ?? 'text' }}"
			value=""
			@if(isset($field->disabled)) disabled @endif
			aria-labelledby="{{ $field->name }}-label"
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
		@endforelse
	@else
		<input
			id="{{ $field->id }}"
			name="{{ $field->name }}"
			type="{{ $field->type ?? 'text' }}"
			value="{{ $field->value }}"
			@if(isset($field->disabled)) disabled @endif
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
	@endif
	@if(isset($field->description))
	<p class="description" id="{{ $field->id }}-description">
		{!! $field->description !!}
	</p>
	@endif
</div>
