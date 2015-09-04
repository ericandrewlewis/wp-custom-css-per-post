/**
 * Keep the the post ID that's being customized appended to the query string
 * of any preview, so that we can create Customizer objects (the individual setting,
 * control, etc) within the preview frame, and avoid the controls being de-activated
 * in the parent frame.
 */
(function( api ) {
	/**
	 * api.previewer will not be available until the `ready` event is called on
	 * the Customizer event bus.
	 *
	 * @param  {[type]} ) {		debugger;	} [description]
	 * @return {[type]}   [description]
	 */
	api.bind( 'ready', function() {
		function getURLParam(oTarget, sVar) {
			return decodeURI(oTarget.search.replace(new RegExp("^(?:.*[&\\?]" + encodeURI(sVar).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
		}
		var postId = getURLParam( window.location, 'custom_css_post_id' );

		var originalValidate = api.previewer.previewUrl.validate;

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
})( wp.customize );