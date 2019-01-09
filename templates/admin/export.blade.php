<?php /** @var \Pressbooks\Modules\Export\Table $table */ ?>
@inject('table', '\Pressbooks\Modules\Export\Table')
<div class="wrap">
    <h2>{{ __( 'Export', 'pressbooks') }}</h2>
    <p>{{ __( 'You can select multiple formats below. Pressbooks keeps your last three exports in each export format. You can pin specific files to make sure they don\'t get deleted', 'pressbooks') }}</p>
    <!-- TODO: New export interface goes here -->
    <div class="clear"></div>
    <h2>{{ __( 'Latest Exports', 'pressbooks') }}</h2>
    {!! $table->prepare_items() !!}
        <form method="POST">
            {!! $table->display() !!}
        </form>
</div>