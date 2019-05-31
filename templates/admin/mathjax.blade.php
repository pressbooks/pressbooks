<div class="wrap">
    <h1>{{ __( 'MathJax', 'pressbooks' ) }}</h1>
    {!! $test_image !!}
    <p class='test-image'> {{ __( 'If you can see a big integral, then Pressbooks is configured correctly, and all is well.', 'pressbooks' ) }} </p>
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
                <th scope="row"><label for="pb-latex-fg">{{ __( 'Default text color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='fg' value='{{ $fg }}' id='pb-latex-fg'/>
                    {!!  __( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>', 'pressbooks' )  !!}
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="pb-latex-bg">{{ __( 'Default background color', 'pressbooks' ) }}</label></th>
                <td>
                    <input type='text' name='bg' value='{{ $bg }}' id='pb-latex-bg'/>
                    {!!__( 'A six digit hexadecimal number like <code>000000</code> or <code>ffffff</code>, or <code>transparent</code>', 'pressbooks' ) !!}
                </td>
            </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="{{ __( 'Update LaTeX Options', 'pressbooks' ) }}"/>
            {!! $wp_nonce_field !!}
        </p>
    </form>
</div>