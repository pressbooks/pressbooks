<div class="wrap @if($can_edit_others_posts) allow-bulk-operations @endif">
	<div aria-live="assertive" role="alert" class="visually-hidden">
		<span class="spinner"></span>
		<p class="message"></p>
	</div>

	@if (apply_filters( 'pb_permissive_webbooks', true ) && $can_manage_options )
	<div id="publicize-panel" class="postbox">
		<div class="inside">
			@if($book_is_public)
			<h4 class="publicize-alert public">{{ __( 'This book’s global privacy is set to', 'pressbooks' ) }} <span>{{ __( 'Public', 'pressbooks' ) }}</span></h4>
			@else
			<h4 class="publicize-alert private">{{ __( 'This book’s global privacy is set to', 'pressbooks' ) }} <span>{{ __( 'Private', 'pressbooks' ) }}</span></h4>
			@endif
			<div class="publicize-form">
				<label for="blog-public"><input type="radio" {{ checked( $book_is_public, 1, false ) }} value="1" name="blog_public" id="blog-public"><span class="public">{{ __( 'Public', 'pressbooks' ) }}</span> &mdash;
					{!! sprintf(
						__('Anyone with the link can see your book. Public books are eligible to be listed in <a href="%s">Pressbooks Directory</a>. Individual chapters can be set to private.', 'pressbooks'),
						esc_url('https://pressbooks.directory')
					) !!}
				</label>
				<label for="blog-private"><input type="radio" {{ checked( $book_is_public, 0, false ) }} value="0" name="blog_public" id="blog-private"><span class="private">{{ __( 'Private', 'pressbooks' ) }}</span> &mdash;
					{{ __( 'Only users you invite can see your book, regardless of individual chapter visibility below.', 'pressbooks' ) }}
				</label>
			</div>
		</div>
	</div>
	@endif
	<h1 class="wp-heading-inline">{{ get_bloginfo( 'name' ) }}</h1>
		@if ( is_super_admin() )
		<div class="page-title-actions">
			<a class="page-title-action" href="{{ admin_url( 'edit.php?post_type=front-matter' ) }}">{{ __( 'Front Matter', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'edit.php?post_type=chapter' ) }}">{{ __( 'Chapters', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'edit.php?post_type=back-matter' ) }}">{{ __( 'Back Matter', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'edit.php?post_type=part' ) }}">{{ __( 'Parts', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'edit.php?post_type=glossary' ) }}">{{ __( 'Glossary', 'pressbooks' ) }}</a>
		</div>
		@elseif ( $can_edit_posts )
		<div class="page-title-actions">
			<a class="page-title-action" href="{{ admin_url( 'admin.php?page=pb_export' ) }}">{{ __( 'Export', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'post-new.php?post_type=front-matter' ) }}">{{ __( 'Add Front Matter', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'post-new.php?post_type=back-matter' ) }}">{{ __( 'Add Back Matter', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'post-new.php?post_type=chapter' ) }}">{{ __( 'Add Chapter', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'post-new.php?post_type=part' ) }}">{{ __( 'Add Part', 'pressbooks' ) }}</a>
			<a class="page-title-action" href="{{ admin_url( 'post-new.php?post_type=glossary' ) }}">{{ __( 'Add Glossary Term', 'pressbooks' ) }}</a>
		</div>
		@endif
	<p class="word-count">
		<strong>{{ __( 'Word Count:', 'pressbooks' ) }}</strong> {!! sprintf( __( '%s (whole book)', 'pressbooks' ), "<span id='wc-all'>$wc</span>" ) !!} /
		{!! sprintf( __( '%s (selected for export)', 'pressbooks' ), "<span id='wc-selected-for-export'>$wc_selected_for_export</span>" ) !!}
	</p>

	@foreach ($types as $slug => $type)
		@if ('chapter' === $slug)
			@foreach ( $book_structure['part'] as $part )
				<table id="part_{{ $part['ID'] }}" class="wp-list-table widefat fixed striped chapters">
					<thead>
						<tr>
							<th scope="col" id="title_{{ $part['ID'] }}" class="has-row-actions manage-column column-title column-primary">
								@if (  current_user_can( 'edit_post', $part['ID'] ) )
									<a href="{{ admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ) }}">{{ $part['post_title'] }}</a>
								@else
									{{ $part['post_title'] }}
								@endif
								<div class="row-actions">
									@if (  current_user_can( 'edit_post', $part['ID'] ) )
									<a href="{{ admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ) }}">{{ __( 'Edit', 'pressbooks' ) }}</a> | @endif
									@if ( count( $book_structure['part'] ) > 1 && current_user_can( 'delete_post', $part['ID'] ) )
									<a class="delete-link" href="{{ get_delete_post_link( $part['ID'] ) }}">{{ __( 'Trash', 'pressbooks' ) }}</a> | @endif
									<a href="{{ get_permalink( $part['ID'] ) }}">{{ __( 'View', 'pressbooks' ) }}</a>
								</div>
							</th>
							<th tabindex='0'>{{ __( 'Authors', 'pressbooks' ) }}</th>
							@if(!$disable_comments )
								<th>{{ __( 'Comments', 'pressbooks' ) }}</th>
							@endif
							<th>
								<span role="button" tabindex='0' aria-label="check/uncheck Show in Web for all {{ $type['name'] }} pages in this Part" id="{{ $slug }}_web_visibility">{{ __( 'Show in Web', 'pressbooks' ) }}</span>
							</th>
							<th>
								<span
									role="button"
									tabindex="0"
									aria-label="check/uncheck Show in Exports for all {{ $type['name'] }} pages in this Part"
									id="part_{{ $part['ID'] }}_chapter_export_visibility">{{ __( 'Show in Exports', 'pressbooks' ) }}</span>
							</th>
							<th>
								<span
									role="button"
									tabindex="0"
									aria-label="check/uncheck Show Title for all {{ $type['name'] }} pages in this Part"
									id="part_{{ $part['ID'] }}_chapter_show_title" role="button">{{ __( 'Show Title', 'pressbooks' ) }}
								</span>
							</th>
						</tr>
					</thead>

					@if ( count( $part['chapters'] ) > 0 )
						<tbody id="the-list-{{ $part['ID'] }}">
							@foreach( $part['chapters'] as $content )
							<tr id="chapter_{{ $content['ID'] }}">
								<td class="title column-title has-row-actions">
									<div class="row-title">
										@if ( current_user_can( 'edit_post', $content['ID'] ) )
											<a href="{{ admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ) }}">
												{{ $content['post_title'] }}
												@if ( $start_point === $content['ID'] )
												<span class="ebook-start-point" title="{{ __( 'Ebook start point', 'pressbooks' ) }}">&#9733;</span>
												@endif
											</a>
										@else
											{{ $content['post_title'] }}
											@if ( $start_point === $content['ID'] )
												<span class="ebook-start-point" title="{{ __( 'Ebook start point', 'pressbooks' ) }}">&#9733;</span>
											@endif
										@endif

										<div class="row-actions">
											@if ( current_user_can( 'edit_post', $content['ID'] ) )<a href="{{ admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ) }}">{{ __( 'Edit', 'pressbooks' ) }}</a> | @endif
											@if ( current_user_can( 'delete_post', $content['ID'] ) )<a class="delete-link" href="{{ get_delete_post_link( $content['ID'] ) }}">{{ __( 'Trash', 'pressbooks' ) }}</a> | @endif
											<a href="{{ get_permalink( $content['ID'] ) }}">{{ __( 'View', 'pressbooks' ) }}</a>
											@if( $can_edit_others_posts )
												@if ( $loop->iteration > 1 || ( $loop->parent->iteration > 1 && $parts > 1 ) || $loop->iteration < count( $part['chapters'] ) || $loop->parent->iteration < $parts )
													<span class="reorder">
														@if ( $loop->iteration > 1 || ( $loop->parent->iteration > 1 && $parts > 1 ) ) | <button class="move-up">{{ __( 'Move Up', 'pressbooks' ) }}</button>@endif
														@if ( $loop->iteration < count( $part['chapters'] ) || $loop->parent->iteration < $parts ) | <button class="move-down">{{ __( 'Move Down', 'pressbooks' ) }}</button>@endif
													</span>
												@endif
											@endif
										</div>
									</div>
								</td>
								<td class="author column-author">
								<span class="author-label">{{ __( 'Authors', 'pressbooks' ) }}:</span>
									{{ $contributors->get( $content['ID'], 'pb_authors' ) ?: '—' }}
								</td>
								@if(!$disable_comments )
								<td class="comments column-comments">
									<a class="post-comment-count" href="{{ admin_url( 'edit-comments.php?p=' . $content['ID'] ) }}">
										<span class="comment-count">{{ $content['comment_count'] }}</span>
									</a>
								</td>
								@endif
								<td class="visibility column-web">
									<input class="web_visibility" type="checkbox" data-id="{{ $content['ID'] }}" name="web_visibility_[{{ $content['ID'] }}]" id="web_visibility_{{ $content['ID'] }}" {{ checked( true, in_array( $content['post_status'], [ 'web-only', 'publish' ], true ), false ) }} {{ !current_user_can( 'publish_post', $content['ID'] ) ? 'disabled' : '' }}>
									<label for="web_visibility_{{ $content['ID'] }}">{{ sprintf(__( 'Show %s in Web', 'pressbooks' ), $content['post_title']) }}</label>
								</td>
								<td class="visibility column-export">
									<input class="export_visibility" type="checkbox" data-id="{{ $content['ID'] }}" name="export_visibility_[{{ $content['ID'] }}]" id="export_visibility_{{ $content['ID'] }}" {{ checked( true, in_array( $content['post_status'], [ 'private', 'publish' ], true ), false ) }} {{ !current_user_can( 'publish_post', $content['ID'] ) ? 'disabled' : '' }}>
									<label for="export_visibility_{{ $content['ID'] }}">{{ sprintf(__( 'Show %s in Exports', 'pressbooks' ), $content['post_title']) }}</label>
								</td>
								<td class="export column-showtitle">
									<input class="show_title" type="checkbox" data-id="{{ $content['ID'] }}" name="show_title_[{{ $content['ID'] }}]" id="show_title_{{ $content['ID'] }}" {{ checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', false ) }} {{ !current_user_can( 'edit_post', $content['ID'] ) ? 'disabled' : '' }}>
									<label for="show_title_{{ $content['ID'] }}">{{ printf(__( 'Show Title for %s', 'pressbooks' ), $content['post_title']) }}</label>
								</td>
							</tr>
						@endforeach
						</tbody>
					@endif
					<tfoot>
						<tr>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							@if(!$disable_comments )
							<th>&nbsp;</th>
							@endif
							<th>&nbsp;</th>
							<th>&nbsp;</th>
							<th>&nbsp;</th>
						</tr>
					</tfoot>
				</table>
				@endforeach
				@if( $can_edit_posts )
				<p class="footer-action"><a href="{{ admin_url( 'post-new.php?post_type=' . $slug . '&startparent=' . $part['ID'] ) }}" class="button">{{ __( 'Add', 'pressbooks' ) }} {{ $type['name'] }}</a></p>
				<p class="footer-action"><a class="button" href="{{ admin_url( 'post-new.php?post_type=part' ) }}">{{ __( 'Add Part', 'pressbooks' ) }}</a></p>
				@endif
		@else
		<table id="{{ $slug }}" class="wp-list-table widefat fixed striped {{ $slug }}">
			<thead>
				<tr>
					<th scope="col" id="title_{{ $slug }}" class="has-row-actions manage-column column-title column-primary">{{ $type['name'] }}</th>
					<th tabindex='0'>{{ __('Authors', 'pressbooks') }}</th>
					@if (false === $disable_comments)
					<th>{{ __('Comments', 'pressbooks') }}</th>
					@endif
					<th>
						<span
							role="button"
							tabindex='0'
							aria-label="check/uncheck Show in Web for all {{ $type['name'] }} pages"
							id="{{ $slug }}_web_visibility">{{ __('Show in Web', 'pressbooks') }}
						</span>
					</th>
					<th>
						<span
							role="button"
							tabindex='0'
							aria-label="check/uncheck Show in Exports for all {{ $type['name'] }} pages"
							id="{{ $slug }}_export_visibility">{{ __('Show in Exports', 'pressbooks') }}
						</span>
					</th>
					<th>
						<span
							role="button"
							tabindex='0'
							aria-label="check/uncheck Show Title for all {{ $type['name'] }} pages"
							id="{{ $slug }}_show_title">{{ __('Show Title', 'pressbooks') }}
						</span>
					</th>
				</tr>
			</thead>

			<tbody id="the-list-{{ $slug }}">
			@foreach( $book_structure[ $slug ] as $content )
				<tr id="{{ $slug }}_{{ $content['ID'] }}">
					<td class="title column-title has-row-actions">
					<div class="row-title">
						@if( current_user_can( 'edit_post', $content['ID'] ) )
							<a href="{{ admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ) }}">
								{{ $content['post_title'] }}
								@if( $start_point === $content['ID'] )
								<span class="ebook-start-point" title="{{ __( 'Ebook start point', 'pressbooks' ) }}">&#9733;</span>
								@endif
							</a>
						@else
							{{ $content['post_title'] }}
							@if( $start_point === $content['ID'] )
							<span class="ebook-start-point" title="{{ __( 'Ebook start point', 'pressbooks' ) }}">&#9733;</span>
							@endif
						@endif

						<div class="row-actions">
							@if( current_user_can( 'edit_post', $content['ID'] ) )<a href="{{ admin_url( 'post.php?post=' . $content['ID'] . '&action=edit' ) }}">{{ __( 'Edit', 'pressbooks' ) }}</a> | @endif
							@if( current_user_can( 'delete_post', $content['ID'] ) )<a class="delete-link" href="{{ get_delete_post_link( $content['ID'] ) }}">{{ __( 'Trash', 'pressbooks' ) }}</a> | @endif
							<a href="{{ get_permalink( $content['ID'] ) }}">{{ __( 'View', 'pressbooks' ) }}</a>
							@if ( $can_edit_others_posts )
								@if ( $loop->iteration> 1 || $loop->iteration< count( $book_structure[ $slug ] ) )
									<span class="reorder">
									@if ( $loop->iteration> 1 )| <button class="move-up">{{ __( 'Move Up', 'pressbooks' ) }}</button>@endif
									@if ( $loop->iteration< count( $book_structure[ $slug ] ) )| <button class="move-down">{{ __( 'Move Down', 'pressbooks' ) }}</button>@endif
									</span>
								@endif
							@endif
						</div>
					</div>
					</td>
					<td class="author column-author">
						<span class="author-label">{{ __( 'Authors', 'pressbooks' ) }}:</span>
						{{ $contributors->get( $content['ID'], 'pb_authors' ) ?: '—' }}
					</td>
					@if(!$disable_comments )
					<td class="comments column-comments">
						<a class="post-comment-count" href="{{ admin_url( 'edit-comments.php?p=' . $content['ID'] ) }}">
							<span class="comment-count">{{ $content['comment_count'] }}</span>
						</a>
					</td>
					@endif
					<td class="visibility column-web">
						<input class="web_visibility" type="checkbox" data-id="{{ $content['ID'] }}" name="web_visibility_[{{ $content['ID'] }}]" id="web_visibility_{{ $content['ID'] }}" {{ checked( true, in_array( $content['post_status'], [ 'web-only', 'publish' ], true ), false ) }} {{ !current_user_can( 'publish_post', $content['ID'] ) ? 'disabled' : '' }}>
						<label for="web_visibility_{{ $content['ID'] }}">{{ sprintf(__( 'Show %s in Web', 'pressbooks' ), $content['post_title']) }}</label>
					</td>
					<td class="visibility column-export">
						<input class="export_visibility" type="checkbox" data-id="{{ $content['ID'] }}" name="export_visibility_[{{ $content['ID'] }}]" id="export_visibility_{{ $content['ID'] }}" {{ checked( true, in_array( $content['post_status'], [ 'private', 'publish' ], true ), false ) }} {{ !current_user_can( 'publish_post', $content['ID'] ) ? 'disabled' : '' }}>
						<label for="export_visibility_{{ $content['ID'] }}">{{ sprintf(__( 'Show %s in Exports', 'pressbooks' ), $content['post_title']) }}</label>
					</td>
					<td class="export column-showtitle">
						<input class="show_title" type="checkbox" data-id="{{ $content['ID'] }}" name="show_title_[{{ $content['ID'] }}]" id="show_title_{{ $content['ID'] }}" {{ checked( get_post_meta( $content['ID'], 'pb_show_title', true ), 'on', false ) }} {{ !current_user_can( 'edit_post', $content['ID'] ) ? 'disabled' : '' }}>
						<label for="show_title_{{ $content['ID'] }}">{{ printf(__( 'Show Title for %s', 'pressbooks' ), $content['post_title']) }}</label>
					</td>
				</tr>
				@endforeach
			</tbody>
			<tfoot>
				<tr>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					@if(!$disable_comments )
						<th>&nbsp;</th>
					@endif
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</tfoot>
		</table>
		@if ( $can_edit_posts )
		<p class="footer-action"><a href="{{ admin_url( 'post-new.php?post_type=' . $slug ) }}" class="button">{{ __( 'Add', 'pressbooks' ) }} {{ $type['name'] }}</a></p>
		@endif
		@endif
	@endforeach
</div>
