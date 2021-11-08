export default ( inputSelector, descriptionSelector, descriptionId ) => {
    const input = jQuery( inputSelector );

    input.attr( 'aria-describedby', descriptionId );

    let description = input.parent().find( descriptionSelector );
    description = description.length ? description : input.parent().parent().find( descriptionSelector );

    description.attr( 'id', descriptionId );
}
