// Registers every server-rendered hhv/* block in the editor with a live ServerSideRender preview.
( function ( wp ) {
	var el = wp.element.createElement;
	( window.hhvBlocks || [] ).forEach( function ( name ) {
		wp.blocks.registerBlockType( name, {
			edit: function ( props ) {
				return el( 'div', wp.blockEditor.useBlockProps(), el( wp.serverSideRender, { block: name, attributes: props.attributes, urlQueryArgs: { post_id: props.context && props.context.postId } } ) );
			},
			save: function () {
				return null;
			},
		} );
	} );
} )( window.wp );
