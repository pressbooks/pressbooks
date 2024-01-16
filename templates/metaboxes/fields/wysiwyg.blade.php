<div class="form-field">
	<label for="{{ $field->id ?? $field->name }}">
		{{ $field->label }}
	</label>
	@php(wp_editor($field->value ?? '', $field->name, \Pressbooks\Editor\metadata_manager_default_editor_args(['textarea_rows' => $field->rows ?? 20])))
	@if(isset($field->description))
	<p class="description" id="{{ $field->id ?? $field->name . '-description' }}">
		{{ $field->description }}
	</p>
	@endif
</div>
