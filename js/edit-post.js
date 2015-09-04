
(function( $ ) {
	$(document).on( 'ready', function() {
		/*
		 * Create a custom on-click handler to open the Customizer with a preview
		 * of the current state of the post in the Edit Post page.
		 *
		 * This is similar to the handler wp.customize.Loader.initialize() creates,
		 * but that just opens the Customizer.
		 */
		$('.load-customize-with-post-preview').on( 'click', function() {
			/*
			 * Use the built-in Previewing mechanism to trigger an autosave
			 * so the post can be previewed properly.
			 *
			 * Instead of opening a new tab, send the preview request to a hidden iframe.
			 */
			var dummyIframe = document.createElement('iframe');
			dummyIframe.name = 'stub-iframe';
			dummyIframe.style.display = 'none';
			document.body.appendChild( dummyIframe );
			$('#post-preview').attr( 'target', 'stub-iframe' );
			$('#post-preview').trigger('click');

			event.preventDefault();

			// Open the Customizer in the modal with the existing APIs.
			wp.customize.Loader.link = $(this);
			wp.customize.Loader.open( wp.customize.Loader.link.attr('href') );

			/**
			 * Listen to the 'change' event from the Customizer frame,
			 * and update the local setting value accordingly.
			 */
			wp.customize.Loader.messenger.bind('change', function() {
				var val = wp.customize.Loader.iframe.get(0).contentWindow.wp.customize.instance('post[67][meta][custom_css]').get()
				$('[name="custom_css"]').val(val);
			});
		});
	});
})( jQuery );