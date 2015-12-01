<?php
/**
 * Plugin Name: Custom CSS Per Post
 * Version: 0.1
 * Author: Eric Andrew Lewis
 */

/**
 * Output per-post custom CSS in <head> of the site.
 *
 * A Customizer preview loads the drafted changes here via internal filters.
 */
add_action( 'wp_head', function() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	?><style type="text/css"><?php echo get_post_meta( $post->ID, 'custom_css', true ) ?></style><?php
});

/**
 * Include code when the Customizer initializes.
 */
add_action( 'customize_register', function() {
	global $wp_customize;
	require_once( plugin_dir_path( __FILE__ ) . 'class-wp-customize-postmeta-setting.php' );
} );

/**
 * Enqueue the script that will pass the post ID through to the preview frame.
 */
add_action( 'customize_controls_enqueue_scripts', function( $manager ) {
	if ( ! isset( $_REQUEST['custom_css_post_id'] ) ) {
		return;
	}
	wp_enqueue_script( 'custom-css-customize-controls',
		plugins_url( '/js/customize-controls.js', __FILE__ ),
		array( 'customize-controls' ) );

	wp_enqueue_style( 'custom-css-customize-controls',
		plugins_url( '/css/customize-controls.css', __FILE__ ) );
});

/**
 * Load the script that will handle opening the Customizer.
 *
 * This script wraps the customize-loader with autosaving.
 */
add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
	if ( ! in_array($hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
		return;
	}
	wp_enqueue_script( 'edit-post-page-script',
		plugins_url( '/js/edit-post.js', __FILE__ ),
		array( 'customize-loader' ) );
});

/*
 * Register a section, setting and control for custom CSS with the Customizer API.
 *
 * This should be done only when in the context of editing Custom CSS.
 */
add_action( 'customize_register', function( $manager ) {
	global $wp_customize;
	$is_query_var_set = isset( $_REQUEST['custom_css_post_id'] );

	/*
	 * We can extract the post ID that custom CSS is being edited for
	 * from the query argument set both in the Customizer frame and the preview frame.
	 */
	if ( $is_query_var_set ) {
		$post_id = $_REQUEST['custom_css_post_id'];
	}

	/*
	 * The $_POST['customized'] value tells us the post ID when saving data via AJAX.
	 */
	if ( isset( $_POST['customized'] ) ) {
		$post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
		foreach ( $post_values as $setting_id => $post_value ) {
			if ( ! preg_match('#post\[(\d+)\]\[meta\]\[custom_css\]#', $setting_id, $matches ) ) {
				continue;
			}
			$post_id = $matches[1];
			break;
		}
	}

	// If no $post_id could be deduced, we shouldn't do anything.
	if ( empty( $post_id ) ) {
		return;
	}

	$is_customizer_frame = is_admin() && 'customize.php' == basename( $_SERVER['PHP_SELF'] );
	$is_preview_frame = ! is_admin() && is_customize_preview();
	// Remove all default panels and sections in the Customizer and Preview frame.
	if ( $is_customizer_frame || $is_preview_frame ) {
		foreach ( $wp_customize->panels() as $key => $panel ) {
			$wp_customize->remove_panel( $key );
		}

		foreach ( $wp_customize->sections() as $key => $section ) {
			$wp_customize->remove_section( $key );
		}
	}

	$setting = new WP_Customize_Postmeta_Setting(
		$wp_customize,
		sprintf( 'post[%d][meta][custom_css]', $post_id ),
		array(
			'type' => 'postmeta',
			'active_callback' => '__return_true',
		)
	);

	// Add the custom objects for Custom CSS on both the customizer frame
	// and the preview frame.
	$wp_customize->add_section( 'custom_css', array(
		'title' => __( 'Custom CSS' ),
		'description' => __( '' ),
		// Implicitly create a panel for the section.
		'panel' => '',
		'priority' => 160,
		'capability' => 'edit_theme_options',
		// As this should be persistent over any page the preview
		// is loaded, set the active state to true all the time.
		'active_callback' => '__return_true',
	) );

	$wp_customize->add_setting( $setting );

	$wp_customize->add_control( $setting->id, array(
		'label' => __( 'Custom CSS for this post' ),
		'type' => 'textarea',
		'section' => 'custom_css',
		// As this should be persistent over any page the preview
		// is loaded, set the active state to true all the time.
		'active_callback' => '__return_true',
	) );

}, 12, 1 );

/**
 * Output the button for editing the CSS via the Customizer.
 */
function edit_custom_css_in_customizer_button() {
	$post = get_post();

	$preview_url = add_query_arg( array( 'preview' => true ), get_permalink( $post->ID ) );

	/*
	 * Add some extra query args if the preview is for an autosave.
	 *
	 * @see post_preview()
	 */
	$post_not_draft_or_autodraft = $post->post_status != 'draft' && $post->post_status != 'auto-draft';
	if ( $post_not_draft_or_autodraft ) {
		$preview_url = add_query_arg(
			array(
				'preview_id' => $post->ID,
				'preview_nonce' => wp_create_nonce( 'post_preview_' . $post->ID ),
			),
			$preview_url
		);
	}

	$customize_url = add_query_arg(
		array(
			'url' => urlencode( $preview_url ),
			'custom_css_post_id' => $post->ID,
			'autofocus' => array( 'section' => 'custom_css' ),
		),
		wp_customize_url()
	);

	?>
	<button style="margin: 5px 0 10px;" class="load-customize-with-post-preview button" href="<?php echo esc_url( $customize_url ) ?>" type="button">Edit with live preview</button>
	<?php
}

/**
 * Add a meta box on the Edit Post screen for Custom CSS.
 */
add_action( 'add_meta_boxes', function() {
	add_meta_box(
		'post_custom_css',
		__( 'Custom CSS', 'custom_css_per_post' ),
		'custom_css_meta_box_callback',
		'post'
	);
} );

/**
 * Output the Custom CSS metabox content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function custom_css_meta_box_callback( $post ) {

	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'custom_css', 'custom_css_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$value = get_post_meta( $post->ID, 'custom_css', true );

	edit_custom_css_in_customizer_button();
	echo '<textarea name="custom_css" style="width: 100%; min-height: 100px;">' . esc_textarea( $value ) . '</textarea>';
}

/**
 * When the post is saved, save the custom CSS data that was submitted from the
 * textarea as part of the Edit Post form.
 *
 * @param int $post_id The ID of the post being saved.
 */
function save_custom_css_data( $post_id ) {
	/*
	 * Do a bunch of sanity and statefulness checks to ensure we're in the context
	 * where we want to save.
	 */
	if ( ! isset( $_POST['custom_css_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['custom_css_nonce'], 'custom_css' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['custom_css'] ) ) {
		return;
	}

	$value = sanitize_text_field( $_POST['custom_css'] );
	update_post_meta( $post_id, 'custom_css', $value );
}
add_action( 'save_post', 'save_custom_css_data' );