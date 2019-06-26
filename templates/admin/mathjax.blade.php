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
                <th scope="row"><label for="mathjax-font">{{ __('SVG/PNG Fonts', 'pressbooks') }}</label></th>
                <td><select name="font" id="mathjax-font">
                        <option value="TeX" {!! selected( $font, 'TeX' ) !!} >TeX</option>
                        <option value="STIX-Web" {!! selected( $font, 'STIX-Web' ) !!} >STIX-Web</option>
                        <option value="Asana-Math" {!! selected( $font, 'Asana-Math' ) !!} >Asana-Math</option>
                        <option value="Neo-Euler" {!! selected( $font, 'Neo-Euler' ) !!} >Neo-Euler</option>
                        <option value="Gyre-Pagella" {!! selected( $font, 'Gyre-Pagella' ) !!} >Gyre-Pagella</option>
                        <option value="Gyre-Termes" {!! selected( $font, 'Gyre-Termes' ) !!} >Gyre-Termes</option>
                        <option value="Latin-Modern" {!! selected( $font, 'Latin-Modern' ) !!} >Latin-Modern</option>
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