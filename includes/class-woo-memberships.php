<?php

/**
 * Integrate WooCommerce Memberships.
 *
 * @package    BB_WPForms_Woo_Memberships
 * @since      0.1.0
 * @copyright  Copyright (c) 2019, Mike Hemberger
 * @license    GPL-2.0+
 */
class BB_WPForms_Woo_Memberships {

	/**
	 * Primary Class Constructor.
	 *
	 * @since  0.1.0
	 */
	public function __construct() {
		add_action( 'wpforms_user_registered',  array( $this, 'add_new_user_to_membership' ), 10, 4 );
		add_action( 'wpforms_process_complete', array( $this, 'add_logged_in_user_to_membership' ), 10, 4 );
	}

	/**
	 * Add newly created user to the membership.
	 *
	 * @since   0.1.0
	 *
	 * @return  void
	 */
	function add_new_user_to_membership( $user_id, $fields, $form_data, $userdata ) {

		// Bail if no memberships.
		if ( ! $this->has_plan_ids( $form_data ) ) {
			return;
		}

		// Get plan IDs.
		$plan_ids = $this->get_valid_plan_ids( $form_data );

		// Bail if no validated plan IDs.
		if ( empty( $plan_ids ) ) {
			return;
		}

		// Add.
		$this->add_user_to_memberships( $user_id, $plan_ids );
	}

	/**
	 * Add logged in user to the membership.
	 *
	 * @since   0.1.0
	 *
	 * @return  void
	 */
	function add_logged_in_user_to_membership( $fields, $entry, $form_data, $entry_id ) {

		// Bail if this is a registration form, since it uses its own hook.
		if ( ! bb_wpforms_is_registration_form( $form_data ) ) {
			return;
		}

		// Bail if user not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Bail if no memberships.
		if ( ! $this->has_plan_ids( $form_data ) ) {
			return;
		}

		// Get plan IDs.
		$plan_ids = $this->get_valid_plan_ids( $form_data );

		// Bail if no validated plan IDs.
		if ( empty( $plan_ids ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// Add.
		$this->add_user_to_memberships( $user_id, $plan_ids );
	}

	/**
	 * Add user to memberships.
	 * Must be validated prior.
	 *
	 * @since   0.2.0
	 *
	 * @return  void
	 */
	function add_user_to_memberships( $user_id, $plan_ids ) {
		foreach( $plan_ids as $plan_id ) {
			// Skip if user is already a member.
			if ( wc_memberships_is_user_member( $user_id, $plan_id ) ) {
				continue;
			}
			// Add the user to the membership.
			wc_memberships_create_user_membership( array(
				'plan_id' => $plan_id,
				'user_id' => $user_id,
			) );
		}
	}

	/**
	 * Get plan IDs from the form data.
	 *
	 * @since   0.1.0
	 *
	 * @return  array  The plan IDs.
	 */
	function get_valid_plan_ids( $form_data ) {

		// Validated plan IDs.
		$plan_ids = array();

		// Build array of plan IDs.
		$ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $form_data['settings']['bb_woocommerce_memberships'] ) ) ) );

		// Loop through potentential IDs.
		foreach( $ids as $plan_id ) {
			// Skip if plan is not valid (returns plan object or false).
			if ( ! wc_memberships_get_membership_plan( $plan_id ) ) {
				continue;
			}
			$plan_ids[] = $plan_id;
		}

		return $plan_ids;
	}

	/**
	 * Check if form data has plan IDs.
	 *
	 * @since   0.2.0
	 *
	 * @return  bool
	 */
	function has_plan_ids( $form_data ) {
		return ( isset( $form_data['settings']['bb_woocommerce_memberships'] ) && $form_data['settings']['bb_woocommerce_memberships'] );
	}

}

// Initiate.
add_action( 'init', function() {
	// Bail if WP Forms is not active.
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}
	// Bail if Woo Memberships is not active.
	if ( ! class_exists( 'WC_Memberships' ) ) {
		return;
	}
	new BB_WPForms_Woo_Memberships;
});
