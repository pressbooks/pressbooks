<div id="{{ $id ?? 'message' }}"
	 role="alert"
	 class="notice notice-{{ $type ?? 'error' }} {{ $dismissible ?? false ? 'is-dismissible' : '' }} {{ $class ?? '' }} fade">
	@if(isset($title))
		<p><strong>{{ esc_html($title) }}</strong></p>
	@endif
	<p>{!! wp_kses_post($message) !!}</p>
</div>
