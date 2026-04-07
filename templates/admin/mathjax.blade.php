<div class="wrap">
    <h1>{{ __( 'MathJax', 'pressbooks' ) }}</h1>
    {!! $test_image !!}
    <p class='test-image'> {{ __( 'If you can see a big integral, then PB-MathJax is configured correctly, and all is well.', 'pressbooks' ) }} </p>
    <form action="" method="post">
        <table class="form-table" role="none">
            <tbody>
            <tr>
                <th scope="row">{{ __( 'Syntax', 'pressbooks' ) }}</th>
                <td class="syntax">
                    <section>
                        <h2>{{ __( 'LaTeX' ,'pressbooks' ) }}</h2>
                        <p>
                            {!! sprintf( __( 'Inline math delimiter: %s', 'pressbooks' ), '<code>\( e^{i \pi} + 1 = 0 \)</code>') !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Display math delimiter: %s', 'pressbooks' ), '<code>\[ e^{i \pi} + 1 = 0 \]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Shortcode syntax: %s', 'pressbooks' ), '<code>[latex]e^{i \pi} + 1 = 0[/latex]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Double dollar sign syntax: %s', 'pressbooks' ), '<code>$$ e^{i \pi} + 1 = 0 $$</code>' ) !!}
                        </p>
                        @if ( $use_single_dollar )
                        <p>
                            {!! sprintf( __( 'Single dollar sign syntax: %s', 'pressbooks' ), '<code>$e^{i \pi} + 1 = 0$</code>' ) !!}
                        </p>
                        @endif
                    </section>
                    <section>
                        <h2>{{ __( 'AsciiMath' ,'pressbooks' ) }}</h2>
                        <p>
                            {!! sprintf( __( 'Shortcode syntax: %s', 'pressbooks' ),'<code>[asciimath]e^{i \pi} + 1 = 0[/asciimath]</code>' ) !!}
                        </p>
                        <p>
                            {!! sprintf( __( 'Dollar sign syntax: %s', 'pressbooks' ),'<code>$asciimath e^{i \pi} + 1 = 0$</code>' ) !!}
                        </p>
                    </section>
                    <section>
                        <h2>{{ __( 'MathML' ,'pressbooks' ) }}</h2>
                        <p>{!! sprintf(
                            __( 'Markup syntax: %s', 'pressbooks' ),
                            '<code>&lt;math&gt;&lt;!-- Your math here --&gt;&lt;/math&gt;</code>'
                        ) !!} </p>
                    </section>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="mathjax-use-single-dollar">{{ __( 'Single dollar sign delimiter', 'pressbooks' ) }}</label>
                </th>
                <td>
                    <input type="checkbox" name="use_single_dollar" id="mathjax-use-single-dollar"
                           value="1" @if ( $use_single_dollar ) checked @endif />
                    <p>
                        {!! sprintf( __( 'When enabled, %1$s will be treated as inline LaTeX math. Use %2$s to display a literal dollar sign.', 'pressbooks'	), '<code>$e^{i \\pi} + 1 = 0$</code>', '<code>\\$</code>' ) !!}
                    </p>
                    <p>
                        {{ __( 'Note: The opening $ must not be followed by a space and the closing $ must not be preceded by a space.', 'pressbooks' ) }}
                    </p>
                    <p>
                        {{ __( 'The opening $ must not be followed by a space and the closing $ must not be preceded by a space, so currency like "$ 5,000" is not affected.', 'pressbooks' ) }}
                        {!! sprintf( __( 'Use %s to display a literal dollar sign.', 'pressbooks' ), '<code>\$</code>' ) !!}
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mathjax-fg">{{ __( 'Text color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='fg' value='{{ $fg }}' id='mathjax-fg'/>
                    <p>{!!  __( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>', 'pressbooks' )  !!}</p>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="{{ __( 'Save Changes', 'pressbooks' ) }}"/>
            {!! $wp_nonce_field !!}
        </p>
    </form>
</div>
