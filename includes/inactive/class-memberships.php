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
		add_filter( 'wpforms_builder_settings_sections',         array( $this, 'settings_section' ), 20, 2 );
		add_filter( 'wpforms_form_settings_panel_content',       array( $this, 'settings_section_content' ), 20 );
		add_filter( 'wpforms_user_registration_username_exists', array( $this, 'add_login_link_to_error' ), 99 );
		add_filter( 'wpforms_user_registration_email_exists',    array( $this, 'add_login_link_to_error' ), 99 );
		add_filter( 'wpforms_field_properties_email',            array( $this, 'maybe_modify_email_field' ), 10, 3 );
		add_filter( 'wpforms_field_properties_text',             array( $this, 'maybe_modify_username_field' ), 10, 3 );
		add_filter( 'wpforms_field_properties_password',         array( $this, 'maybe_modify_password_field' ), 10, 3 );
		add_filter( 'wpforms_process_before_form_data',          array( $this, 'maybe_bypass_processing' ), 10, 2 );
		add_action( 'wpforms_user_registered',                   array( $this, 'add_new_user_to_membership' ), 10, 4 );
		add_action( 'wpforms_process_complete',                  array( $this, 'add_logged_in_user_to_membership' ), 10, 4 );
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

			// If Woo Memberships is active.
			if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {

				// Get saved plan IDs.
				$plan_ids = array();
				if ( isset( $instance->settings['bb_woocommerce_membership_ids'] ) && ! empty( $instance->settings['bb_woocommerce_membership_ids'] ) ) {
					$plan_ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $instance->settings['bb_woocommerce_membership_ids'] ) ) ) );
				}
				?>
				<div id="wpforms-panel-field-settings-bb_woocommerce_memberships-wrap" class="wpforms-panel-field  wpforms-panel-field-select">
					<label for="wpforms-panel-field-settings-bb_woocommerce_memberships"><?php echo __( 'WooCommerce Memberships', 'wpforms-memberships' ); ?></label>
					<select id="bb-membership-plans" data-placeholder="Choose Membership(s)..." multiple class="chosen-select">
						<?php
						$plans = wc_memberships_get_membership_plans();
						if ( $plans ) {
							foreach ( $plans as $plan ) {
								$plan_id  = $plan->get_id();
								$selected = in_array( $plan_id, $plan_ids ) ? ' selected' : '';
								printf( '<option value="%s"%s>%s</option>', $plan->get_id(), $selected, $plan->get_name() );
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
			}

			// If LearnDash is active.
			if ( function_exists( 'learndash_get_course_id' ) ) {

				// Get saved course IDs.
				$course_ids = array();
				if ( isset( $instance->settings['bb_learndash_course_ids'] ) && ! empty( $instance->settings['bb_learndash_course_ids'] ) ) {
					$course_ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $instance->settings['bb_learndash_course_ids'] ) ) ) );
				}
				?>
				<div id="wpforms-panel-field-settings-bb_learndash_courses-wrap" class="wpforms-panel-field  wpforms-panel-field-select">
					<label for="wpforms-panel-field-settings-bb_learndash_courses"><?php echo __( 'LearnDash Courses', 'wpforms-memberships' ); ?></label>
					<select id="bb-learndash-courses" data-placeholder="Choose Course(s)..." multiple class="chosen-select">
						<?php
						$args = array(
							'post_type'      => 'sfwd-courses',
							'posts_per_page' => -1,
							'post_status'    => 'publish',
						);
						$courses = new WP_Query( $args );
						if ( $courses->have_posts() ) {
							while ( $courses->have_posts() ) : $courses->the_post();
								$selected = in_array( get_the_ID(), $course_ids ) ? ' selected' : '';
								printf( '<option value="%s"%s>%s</option>', get_the_ID(), $selected, get_the_title() );
							endwhile;
						}
						wp_reset_postdata();
						?>
					</select>
				</div>
				<?php

				// Membership Plan IDs.
				wpforms_panel_field(
					'text',
					'settings',
					'bb_learndash_courses',
					$instance->form_data,
					''
				);

			}

			// If Registration form.
			if ( isset( $instance->form_data['meta']['template'] ) && ( 'user_registration' === $instance->form_data['meta']['template'] ) ) {

				// Bypass registration.
				wpforms_panel_field(
					'checkbox',
					'settings',
					'bb_registration_bypass',
					$instance->form_data,
					__( 'Bypass registration if user is logged in', 'wpforms-memberships' )
				);
				// Note.
				echo '<p style="font-size:100%;margin-left:26px;margin-top:-12px;">' . __( 'This makes the email field readonly, removes the registration username/password fields, and allows a user to be added to memberships via this registration form if they are already logged in.', 'wpforms-memberships'  ) . '</p>';

			}

		echo '</div>';

		// If Woo Memberships or LearnDash.
		if ( function_exists( 'wc_memberships_get_membership_plans' ) || function_exists( 'learndash_get_course_id' ) ) {
		?>
		<style>
			#wpforms-builder .wpforms-panel-field#wpforms-panel-field-settings-bb_woocommerce_memberships-wrap,
			#wpforms-builder .wpforms-panel-field#wpforms-panel-field-settings-bb_learndash_courses-wrap {
				margin-bottom: 0;
			}
			#wpforms-builder .wpforms-panel-field#wpforms-panel-field-settings-bb_woocommerce_memberships-wrap + #wpforms-panel-field-settings-bb_woocommerce_memberships-wrap,
			#wpforms-builder .wpforms-panel-field#wpforms-panel-field-settings-bb_learndash_courses-wrap + #wpforms-panel-field-settings-bb_learndash_courses-wrap {
				margin-bottom: 24px;
			}
			#wpforms-builder .wpforms-panel-field #wpforms-panel-field-settings-bb_woocommerce_memberships,
			#wpforms-builder .wpforms-panel-field #wpforms-panel-field-settings-bb_learndash_courses {
				margin: 0;
				border-top: none;
				opacity: .5;
			}
		</style>
		<script>
			jQuery(document).ready(function($) {

				// Get our inputs.
				var $wooInput        = $( '#wpforms-panel-field-settings-bb_woocommerce_memberships' );
				var $wooSelect       = $( '#bb-membership-plans' );
				var $learndashInput  = $( '#wpforms-panel-field-settings-bb_learndash_courses' );
				var $learndashSelect = $( '#bb-learndash-courses' );

				if ( $wooInput.length && $wooSelect.length ) {
					doMembershipStuff( $wooInput, $wooSelect );
				}
				if ( $learndashInput.length && $learndashSelect.length ) {
					doMembershipStuff( $learndashInput, $learndashSelect );
				}

				function doMembershipStuff( $input, $select ) {
					// Set input to readonly.
					$input.prop( 'readonly', true );
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
				}
			});
		</script>
		<?php
		}
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
		if ( ! isset( $form_data['meta']['template'] ) || ( 'user_registration' === $form_data['meta']['template'] ) ) {
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
	 * Check if the form is one to be bypassed.
	 *
	 * @since   0.3.0
	 *
	 * @return  bool
	 */
	function is_bypass( $form_data ) {
		// Bail if not a User Registration form.
		if ( ! isset( $form_data['meta']['template'] ) || ( 'user_registration' !== $form_data['meta']['template'] ) ) {
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
		// Bail if no memberships.
		if ( ! $this->has_plan_ids( $form_data ) ) {
			return false;
		}
		// Get plan IDs.
		$plan_ids = $this->get_valid_plan_ids( $form_data );
		// Bail if no validated plan IDs.
		if ( empty( $plan_ids ) ) {
			return false;
		}
		return true;
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

// Initiate the membership settings.
add_action( 'init', function() {
	// Bail if WP Forms is not active.
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}
	new BB_WPForms_Memberships_Settings;
});
