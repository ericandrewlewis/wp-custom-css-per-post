<?php
/**
 * Plugin Name: Custom CSS Per Post
 * Version: 0.1
 * Author: Eric Andrew Lewis
 */

add_action( 'customize_register', function() {
	global $wp_customize;
	require_once( plugin_dir_path( __FILE__ ) . 'class-wp-customize-postmeta-setting.php' );
} );

/**
 * Output per-post custom CSS in <head> of the site.
 *
 * The Customizer preview page loads will load the drafted changes here via internal
 * filters.
 */
add_action( 'wp_head', function() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	?><style type="text/css"><?php echo get_post_meta( $post->ID, 'custom_css', true ) ?></style><?php
});

/**
 * Enqueue the script that will pass the post ID through to the preview frame.
 */
add_action( 'customize_controls_enqueue_scripts', function( $manager ) {
	if ( ! isset( $_REQUEST['custom_css_post_id'] ) ) {
		return;
	}
	wp_enqueue_script( 'custom-css-customize-controls',
		plugins_url( '/script.js', __FILE__ ),
		array( 'customize-controls' ) );
});

/*
 * Register a section, setting and control for custom css with the Customizer API.
 */
add_action( 'customize_register', function( $manager ) {
	global $wp_customize;

	$is_customizer_frame = is_admin() && 'customize.php' == basename( $_SERVER['PHP_SELF'] );
	$is_preview_frame = ! is_admin() && is_customize_preview();
	$is_query_var_set = isset( $_REQUEST['custom_css_post_id'] );

	// We can extract the post ID from the query argument we've manually set
	// both in the Customizer frame and the preview frame.
	if ( $is_query_var_set ) {
		$post_id = $_REQUEST['custom_css_post_id'];
	}

	// The $_POST['customized'] value should be looked at to figure out the post id
	// when saving data via AJAX.
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

	// Hide all default panels and sections in the Customizer and Preview frame.
	if ( $is_customizer_frame ) {
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
		'description' => __( 'Add custom CSS here' ),
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

/*
 * Enqueue the `customizer-loader` script on the Post Edit screen.
 */
add_action( 'admin_enqueue_scripts', function($hook_suffix) {
	if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
		wp_enqueue_script( 'customize-loader' );
	}
} );

/*
 * Add a button in the Submit meta box that, on click, opens the customizer.
 */
add_action( 'post_submitbox_misc_actions', function() {
	$post = get_post();
	$customize_url = add_query_arg(
		array(
			'url' => urlencode( get_permalink( $post->ID ) ),
			'custom_css_post_id' => $post->ID,
			'autofocus' => array( 'section' => 'custom_css' ),
		),
		wp_customize_url()
	);
	?>
	<div style="margin: 10px 0; text-align: center;">
		<button class="load-customize button" href="<?php echo esc_url( $customize_url ) ?>" type="button">Custom CSS</button>
	</div>
	<?php
} );