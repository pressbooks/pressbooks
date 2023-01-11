<div class="wrap @if ($can_edit_others_posts) allow-bulk-operations @endif">
    <div aria-live="assertive" role="alert" class="visually-hidden">
        <span class="spinner"></span>
        <p class="message"></p>
    </div>

    @if (apply_filters('pb_permissive_webbooks', true) && $can_manage_options)
        <div id="publicize-panel" class="postbox">
            <div class="inside">
                @if ($book_is_public)
                    <h4 class="publicize-alert public">{{ __('This book’s global privacy is set to', 'pressbooks') }}
                        <span>{{ __('Public', 'pressbooks') }}</span>
                    </h4>
                @else
                    <h4 class="publicize-alert private">{{ __('This book’s global privacy is set to', 'pressbooks') }}
                        <span>{{ __('Private', 'pressbooks') }}</span>
                    </h4>
                @endif
                <div class="publicize-form">
                    <label for="blog-public"><input type="radio" {{ checked($book_is_public, 1, false) }}
                            value="1" name="blog_public" id="blog-public"><span
                            class="public">{{ __('Public', 'pressbooks') }}</span> &mdash;
                        {!! sprintf(
                            __(
                                'Anyone with the link can see your book. Public books are eligible to be listed in <a href="%s">Pressbooks Directory</a>. Individual chapters can be set to private.',
                                'pressbooks',
                            ),
                            esc_url('https://pressbooks.directory'),
                        ) !!}
                    </label>
                    <label for="blog-private"><input type="radio" {{ checked($book_is_public, 0, false) }}
                            value="0" name="blog_public" id="blog-private"><span
                            class="private">{{ __('Private', 'pressbooks') }}</span> &mdash;
                        {{ __('Only users you invite can see your book, regardless of individual chapter visibility below.', 'pressbooks') }}
                    </label>
                </div>
            </div>
        </div>
    @endif
    <h1 class="wp-heading-inline">{{ get_bloginfo('name') }}</h1>
    @if (is_super_admin())
        <div class="page-title-actions">
            <a class="page-title-action" href="{!! admin_url('edit.php?post_type=front-matter') !!}">{{ __('Front Matter', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('edit.php?post_type=chapter') !!}">{{ __('Chapters', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('edit.php?post_type=back-matter') !!}">{{ __('Back Matter', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('edit.php?post_type=part') !!}">{{ __('Parts', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('edit.php?post_type=glossary') !!}">{{ __('Glossary', 'pressbooks') }}</a>
        </div>
    @elseif ($can_edit_posts)
        <div class="page-title-actions">
            <a class="page-title-action" href="{!! admin_url('admin.php?page=pb_export') !!}">{{ __('Export', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('post-new.php?post_type=front-matter') !!}">{{ __('Add Front Matter', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('post-new.php?post_type=back-matter') !!}">{{ __('Add Back Matter', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('post-new.php?post_type=chapter') !!}">{{ __('Add Chapter', 'pressbooks') }}</a>
            <a class="page-title-action" href="{!! admin_url('post-new.php?post_type=part') !!}">{{ __('Add Part', 'pressbooks') }}</a>
            <a class="page-title-action"
                href="{!! admin_url('post-new.php?post_type=glossary') !!}">{{ __('Add Glossary Term', 'pressbooks') }}</a>
        </div>
    @endif
    <p class="word-count">
        <strong>{{ __('Word Count:', 'pressbooks') }}</strong> {!! sprintf(__('%s (whole book)', 'pressbooks'), "<span id='wc-all'>$wc</span>") !!} /
        {!! sprintf(
            __('%s (selected for export)', 'pressbooks'),
            "<span id='wc-selected-for-export'>$wc_selected_for_export</span>",
        ) !!}
    </p>

    @foreach ($structure as $slug => $group)
        <div role="region" aria-labelledby="{{ $slug }}">
            <h2 class="wp-heading-inline">
                @if (!str_contains($slug, 'part'))
                    {{ $group['name'] }}
                @else
                    {{ $group['title'] }}
                @endif
            </h2>
            @if ($can_edit_posts)
                <div class="page-title-actions">
                    @if (str_contains($slug, 'part'))
						<a class="page-title-action" href="{!! admin_url("post-new.php?post_type={$group['abbreviation']}&startparent={$group['id']}") !!}">
							{{ __('Add', 'pressbooks') }} {{ $group['name'] }}
						</a>
                        <a class="page-title-action" href="{!! admin_url('post-new.php?post_type=part') !!}">
							{{ __('Add Part', 'pressbooks') }}
						</a>
					@else
						<a class="page-title-action" href="{!! admin_url('post-new.php?post_type=' . $slug) !!}">
							{{ __('Add', 'pressbooks') }} {{ $group['name'] }}
						</a>
                    @endif
                </div>
            @endif
            @if (str_contains($slug, 'part'))
                <div class="part-actions">
                    @if (current_user_can('edit_post', $group['id']))
                        <a href="{!! admin_url('post.php?post=' . $group['id'] . '&action=edit') !!}">{!! sprintf(__('Edit​%s', 'pressbooks'), '<span class="screen-reader-text"> ' . $group['title'] . '</span>') !!}</a> |
                    @endif
                    @if ($parts > 1 && current_user_can('delete_post', $group['id']))
                        <a class="delete-link" href="{!! get_delete_post_link($group['id']) !!}">{!! sprintf(__('Trash​%s', 'pressbooks'), '<span class="screen-reader-text"> ' . $group['title'] . '</span>') !!}</a> |
                    @endif
                    <a href="{!! get_permalink($group['id']) !!}">{!! sprintf(__('View​%s', 'pressbooks'), '<span class="screen-reader-text"> ' . $group['title'] . '</span>') !!}</a>
                </div>
            @endif
            <table id="{{ $slug }}"
                class="wp-list-table widefat fixed striped {{ str_contains($slug, 'part') ? 'chapters' : $slug }}">
                <caption class="screen-reader-text">
                    @if (!str_contains($slug, 'part'))
                        {{ $group['name'] }}
                    @else
                        {{ $group['title'] }}
                    @endif
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('Authors', 'pressbooks') }}</th>
                        @if (false === $disable_comments)
                            <th scope="col">{{ __('Comments', 'pressbooks') }}</th>
                        @endif
                        <th scope="col"><button class="button"
                                id="{{ str_contains($slug, 'part') ? 'chapters' : $slug }}_web_visibility"
                                type="button" aria-pressed="false">{{ __('Show in Web', 'pressbooks') }}</button></th>
                        <th scope="col"><button class="button"
                                id="{{ str_contains($slug, 'part') ? 'chapters' : $slug }}_export_visibility"
                                type="button" aria-pressed="false">{{ __('Show in Exports', 'pressbooks') }}</button>
                        </th>
                        <th scope="col"><button class="button"
                                id="{{ str_contains($slug, 'part') ? 'chapters' : $slug }}_show_title" type="button"
                                aria-pressed="false">{{ __('Show Title', 'pressbooks') }}</button></th>
                    </tr>
                </thead>

                <tbody id="the-list-{{ $slug }}">
                    @foreach ($group['items'] as $content)
                        <tr id="{{ $slug }}_{{ $content['ID'] }}">
                            <td class="title column-title has-row-actions">
                                <div class="row-title">
                                    @if (current_user_can('edit_post', $content['ID']))
                                        <a href="{!! admin_url('post.php?post=' . $content['ID'] . '&action=edit') !!}">
                                            {{ $content['post_title'] }}
                                            @if ($start_point === $content['ID'])
                                                <span class="ebook-start-point"
                                                    title="{{ __('Ebook start point', 'pressbooks') }}">&#9733;</span>
                                            @endif
                                        </a>
                                    @else
                                        {{ $content['post_title'] }}
                                        @if ($start_point === $content['ID'])
                                            <span class="ebook-start-point"
                                                title="{{ __('Ebook start point', 'pressbooks') }}">&#9733;</span>
                                        @endif
                                    @endif

                                    <div class="row-actions">
                                        @if (current_user_can('edit_post', $content['ID']))
                                            <a href="{!! admin_url('post.php?post=' . $content['ID'] . '&action=edit') !!}">{!! sprintf(
                                                __('Edit​%s', 'pressbooks'),
                                                '<span class="screen-reader-text"> ' . $content['post_title'] . '</span>',
                                            ) !!}</a> |
                                        @endif
                                        @if (current_user_can('delete_post', $content['ID']))
                                            <a class="delete-link"
                                                href="{!! get_delete_post_link($content['ID']) !!}">{!! sprintf(
                                                    __('Trash%s', 'pressbooks'),
                                                    '<span class="screen-reader-text"> ' . $content['post_title'] . '</span>',
                                                ) !!}</a> |
                                        @endif
                                        <a href="{!! get_permalink($content['ID']) !!}">{!! sprintf(
                                            __('View%s', 'pressbooks'),
                                            '<span class="screen-reader-text"> ' . $content['post_title'] . '</span>',
                                        ) !!}</a>
                                        @if ($can_edit_others_posts)
                                            @if ($loop->iteration > 1 ||
                                                ($group['index'] > 1 && $parts > 1) ||
                                                $loop->iteration < count($group['items']) ||
                                                $group['index'] < $parts)
                                                <span class="reorder">
                                                    @if ($loop->iteration > 1 || ($group['index'] > 1 && $parts > 1))
                                                        | <button class="move-up">{!! sprintf(
                                                            __('Move%s Up', 'pressbooks'),
                                                            '<span class="screen-reader-text"> ' . $content['post_title'] . '</span>',
                                                        ) !!}</button>
                                                    @endif
                                                    @if ($loop->iteration < count($group['items']) || $group['index'] < $parts)
                                                        | <button class="move-down">{!! sprintf(
                                                            __('Move%s Down', 'pressbooks'),
                                                            '<span class="screen-reader-text"> ' . $content['post_title'] . '</span>',
                                                        ) !!}</button>
                                                    @endif
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="author column-author">
                                <span class="author-label">{{ __('Authors', 'pressbooks') }}:</span>
                                {{ $contributors->get($content['ID'], 'pb_authors') ?: '—' }}
                            </td>
                            @if (!$disable_comments)
                                <td class="comments column-comments">
                                    <a class="post-comment-count" href="{!! admin_url('edit-comments.php?p=' . $content['ID']) !!}">
                                        <span class="comment-count">{{ $content['comment_count'] }}</span><span
                                            class="screen-reader-text">
                                            {{ _n('comment', 'comments', $content['comment_count'], 'pressbooks') }}</span>
                                    </a>
                                </td>
                            @endif
                            <td class="visibility column-web ">
                                <input class="web_visibility toggle" type="checkbox" data-id="{{ $content['ID'] }}"
                                    name="web_visibility_[{{ $content['ID'] }}]"
                                    id="web_visibility_{{ $content['ID'] }}"
                                    {{ checked(true, in_array($content['post_status'], ['web-only', 'publish'], true), false) }}
                                    {{ !current_user_can('publish_post', $content['ID']) ? 'disabled' : '' }}>
                                <label
                                    for="web_visibility_{{ $content['ID'] }}">{{ sprintf(__('Show %s in Web', 'pressbooks'), $content['post_title']) }}</label>
                            </td>
                            <td class="visibility column-export">
                                <input class="export_visibility toggle" type="checkbox"
                                    data-id="{{ $content['ID'] }}" name="export_visibility_[{{ $content['ID'] }}]"
                                    id="export_visibility_{{ $content['ID'] }}"
                                    {{ checked(true, in_array($content['post_status'], ['private', 'publish'], true), false) }}
                                    {{ !current_user_can('publish_post', $content['ID']) ? 'disabled' : '' }}>
                                <label
                                    for="export_visibility_{{ $content['ID'] }}">{{ sprintf(__('Show %s in Exports', 'pressbooks'), $content['post_title']) }}</label>
                            </td>
                            <td class="export column-showtitle">
                                <input class="show_title toggle" type="checkbox" data-id="{{ $content['ID'] }}"
                                    name="show_title_[{{ $content['ID'] }}]" id="show_title_{{ $content['ID'] }}"
                                    {{ checked(get_post_meta($content['ID'], 'pb_show_title', true), 'on', false) }}
                                    {{ !current_user_can('edit_post', $content['ID']) ? 'disabled' : '' }}>
                                <label
                                    for="show_title_{{ $content['ID'] }}">{{ sprintf(__('Show title for %s', 'pressbooks'), $content['post_title']) }}</label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
