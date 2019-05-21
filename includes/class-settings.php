<?php

/**
 * Add WPForms Settings.
 *
 * @package    BB_WPForms_Memberships
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
		add_action( 'admin_enqueue_scripts',               array( $this, 'register_scripts' ) );
		add_filter( 'wpforms_builder_settings_sections',   array( $this, 'settings_section' ), 20, 2 );
		add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
	}

	// Register scripts for later.
	function register_scripts() {
		wp_register_script( 'bb-wpforms-memberships', WPFORMS_WOO_MEMBERSHIPS_URL . '/assets/js/memberships.js', 	array( 'jquery' ), CHILD_THEME_VERSION, true );
		wp_register_style( 'bb-wpforms-memberships', WPFORMS_WOO_MEMBERSHIPS_URL . '/assets/css/memberships.css', array(), CHILD_THEME_VERSION );
		wp_register_script( 'chosen', WPFORMS_WOO_MEMBERSHIPS_URL . 'vendor/harvesthq/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.8.7', true );
		wp_register_style( 'chosen', WPFORMS_WOO_MEMBERSHIPS_URL . 'vendor/harvesthq/chosen/chosen.min.css', array(), '1.8.7' );
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

		// Enqueue scripts and styles.
		wp_enqueue_script( 'bb-wpforms-memberships' );
		wp_enqueue_style( 'bb-wpforms-memberships' );
		wp_enqueue_script( 'chosen' );
		wp_enqueue_style( 'chosen' );

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-bb_memberships">';
		echo '<div class="wpforms-panel-content-section-title">' . __( 'Memberships', 'wpforms-memberships' ) . '</div>';

			// If not a Registration form.
			if ( ! ( isset( $instance->form_data['meta']['template'] ) && ( 'user_registration' === $instance->form_data['meta']['template'] ) ) ) {
				printf( '<p>%s</p>', __( 'Warning! Membership plans added to any forms other than User Registration will only add currently logged in user to the selected memberhip plans.', 'wpforms-memberships' ) );
			}

			// If Woo Memberships is active.
			if ( class_exists( 'WC_Memberships' ) ) {

				// Get saved plan IDs.
				$plan_ids = array();
				if ( isset( $instance->settings['bb_woocommerce_membership_ids'] ) && ! empty( $instance->settings['bb_woocommerce_membership_ids'] ) ) {
					$plan_ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $instance->settings['bb_woocommerce_membership_ids'] ) ) ) );
				}
				?>
				<!-- Faux Membership ID's field used to populate actual bb_woocommerce_memberships field. -->
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
				<!-- Faux Course ID's field used to populate actual bb_learndash_courses field. -->
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

				// Course IDs.
				wpforms_panel_field(
					'text',
					'settings',
					'bb_learndash_courses',
					$instance->form_data,
					''
				);

			}

			// If Registration form.
			if ( bb_wpforms_is_registration_form( $instance->form_data ) ) {

				// Bypass registration.
				wpforms_panel_field(
					'checkbox',
					'settings',
					'bb_registration_bypass',
					$instance->form_data,
					__( 'Bypass registration if user is logged in', 'wpforms-memberships' )
				);
				// Note.
				echo '<p class="bb-wpforms-field-note">' . __( 'Make the email field readonly, remove the registration username/password fields, and allow a user to be added to memberships via this registration form if they are already logged in.', 'wpforms-memberships'  ) . '</p>';

				// Auto login.
				wpforms_panel_field(
					'checkbox',
					'settings',
					'bb_registration_auto_login',
					$instance->form_data,
					__( 'Automatically log in after succesful registration', 'wpforms-memberships' )
				);
				// Note.
				echo '<p class="bb-wpforms-field-note">' . __( 'Only applies if there is no user activation method set.', 'wpforms-memberships'  ) . '</p>';

			}

		echo '</div>';

	}
}

// Initiate.
add_action( 'admin_init', function() {
	// Bail if WP Forms is not active.
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}
	new BB_WPForms_Memberships_Settings;
});
