/**
 * Keep the the post ID that's being customized appended to the query string
 * of any preview, so that Customizer objects (the individual setting,
 * control, etc.) are created in the preview frame.
 *
 * The preview frame needs to be aware of these so it doesn't deactivate controls
 * in the Customizer's controls pane.
 */
(function( $, api ) {
	/**
	 * Bind to the `ready` event as api.previewer is not available until after that.
	 */
	api.bind( 'ready', function() {
		function getURLParam(oTarget, sVar) {
			return decodeURI(oTarget.search.replace(new RegExp("^(?:.*[&\\?]" + encodeURI(sVar).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
		}
		var postId = getURLParam( window.location, 'custom_css_post_id' );

		var originalValidate = api.previewer.previewUrl.validate;

		/**
		 * Add custom validation logic to append the post ID being customized
		 * to the preview URL.
		 */
		api.previewer.previewUrl.validate = function ( url ) {
			url = originalValidate.call( this, url );
			if ( url ) {
				var pattern = new RegExp( 'custom_css_post_id=[^&]+&?' );
				url = url.replace( /#.*/, '' ); // Remove fragment.
				if ( url.indexOf( '?' ) === -1 ) {
					url += '?';
				} else {
					url += '&';
				}
				// Clear out the query parameter, if it already exists.
				url = url.replace( pattern, '' );
				url += 'custom_css_post_id=' + encodeURIComponent( postId );
			}
			return url;
		};
		api.previewer.previewUrl.set( api.previewer.previewUrl() );
	});

	/**
	 * After the Customizer frame has loaded, create a button that closes the
	 * Customizer and returns to the Edit Post screen.
	 *
	 * The Customizer's default "Save" button is hidden via css. @see css/customize-controls.css
	 */
	$(document).on( 'ready', function() {
		var $button = $('<button type="button" name="back-to-edit-screen" id="back-to-edit-screen" class="button button-primary">Back to Edit screen</button>');
		$('#customize-header-actions').prepend( $button );
		$button.on( 'click', function() {
			window.parent.wp.customize.Loader.saved( true );
			$( '.customize-controls-close' ).trigger( 'click' );
		});
	});

})( jQuery, wp.customize );