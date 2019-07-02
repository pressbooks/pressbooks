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
                        <p>{!! sprintf(
                           __( 'Shortcode syntax: %s', 'pressbooks' ),
	                         '<code>[latex]e^{i \pi} + 1 = 0[/latex]</code>'
					    ) !!} </p>
                        <p>{!! sprintf(
                           __( 'Dollar sign syntax: %s', 'pressbooks' ),
	                         '<code>$latex e^{i \pi} + 1 = 0$</code>'
					    ) !!} </p>
                    </section>
                    <section>
                        <h2>{{ __( 'AsciiMath' ,'pressbooks' ) }}</h2>
                        <p>{!! sprintf(
                            __( 'Shortcode syntax: %s', 'pressbooks' ),
	                        '<code>[asciimath]e^{i \pi} + 1 = 0[/asciimath]</code>'
                        ) !!} </p>
                        <p>{!! sprintf(
                           __( 'Dollar sign syntax: %s', 'pressbooks' ),
	                         '<code>$asciimath e^{i \pi} + 1 = 0$</code>'
					    ) !!} </p>
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
                <th scope="row"><label for="mathjax-fg">{{ __( 'Text color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='fg' value='{{ $fg }}' id='mathjax-fg'/>
                    <p>{!!  __( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>', 'pressbooks' )  !!}</p>
                </td>
            </tr>
            <tr>
                <!-- TODO: Use foreach and $this->possibleFonts -->
                <th scope="row"><label for="mathjax-font">{{ __('SVG/PNG Fonts', 'pressbooks') }}</label></th>
                <td><select name="font" id="mathjax-font">
                        @foreach ($possible_fonts as $possible_font)
                            <option value="{!! $possible_font !!}" {!! selected( $font, $possible_font ) !!} >{{ $possible_font }}</option>
                        @endforeach
                    </select>
                    <p>{!!  __( 'Affects exports (PDF, EPUB, MOBI.) Webbook uses CommonHTML. CommonHTML currently only supports MathJax’s default TeX fonts.', 'pressbooks' )  !!}</p>
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