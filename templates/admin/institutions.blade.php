<div class="custom-metadata-field institutions">
    <label for="pb-institutions">{{ __( 'Institutions', 'pressbooks' ) }}</label>
    <select id="pb-institutions" name="pb_institutions[]" multiple>
        @foreach ( $institutions as $institution )
            <option value="{{ $institution }}" selected>{{ \Pressbooks\Metadata\get_institution_name( $institution ) }}</option>
        @endforeach
    </select>
    <div class="description">
        {{ __( 'This optional field can be used to display the institution(s) which created this resource. If your college or university is not listed, please contact your network manager.', 'pressbooks' ) }}
    </div>
</div>
