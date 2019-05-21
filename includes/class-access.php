<?php

/**
 * Base class to integrate memberships and WPForms.
 *
 * @package    BB_WPForms_Memberships
 * @since      0.1.0
 * @copyright  Copyright (c) 2019, Mike Hemberger
 * @license    GPL-2.0+
 */
class BB_WPForms_Memberships_Access {

	/**
	 * Primary Class Constructor.
	 *
	 * @since  0.1.0
	 */
	public function __construct() {
		add_filter( 'wpforms_user_registration_username_exists', array( $this, 'add_login_link_to_error' ), 99 );
		add_filter( 'wpforms_user_registration_email_exists',    array( $this, 'add_login_link_to_error' ), 99 );
		add_filter( 'wpforms_field_properties_email',            array( $this, 'maybe_modify_email_field' ), 10, 3 );
		add_filter( 'wpforms_field_properties_text',             array( $this, 'maybe_modify_username_field' ), 10, 3 );
		add_filter( 'wpforms_field_properties_password',         array( $this, 'maybe_modify_password_field' ), 10, 3 );
		add_filter( 'wpforms_process_before_form_data',          array( $this, 'maybe_bypass_processing' ), 10, 2 );
		add_action( 'wpforms_user_registered',                   array( $this, 'maybe_auto_login' ), 20, 4 );
	}

	/**
	 * Add login link with redirect if trying to register a user that already exists.
	 *
	 * @since   0.2.0
	 *
	 * @param   string  The existing message.
	 *
	 * @return  string  New message.
	 */
	function add_login_link_to_error( $message ) {
		// Bail if user is logged in.
		if ( is_user_logged_in() ) {
			return $message;
		}
		// Current URL with any query params.
		$redirect    = home_url( add_query_arg( null, null ) );
		$new_message = sprintf( '&nbsp;<a href="%s">%s</a>', wp_login_url( $redirect ), __( 'Log in?', 'wpforms-memberships' ) );
		return $message . $new_message;
	}

	/**
	 * Set the default value of the email field, and make it readonly.
	 *
	 * @since   0.3.0
	 *
	 * @param   array  $properties  The field properties.
	 * @param   array  $field       The field data.
	 * @param   array  $form_data   The form data.
	 *
	 * @return  array  The modified field properties.
	 */
	function maybe_modify_email_field( $properties, $field, $form_data ) {
		// Bail if not bypassing this form.
		if ( ! $this->is_bypass( $form_data ) ) {
			return $properties;
		}
		// Get current user.
		$current_user = wp_get_current_user();
		// Bail if current user does not exist.
		if ( ! $current_user->exists() ) {
			return $properties;
		}
		// Skip if field does not have the meta we need.
		if ( ! ( isset( $field['meta']['nickname'] ) && isset( $field['meta']['delete'] ) ) ) {
			return $properties;
		}
		// Skip if the nickname is not email.
		if ( 'email' !== $field['meta']['nickname'] ) {
			return $properties;
		}
		// Skip if deleting is not disabled.
		if ( $field['meta']['delete'] ) {
			return $properties;
		}
		// Set the current logged in user's email as the field value.
		$properties['inputs']['primary']['attr']['value'] = $current_user->user_email;
		// Set the readonly propery.
		$properties['inputs']['primary']['attr']['readonly'] = 'readonly';
		// If email confirm field.
		if ( isset( $properties['inputs']['secondary'] ) ) {
			// Set the current logged in user's email as the field value.
			$properties['inputs']['secondary']['attr']['value'] = $current_user->user_email;
			// Set the readonly propery.
			$properties['inputs']['secondary']['attr']['readonly'] = 'readonly';
		}
		return $properties;
	}

	/**
	 * Hide and make the username field not required.
	 *
	 * @since   0.3.0
	 *
	 * @param   array  $properties  The field properties.
	 * @param   array  $field       The field data.
	 * @param   array  $form_data   The form data.
	 *
	 * @return  array  The modified field properties.
	 */
	function maybe_modify_username_field( $properties, $field, $form_data ) {
		// If not the username field.
		if ( ! ( isset( $form_data['settings']['registration_username'] ) && isset( $form_data['fields'][ $form_data['settings']['registration_username'] ] ) ) ) {
			return $properties;
		}
		// Bail if not bypassing this form.
		if ( ! $this->is_bypass( $form_data ) ) {
			return $properties;
		}
		// Set as not required.
		$properties['label']['required'] = '';
		$properties['inputs']['primary']['required'] = '';
		// Hide the field.
		$properties['container']['attr']['style'] = 'display:none;';
		return $properties;
	}

	/**
	 * Hide and make the password field not required.
	 *
	 * @since   0.3.0
	 *
	 * @param   array  $properties  The field properties.
	 * @param   array  $field       The field data.
	 * @param   array  $form_data   The form data.
	 *
	 * @return  array  The modified field properties.
	 */
	function maybe_modify_password_field( $properties, $field, $form_data ) {
		// If not the password field.
		if ( ! ( isset( $form_data['settings']['registration_password'] ) && isset( $form_data['fields'][ $form_data['settings']['registration_password'] ] ) ) ) {
			return $properties;
		}
		// Bail if not bypassing this form.
		if ( ! $this->is_bypass( $form_data ) ) {
			return $properties;
		}
		// Set as not required.
		$properties['label']['required'] = '';
		$properties['inputs']['primary']['required'] = '';
		if ( isset( $properties['inputs']['secondary'] ) ) {
			$properties['inputs']['secondary']['required'] = '';
		}
		// Hide the field.
		$properties['container']['attr']['style'] = 'display:none;';
		return $properties;
	}

	/**
	 * Set the template as blank instead of user registration.
	 * Bypass username and password fields by making them not required during processing.
	 *
	 * @since   0.2.0
	 *
	 * @return  array  The form data.
	 */
	function maybe_bypass_processing( $form_data, $entry ) {
		// Bail if not bypassing this form.
		if ( ! $this->is_bypass( $form_data ) ) {
			return $form_data;
		}
		// Set template as blank so no registration processes are triggered.
		$form_data['meta']['template'] = 'blank';
		// Username.
		if ( isset( $form_data['settings']['registration_username'] ) && isset( $form_data['fields'][ $form_data['settings']['registration_username'] ] ) ) {
			$form_data['fields'][ $form_data['settings']['registration_username'] ]['required'] = '0';
		}
		// Password.
		if ( isset( $form_data['settings']['registration_password'] ) && isset( $form_data['fields'][ $form_data['settings']['registration_password'] ] ) ) {
			$form_data['fields'][ $form_data['settings']['registration_password'] ]['required'] = '0';
		}
		return $form_data;
	}

	/**
	 * Auto log in user after successful registration.
	 *
	 * @since   0.4.0
	 *
	 * @return  void
	 */
	function maybe_auto_login( $user_id, $fields, $form_data, $userdata ) {
		if ( ! $this->is_auto_login( $form_data ) ) {
			return;
		}
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
	}

	/**
	 * Check if the form is one to be bypassed.
	 *
	 * @since   0.3.0
	 *
	 * @return  bool
	 */
	function is_bypass( $form_data ) {
		// Bail if not a User Registration form.
		if ( ! bb_wpforms_is_registration_form( $form_data ) ) {
			return false;
		}
		// If form is not set for registration bypass.
		if ( ! ( isset( $form_data['settings']['bb_registration_bypass'] ) && $form_data['settings']['bb_registration_bypass'] ) ) {
			return false;
		}
		// Bail if user is not logged in.
		if ( ! is_user_logged_in() ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the form is one to auto login.
	 *
	 * @since   0.4.0
	 *
	 * @return  bool
	 */
	function is_auto_login( $form_data ) {
		// Bail if not a User Registration form.
		if ( ! bb_wpforms_is_registration_form( $form_data ) ) {
			return false;
		}
		// If form is not set for registration auto login.
		if ( ! ( isset( $form_data['settings']['bb_registration_auto_login'] ) && $form_data['settings']['bb_registration_auto_login'] ) ) {
			return false;
		}
		// Bail user is already logged in.
		if ( is_user_logged_in() ) {
			return false;
		}
		// Bail activation method is set, since the user will not be activated at this point if something is set.
		if ( isset( $form_data['settings']['registration_activation_method'] ) && $form_data['settings']['registration_activation_method'] ) {
			return false;
		}
		return true;
	}

}

// Initiate.
add_action( 'init', function() {
	// Bail if WP Forms is not active.
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}
	new BB_WPForms_Memberships_Access;
});
