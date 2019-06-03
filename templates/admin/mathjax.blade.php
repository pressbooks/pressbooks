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
                        <h2>{{ __( 'AsciiMath' ,'pressbooks' ) }}</h2>
                        <p>{!! sprintf(
                            __( 'Shortcode syntax: %s', 'pressbooks' ),
	                        '<code>[math]e^{i \pi} + 1 = 0[/math]</code>'
                        ) !!} </p>
                        <p>{!! sprintf(
                           __( 'Dollar sign syntax: %s', 'pressbooks' ),
	                         '<code>$math e^{i \pi} + 1 = 0$</code>'
					    ) !!} </p>
                    </section>
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
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mathjax-url">{{ __( 'PB MathJax URL', 'pressbooks' ) }}</label></th>
                <td>
                    <input class='large-text' type='url' name='pb_mathjax_url' value='{{ $pb_mathjax_url }}' id='mathjax-url'/>
                    <p>{!! sprintf(
                            __( 'URL to your <a href="%s">PB-MathJax Microservice</a>', 'pressbooks' ),
	                         'https://github.com/pressbooks/pb-mathjax'
					    ) !!} </p>
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
                <th scope="row"><label for="mathjax-bg">{{ __( 'Background color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='bg' value='{{ $bg }}' id='mathjax-bg'/>
                    <p>{!!__( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>, or <code>transparent</code>', 'pressbooks' ) !!}</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mathjax-fontsize">{{ __( 'Font size', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='fontsize' value='{{ $fontsize }}' id='mathjax-fontsize'/>
                    <p>{!!__( 'A CSS value for <code>font-size</code>', 'pressbooks' ) !!}</p>
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="{{ __( 'Update MathJax Options', 'pressbooks' ) }}"/>
            {!! $wp_nonce_field !!}
        </p>
    </form>
</div>