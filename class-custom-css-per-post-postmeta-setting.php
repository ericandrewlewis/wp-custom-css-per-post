<?php
/**
 * A class for postmeta Customizer Setting, because existing filters
 * inside of WP_Customize_Setting are not substantial enough, namely `customize_value_*`.
 */
class Custom_CSS_Per_Post_Postmeta_Customize_Setting extends WP_Customize_Setting {
	/**
	 * Utility function to extract the post ID from the id data.
	 */
	public function post_id() {
		return $this->id_data['keys'][0];
	}

	/**
	 * Utility function to extract the post ID from the id data.
	 */
	public function meta_key() {
		return $this->id_data['keys'][2];
	}

	public function value() {
		$meta = get_post_meta( $this->post_id(), $this->meta_key(), true );
		return $meta;
	}

	public function preview() {
		// These lines coupled from parent class.
		if ( ! isset( $this->_original_value ) ) {
			$this->_original_value = $this->value();
		}
		if ( ! isset( $this->_previewed_blog_id ) ) {
			$this->_previewed_blog_id = get_current_blog_id();
		}
		add_filter( 'get_post_metadata', array( $this, 'preview_filter' ), 10, 4 );
	}

	public function preview_filter( $original, $object_id, $meta_key, $single ) {
		if ( ! $this->is_current_blog_previewed() ) {
			return $original;
		}
		if ( $object_id != $this->post_id() ) {
			return $original;
		}
		if ( $meta_key != $this->meta_key() ) {
			return $original;
		}
		$undefined = new stdClass(); // symbol hack
		$post_value = $this->post_value( $undefined );
		if ( $undefined === $post_value ) {
			$value = $this->_original_value;
		} else {
			$value = $post_value;
		}

		return $value;
	}

	protected function update( $value ) {
		update_post_meta( $this->post_id(), $this->meta_key(), $value );
	}
}