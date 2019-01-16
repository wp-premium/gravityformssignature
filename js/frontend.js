jQuery( document ).ready( function( $ ) {

	$( window ).on( 'load', function() {

		$( '.gfield_signature_container' ).each( function() {

			var width  = parseFloat( $( this ).css( 'width' ) ),
				height = parseFloat( $( this ).css( 'height' ) );

			$( this ).data( 'ratio', height / width );
			$( this ).data( 'original-width', width );

		} );

	} );

	$( window ).on( 'load resize', function() {

		$( '.gfield_signature_container' ).each( function() {

			var originalWidth  = $( this ).data( 'original-width' ),
				originalHeight = parseFloat( $( this ).css( 'height' ) ),
				containerWidth = $( this ).closest( '.gfield' ).innerWidth();

			var width  = containerWidth > originalWidth ? originalWidth : containerWidth,
				height = Math.round( width * $( this ).data( 'ratio' ) );

			// Get container ID.
			var containerID = $( this ).parent().parent().find( '.gfield_label' ).attr( 'for' );

			// Get data element.
			var $data = $( this ).parent().find( 'input[name$="_data"]:eq( 0 )' );

			if ( $data.val().length > 0 ) {

				var data = $data.val();
				    data = Base64.decode( data );
				    data = data.replace( originalWidth + ',' + originalHeight, width + ',' + height );

			}

			// Resize signature.
			ResizeSignature( containerID, width, height );
			ClearSignature( containerID );

			if ( data ) {
				LoadSignature( containerID, data, 1 );
			}

		} );

	} );

} );
