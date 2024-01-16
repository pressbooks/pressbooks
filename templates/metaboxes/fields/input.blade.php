<div class="form-field">
	<label @if($field->multiple) id="{{ $field->name }}-label" @else for="{{ $field->id }}" @endif>
		{!! $field->label !!}
	</label>
	@if($field->multiple)
		<div x-data="{
			count: {{ count($field->value) ?? 0 }},
			addNew() {
				this.count++;
				const newItem = this.$refs.template.content.cloneNode(true);
				const newInput = newItem.querySelector('input');
				newInput.setAttribute('id', `${newInput.id}-${this.count + 1}`);
				this.$refs.template.before(newItem);
			}
		}">
		@forelse($field->value as $key => $value)
		<input
			id="{{ $field->id }}-{{ $key + 1 }}"
			name="{{ $field->name }}[]"
			type="{{ $field->type ?? 'text' }}"
			value="{{ $value }}"
			@if($field->disabled) disabled @endif
			@if($field->readonly) readonly @endif
			aria-labelledby="{{ $field->name }}-label"
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
		@empty
		<input
			id="{{ $field->id }}-1"
			name="{{ $field->name }}[]"
			type="{{ $field->type ?? 'text' }}"
			value=""
			@if($field->disabled) disabled @endif
			@if($field->readonly) readonly @endif
			aria-labelledby="{{ $field->name }}-label"
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
		@endforelse
		<template x-ref="template">
			<input
				id="{{ $field->id }}"
				name="{{ $field->name }}[]"
				type="{{ $field->type ?? 'text' }}"
				value=""
				@if($field->disabled) disabled @endif
				@if($field->readonly) readonly @endif
				aria-labelledby="{{ $field->name }}-label"
				@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
			/>
		</template>
		<p><button class="button" type="button" @click="addNew">{{ __('Add New') }}</button></p>
		</div>
	@else
		<input
			id="{{ $field->id }}"
			name="{{ $field->name }}"
			type="{{ $field->type ?? 'text' }}"
			value="{{ $field->value }}"
			@if($field->disabled) disabled @endif
			@if($field->readonly) readonly @endif
			@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
		/>
	@endif
	@if(isset($field->description))
	<p class="description" id="{{ $field->id }}-description">
		{!! $field->description !!}
	</p>
	@endif
</div>
