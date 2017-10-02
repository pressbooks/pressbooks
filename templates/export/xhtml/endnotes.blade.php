<?php /** @var \Pressbooks\Modules\Export\Xhtml\Blade $s */ ?>
<div class="endnotes">
    <hr/>
    <h3>@php __( 'Notes', 'pressbooks' ) @endphp</h3>
    <ol>
        @foreach($s->endnotes[$endnote_id] as $endnote)
            <li><span>{{ $endnote }}</span></li>
        @endforeach
    </ol>
</div>