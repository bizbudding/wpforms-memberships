<?php

/**
 * Integrate Memberships and WPForms
 *
 * @package    BB_WPForms_Memberships_Settings
 * @since      0.1.0
 * @copyright  Copyright (c) 2019, Mike Hemberger
 * @license    GPL-2.0+
 */
class BB_WPForms_Memberships_Settings {

	/**
	 * Primary Class Constructor.
	 *
	 * @since  0.1.0
	 */
	public function __construct() {
		add_filter( 'wpforms_builder_settings_sections',   array( $this, 'settings_section' ), 20, 2 );
		add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
		add_action( 'wpforms_user_registered',             array( $this, 'add_new_user_to_membership' ), 10, 4 );
		add_action( 'wpforms_process_complete',            array( $this, 'add_logged_in_user_to_membership' ), 10, 4 );
	}

	/**
	 * Add Settings Section.
	 *
	 * @since  0.1.0
	 */
	function settings_section( $sections, $form_data ) {
		$sections['bb_memberships'] = __( 'Memberships', 'wpforms-memberships' );
		return $sections;
	}

	/**
	 * Settings Content.
	 *
	 * @since  0.1.0
	 */
	function settings_section_content( $instance ) {

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-bb_memberships">';
		echo '<div class="wpforms-panel-content-section-title">' . __( 'Memberships', 'wpforms-memberships' ) . '</div>';

			// If not a Registration form.
			if ( ! ( isset( $instance->form_data['meta']['template'] ) && ( 'user_registration' === $instance->form_data['meta']['template'] ) ) ) {
				printf( '<p>%s</p>', __( 'Warning! Membership plans added to any forms other than User Registration will only add currently logged in user to the selected memberhip plans.', 'wpforms-memberships' ) );
			}

			// Enqueue scripts.
			wp_enqueue_script( 'chosen', WPFORMS_WOO_MEMBERSHIPS_URL . 'vendor/harvesthq/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.8.7', true );
			wp_enqueue_style( 'chosen', WPFORMS_WOO_MEMBERSHIPS_URL . 'vendor/harvesthq/chosen/chosen.min.css', array(), '1.8.7' );

			// Get saved plan IDs.
			$plan_ids = array();
			if ( isset( $instance->settings['bb_woocommerce_membership_ids'] ) && ! empty( $instance->settings['bb_woocommerce_membership_ids'] ) ) {
				$plan_ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $instance->settings['bb_woocommerce_membership_ids'] ) ) ) );
			}
			?>
			<div id="wpforms-panel-field-settings-bb_woocommerce_membership-wrap" class="wpforms-panel-field  wpforms-panel-field-select">
				<label for="wpforms-panel-field-settings-bb_woocommerce_membership"><?php echo __( 'WooCommerce Memberships', 'wpforms-memberships' ); ?></label>
				<select id="bb-membership-plans" data-placeholder="Choose Membership(s)..." multiple class="chosen-select">
					<?php
					if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {
						$plans = wc_memberships_get_membership_plans();
						if ( $plans ) {
							foreach ( $plans as $plan ) {
								$plan_id  = $plan->get_id();
								$selected = in_array( $plan_id, $plan_ids ) ? ' selected' : '';
								printf( '<option value="%s"%s>%s</option>', $plan->get_id(), $selected, $plan->get_name() );
							}
						}
					}
					?>
				</select>
			</div>
			<?php

			// Membership Plan IDs.
			wpforms_panel_field(
				'text',
				'settings',
				'bb_woocommerce_memberships',
				$instance->form_data,
				''
			);

			// If Registration form.
			// if ( isset( $instance->form_data['meta']['template'] ) && ( 'user_registration' === $instance->form_data['meta']['template'] ) ) {

			// 	$form_options = array();

			// 	// Get forms.
			// 	$forms = wpforms()->form->get();
			// 	if ( ! empty( $forms ) ) {
			// 		$form_options[''] = __( '--- Choose Membership Form ---', 'wpforms-memberships' );
			// 		foreach ( $forms as $form ) {
			// 			// Skip if the form we're on.
			// 			if ( $form->ID === $instance->form->ID ) {
			// 				continue;
			// 			}
			// 			$form_options[ $form->ID ] = esc_html( $form->post_title );
			// 		}
			// 	}

			// 	// Membership form.
			// 	wpforms_panel_field(
			// 		'select',
			// 		'settings',
			// 		'bb_membership_form',
			// 		$instance->form_data,
			// 		__( 'Membership form if user account exists.', 'wpforms-memberships' ),
			// 		array(
			// 			'options' => $form_options,
			// 		)
			// 	);

			// }

		echo '</div>';

		?>
		<script>
			jQuery(document).ready(function($) {
				// Get our inputs.
				var $input  = $( '#wpforms-panel-field-settings-bb_woocommerce_memberships' );
				var $select = $( '#bb-membership-plans' );
				// Set input to readonly.
				$input.prop( 'readonly', true );
				// Set input to hidden.
				// $input.prop( 'hidden', true );
				// Set select field to multiple.
				$select.prop( 'multiple', true );
				// Start with empty selected items.
				var items = [];
				// If we have a value.
				if ( $input.val() ) {
					// Build array, trimming any spaces.
					var items = $.map( $input.val().split( ',' ), $.trim );
				}
				// Loop through em.
				items.forEach(function(plan) {
					// Check if option exists.
					var option = $select.find( 'option[value="' + plan + '"]' );
					if ( option.length ) {
						// Select it.
						option.prop( 'selected', true );
					}
				});
				// Initiate Chosen.
				$select.chosen({
					width: '100%',
				});
				// Update our hidden field when select is changed.
				$select.on( 'change', function( event, params ) {
					$input.val( $(this).val() );
				});
			});
		</script>
		<?php
	}

	/**
	 * Add newly created user to the membership.
	 *
	 * @since   0.1.0
	 *
	 * @return  void
	 */
	function add_new_user_to_membership( $user_id, $fields, $form_data, $userdata ) {

		// Get plan IDs.
		$plan_ids = $this->get_plan_ids_from_data( $form_data );

		// Bail if no validated plan IDs.
		if ( empty( $plan_ids ) ) {
			return;
		}

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
	 * Add logged in user to the membership.
	 *
	 * @since   0.1.0
	 *
	 * @return  void
	 */
	function add_logged_in_user_to_membership( $fields, $entry, $form_data, $entry_id ) {

		// Bail if this is a registration form, that uses its own hook.
		if ( ! isset( $form_data['meta']['template'] ) || ( 'user_registration' === $form_data['meta']['template'] ) ) {
			return;
		}

		// Bail if user not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Get plan IDs.
		$plan_ids = $this->get_plan_ids_from_data( $form_data );

		// Bail if no validated plan IDs.
		if ( empty( $plan_ids ) ) {
			return;
		}

		$user_id = get_current_user_id();

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
	function get_plan_ids_from_data( $form_data ) {

		// Validated plan IDs.
		$plan_ids = array();

		// Bail if no memberships.
		if ( ! isset( $form_data['settings']['bb_woocommerce_memberships'] ) || empty( $form_data['settings']['bb_woocommerce_memberships'] ) ) {
			return $plan_ids;
		}

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

}

// Initiate the membership settings.
add_action( 'init', function() {
	new BB_WPForms_Memberships_Settings;
});
