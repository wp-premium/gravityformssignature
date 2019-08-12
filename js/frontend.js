jQuery( window ).on( 'gform_post_render', function( formId ) {

	jQuery( '.gfield_signature_container' ).each( function() {

		// If original width is already set, exit.
		if ( jQuery( this ).data( 'original-width' ) ) {
			return;
		}

		var width  = parseFloat( jQuery( this ).css( 'width' ) ),
			height = parseFloat( jQuery( this ).css( 'height' ) ),
			containerID = jQuery( this ).parent().parent().find( '.gfield_label' ).attr( 'for' );

		// Force reset button to work even when Signature is disabled.
		var $resetButton = jQuery( '#' + containerID + '_resetbutton' );
		$resetButton.click( function() {
			SignatureEnabled( containerID, true );
			ClearSignature( containerID );
			gformSignatureResize();
		} ).parent().append( '<button type="button" id="' + containerID + '_lockedReset" class="gform_signature_locked_reset" style="display:none;height:24px;cursor:pointer;padding: 0 0 0 1.8em;opacity:0.75;font-size:0.813em;border:0;background: transparent url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NDggNTEyIiBjbGFzcz0idW5kZWZpbmVkIj48cGF0aCBkPSJNNDAwIDIyNGgtMjR2LTcyQzM3NiA2OC4yIDMwNy44IDAgMjI0IDBTNzIgNjguMiA3MiAxNTJ2NzJINDhjLTI2LjUgMC00OCAyMS41LTQ4IDQ4djE5MmMwIDI2LjUgMjEuNSA0OCA0OCA0OGgzNTJjMjYuNSAwIDQ4LTIxLjUgNDgtNDhWMjcyYzAtMjYuNS0yMS41LTQ4LTQ4LTQ4em0tMTA0IDBIMTUydi03MmMwLTM5LjcgMzIuMy03MiA3Mi03MnM3MiAzMi4zIDcyIDcydjcyeiIgY2xhc3M9InVuZGVmaW5lZCIvPjwvc3ZnPg==) no-repeat left center;background-size:16px;">' + gform_signature_frontend_strings.lockedReset + '</button>' );

		// Trigger reset when Locked Reset button is clicked.
		jQuery( '#' + containerID + '_lockedReset' ).click( function() {
			jQuery( this ).hide();
			$resetButton.click();
		} );

		// Hide the status box so that our Locked Reset button display left-aligned.
		jQuery( '#' + containerID + '_status' ).hide();

		jQuery( this ).data( 'ratio', height / width );
		jQuery( this ).data( 'original-width', width );

	} );

} );

jQuery( document ).ready( function( $ ) {

	$( window ).on( 'load resize', function() {
		gformSignatureResize();
	} );

} );

function gformSignatureResize() {

	$( '.gfield_signature_container' ).each( function() {

		var originalWidth  = $( this ).data( 'original-width' ),
			containerWidth = $( this ).closest( '.gfield' ).innerWidth(),
			width          = containerWidth > originalWidth ? originalWidth : containerWidth,
			ratio          = $( this ).data( 'ratio' ),
			height         = Math.round( width * ratio );

		var containerID = $( this ).parent().parent().find( '.gfield_label' ).attr( 'for' ),
			data        = Base64.decode( $( this ).parent().find( 'input[name$="_data"]:eq( 0 )' ).val() );

		if( data && width < originalWidth ) {
			SignatureEnabled( containerID, false );
			$( '#' + containerID + '_lockedReset' ).show();
			return;
		}

		// Resize signature.
		ResizeSignature( containerID, width, height );
		ClearSignature( containerID );

		if ( data ) {
			LoadSignature( containerID, data, 1 );
		}

	} );

}
