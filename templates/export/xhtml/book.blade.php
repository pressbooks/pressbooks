<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>
@inject('s', '\Pressbooks\Modules\Export\Xhtml\Blade')
@php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; @endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{{ $lang }}">
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type"/>
    <meta http-equiv="Content-Language" content="{{ $lang }}"/>
    <meta name="generator" content="Pressbooks {{ $pb_plugin_version }}"/>
    @foreach ($metadata as $key => $val)
        <meta name="{{ $s->sanitizeHtmlMetaKey($key) }}" content="{{ $s->sanitizeHtmlMetaVal($val) }}"/>
    @endforeach
    <title>{{ $title }}</title>
    @isset($style_url)
        <link rel='stylesheet' href='{{ $style_url }}' type='text/css'/>
    @endisset
    @isset($script_url)
        <script src='{{ $script_url }}' type='text/javascript'></script>
    @endisset
</head>
<body lang="{{ $lang }}">
@include('export.xhtml.before-title')
@include('export.xhtml.half-title')
@include('export.xhtml.title')
@include('export.xhtml.copyright')
{{-- echoDedicationAndEpigraph --}}
{{-- echoToc --}}
{{-- echoFrontMatter --}}
{{-- createPromo --}}
{{-- echoPartsAndChapters --}}
{{-- echoBackMatter --}}
</body>
</html>