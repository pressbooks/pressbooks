<div class="form-field">
	<label id="{{ $field->name }}-label">{!! $field->label !!}</label>

	<div x-data="{
		count: {{ count($field->value) }},
		addNew() {
			this.count++;
			const newItem = this.$refs.template.content.cloneNode(true);
			const index = this.count;
			newItem.querySelectorAll('[data-index]').forEach(el => {
				const name = el.getAttribute('name');
				const id = el.getAttribute('id');
				if (name) el.setAttribute('name', name.replace('[0]', `[${index}]`));
				if (id) el.setAttribute('id', id.replace('-0-', `-${index}-`));
			});
			this.$refs.template.before(newItem);
		},
		remove(event) {
			const item = event.target.closest('.related-link-item');
			if (item) item.remove();
		},
		checkLink(event) {
			const url = event.target.value;
			if (!url) return;
			try {
				fetch(url, { method: 'HEAD' })
					.then(response => {
						if (!response.ok) {
							console.log('URL appears to be invalid or unreachable');
						} else {
							console.log('URL is valid' );
						}
					})
					.catch(() => {
						console.log('Could not verify URL');
					});
			} catch (error) {
				console.log('Could not verify URL');
			}
		}
	}" class="related-links-container">

		@forelse($field->value as $index => $item)
		<div class="related-link-item" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-title-{{ $index }}" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'Title', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
				</label>
				<input
					id="{{ $field->id }}-title-{{ $index }}"
					name="{{ $field->name }}[title][]"
					type="text"
					value="{{ $item['title'] ?? '' }}"
					required
					placeholder="{{ __( 'e.g., Instructor\'s Manual', 'pressbooks' ) }}"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
					data-index="{{ $index }}"
				/>
			</div>
			
			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-url-{{ $index }}" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'URL', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
				</label>
				<input
					id="{{ $field->id }}-url-{{ $index }}"
					name="{{ $field->name }}[url][]"
					type="url"
					value="{{ $item['url'] ?? '' }}"
					required
					placeholder="https://example.com/resource"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					data-index="{{ $index }}"
					@blur="checkLink"
				/>
			</div>

			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-description-{{ $index }}" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'Description', 'pressbooks' ) }}
				</label>
				<input
					id="{{ $field->id }}-description-{{ $index }}"
					name="{{ $field->name }}[description][]"
					type="text"
					value="{{ $item['description'] ?? '' }}"
					placeholder="{{ __( 'Optional description of the resource', 'pressbooks' ) }}"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					data-index="{{ $index }}"
				/>
			</div>

			<div style="margin-bottom: 10px;">
				<label style="display: inline-flex; align-items: center; cursor: pointer;">
					<input
						type="checkbox"
						name="{{ $field->name }}[privacy][]"
						value="private"
						{{ isset($item['privacy']) && $item['privacy'] === 'private' ? 'checked' : '' }}
						style="margin-right: 8px;"
						data-index="{{ $index }}"
					/>
					<span>{{ __( 'Private (only visible to logged-in users with the role of Author, Editor, or Administrator)', 'pressbooks' ) }}</span>
				</label>
			</div>

			<button
				type="button"
				class="button button-secondary"
				@click="remove"
				style="margin-top: 5px;"
			>
				<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
				{{ __( 'Remove', 'pressbooks' ) }}
			</button>
		</div>
		@empty
		<div class="related-link-item" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-title-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'Title', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
				</label>
				<input
					id="{{ $field->id }}-title-0"
					name="{{ $field->name }}[title][]"
					type="text"
					value=""
					required
					placeholder="{{ __( 'e.g., Instructor\'s Manual', 'pressbooks' ) }}"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
					data-index="0"
				/>
			</div>
			
			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-url-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'URL', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
				</label>
				<input
					id="{{ $field->id }}-url-0"
					name="{{ $field->name }}[url][]"
					type="url"
					value=""
					required
					placeholder="https://example.com/resource"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					data-index="0"
					@blur="checkLink"
				/>
			</div>

			<div style="margin-bottom: 10px;">
				<label for="{{ $field->id }}-description-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
					{{ __( 'Description', 'pressbooks' ) }}
				</label>
				<input
					id="{{ $field->id }}-description-0"
					name="{{ $field->name }}[description][]"
					type="text"
					value=""
					placeholder="{{ __( 'Optional description of the resource', 'pressbooks' ) }}"
					style="width: 100%;"
					aria-labelledby="{{ $field->name }}-label"
					data-index="0"
				/>
			</div>

			<div style="margin-bottom: 10px;">
				<label style="display: inline-flex; align-items: center; cursor: pointer;">
					<input
						type="checkbox"
						name="{{ $field->name }}[privacy][]"
						value="private"
						style="margin-right: 8px;"
						data-index="0"
					/>
					<span>{{ __( 'Private (only visible to logged-in users with the role of Author, Editor, or Administrator)', 'pressbooks' ) }}</span>
				</label>
			</div>

			<button
				type="button"
				class="button button-secondary"
				@click="remove"
				style="margin-top: 5px;"
			>
				<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
				{{ __( 'Remove', 'pressbooks' ) }}
			</button>
		</div>
		@endforelse

		<template x-ref="template">
			<div class="related-link-item" style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px;">
				<div style="margin-bottom: 10px;">
					<label for="{{ $field->id }}-title-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
						{{ __( 'Title', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
					</label>
					<input
						id="{{ $field->id }}-title-0"
						name="{{ $field->name }}[title][]"
						type="text"
						value=""
						required
						placeholder="{{ __( 'e.g., Instructor\'s Manual', 'pressbooks' ) }}"
						style="width: 100%;"
						aria-labelledby="{{ $field->name }}-label"
						@if(isset($field->description)) aria-describedby="{{ $field->id }}-description" @endif
						data-index="0"
					/>
				</div>
				
				<div style="margin-bottom: 10px;">
					<label for="{{ $field->id }}-url-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
						{{ __( 'URL', 'pressbooks' ) }} <span style="color: #d63638;">*</span>
					</label>
					<input
						id="{{ $field->id }}-url-0"
						name="{{ $field->name }}[url][]"
						type="url"
						value=""
						required
						placeholder="https://example.com/resource"
						style="width: 100%;"
						aria-labelledby="{{ $field->name }}-label"
						data-index="0"
					/>
				</div>

				<div style="margin-bottom: 10px;">
					<label for="{{ $field->id }}-description-0" style="display: block; font-weight: 600; margin-bottom: 5px;">
						{{ __( 'Description', 'pressbooks' ) }}
					</label>
					<input
						id="{{ $field->id }}-description-0"
						name="{{ $field->name }}[description][]"
						type="text"
						value=""
						placeholder="{{ __( 'Optional description of the resource', 'pressbooks' ) }}"
						style="width: 100%;"
						aria-labelledby="{{ $field->name }}-label"
						data-index="0"
					/>
				</div>

				<div style="margin-bottom: 10px;">
					<label style="display: inline-flex; align-items: center; cursor: pointer;">
						<input
							type="checkbox"
							name="{{ $field->name }}[privacy][]"
							value="private"
							style="margin-right: 8px;"
							data-index="0"
						/>
						<span>{{ __( 'Private (only visible to logged-in users with the role of Author, Editor, or Administrator)', 'pressbooks' ) }}</span>
					</label>
				</div>

				<button
					type="button"
					class="button button-secondary"
					@click="remove"
					style="margin-top: 5px;"
				>
					<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
					{{ __( 'Remove', 'pressbooks' ) }}
				</button>
			</div>
		</template>

		<button
			type="button"
			class="button button-primary"
			@click="addNew"
			style="margin-top: 10px;"
		>
			<span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
			{{ __( 'Add Supplemental Material', 'pressbooks' ) }}
		</button>
	</div>

	@if(isset($field->description))
	<p class="description" id="{{ $field->id }}-description">{!! $field->description !!}</p>
	@endif
</div>

