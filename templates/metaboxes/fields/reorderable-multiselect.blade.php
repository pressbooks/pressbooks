<div class="form-field">
	<pressbooks-reorderable-multiselect data-messages="{{ json_encode([
		'Add' => __('Add', 'pressbooks'),
		'Remove' => __('Remove', 'pressbooks'),
		'Move Up' => __('Move Up', 'pressbooks'),
		'Move Down' => __('Move Down', 'pressbooks'),
		'Available Options' => __('Available options', 'pressbooks'),
		'Moved to position $1' => __('Moved to position $1', 'pressbooks'),
		'Removed $1 from selection' => __('Removed $1 from selection', 'pressbooks'),
		'Added $1 to selection' => __('Added $1 to selection', 'pressbooks')
	]) }}">
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
