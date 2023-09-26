<div class="form-wrap">
	@foreach($fields as $field)
		@include("metaboxes.fields.{$field->view}", ['field' => $field])
	@endforeach
</div>
