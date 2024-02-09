<div class="form-field">
	<pressbooks-reorderable-multiselect data-messages="{{ json_encode([
		'Add' => __('Add', 'pressbooks'),
		'Remove' => __('Remove', 'pressbooks'),
		'Move Up' => __('Move Up', 'pressbooks'),
		'Move Down' => __('Move Down', 'pressbooks'),
		'Available Options' => __('Available options', 'pressbooks'),
		'Moved to position $1' => __('Déplacé en position $1'),
		'Removed $1 from selection' => __('Supprimer $1 de la sélection'),
		'Added $1 to selection' => __('Ajouté $1 à la sélection')
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
