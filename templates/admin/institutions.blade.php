<div class="custom-metadata-field institutions">
    <label for="pb-institutions">{{ __( 'Institutions', 'pressbooks' ) }}</label>
    <select id="pb-institutions" name="pb_institutions[]" multiple>
        @foreach ( $institutions as $institution )
            <option value="{{ $institution }}" selected>{{ \Pressbooks\Metadata\get_institution_name( $institution ) }}</option>
        @endforeach
    </select>
</div>
