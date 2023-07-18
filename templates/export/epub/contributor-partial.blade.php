@php
$type = $role === 'ctb' ? 'contributor' : 'creator';
$contributor_number = str_pad( $index, 2, '0', STR_PAD_LEFT );
@endphp

@if( $role === 'ctb' )
	<dc:contributor id="{{ $type . '-' . $contributor_number }}">{!! $contributor !!}</dc:contributor>
@else
	<dc:creator id="{{ $type . '-' . $contributor_number }}">{!! $contributor !!}</dc:creator>
@endif
<meta refines="#{{ $type . '-' . $contributor_number }}" property="role" scheme="marc:relators">{{ $role }}</meta>
