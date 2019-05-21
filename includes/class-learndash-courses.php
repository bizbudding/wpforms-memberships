<?php

/**
 * Integrate LearnDash Courses.
 *
 * @package    BB_WPForms_LearnDash_Courses
 * @since      0.4.0
 * @copyright  Copyright (c) 2019, Mike Hemberger
 * @license    GPL-2.0+
 */
class BB_WPForms_LearnDash_Courses {

	/**
	 * Primary Class Constructor.
	 *
	 * @since  0.4.0
	 */
	public function __construct() {
		add_action( 'wpforms_user_registered',  array( $this, 'add_new_user_to_course' ), 10, 4 );
		add_action( 'wpforms_process_complete', array( $this, 'add_logged_in_user_to_course' ), 10, 4 );
	}

	/**
	 * Add newly created user to the course.
	 *
	 * @since   0.4.0
	 *
	 * @return  void
	 */
	function add_new_user_to_course( $user_id, $fields, $form_data, $userdata ) {

		// Bail if no courses.
		if ( ! $this->has_course_ids( $form_data ) ) {
			return;
		}

		// Get course IDs.
		$course_ids = $this->get_valid_course_ids( $form_data );

		// Bail if no validated course IDs.
		if ( empty( $course_ids ) ) {
			return;
		}

		// Add.
		$this->add_user_to_courses( $user_id, $course_ids );
	}

	/**
	 * Add logged in user to the course.
	 *
	 * @since   0.4.0
	 *
	 * @return  void
	 */
	function add_logged_in_user_to_course( $fields, $entry, $form_data, $entry_id ) {

		// Bail if this is a registration form, since it uses its own hook.
		if ( ! bb_wpforms_is_registration_form( $form_data ) ) {
			return;
		}

		// Bail if user not logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Bail if no courses.
		if ( ! $this->has_course_ids( $form_data ) ) {
			return;
		}

		// Get course IDs.
		$course_ids = $this->get_valid_course_ids( $form_data );

		// Bail if no validated course IDs.
		if ( empty( $course_ids ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// Add.
		$this->add_user_to_courses( $user_id, $course_ids );
	}

	/**
	 * Add user to courses.
	 * Must be validated prior.
	 *
	 * @since   0.4.0
	 *
	 * @return  void
	 */
	function add_user_to_courses( $user_id, $course_ids ) {
		foreach( $course_ids as $course_id ) {
			// Skip if enrolled already.
			if ( $this->is_user_enrolled_to_course( $user_id, $course_id ) ) {
				continue;
			}
			// Enroll.
			ld_update_course_access( $user_id, $course_id, $remove = false );
		}
	}

	/**
	 * Get course IDs from the form data.
	 *
	 * @since   0.4.0
	 *
	 * @return  array  The course IDs.
	 */
	function get_valid_course_ids( $form_data ) {

		// Validated course IDs.
		$course_ids = array();

		// Build array of course IDs.
		$ids = array_filter( array_map( 'trim', explode( ',', sanitize_text_field( $form_data['settings']['bb_learndash_courses'] ) ) ) );

		// Loop through potentential IDs.
		foreach( $ids as $course_id ) {
			// Skip if course is not valid (returns course ID or 0/false).
			if ( ! learndash_get_course_id( $course_id ) ) {
				continue;
			}
			$course_ids[] = $course_id;
		}

		return $course_ids;
	}

	/**
	 * Check if form data has course IDs.
	 *
	 * @since   0.4.0
	 *
	 * @return  bool
	 */
	function has_course_ids( $form_data ) {
		return ( isset( $form_data['settings']['bb_learndash_courses'] ) && $form_data['settings']['bb_learndash_courses'] );
	}

	/**
	 * Check if a user is already enrolled in a course.
	 * This gets called for every course we're adding a user to,
	 * but `learndash_user_get_enrolled_courses()` gets stored in a transient for 1 min after first call.
	 *
	 * @since   0.4.0
	 *
	 * @param   int   $user_id    User ID
	 * @param   int   $course_id  Course ID
	 *
	 * @return  bool  True if enrolled|false otherwise
	 */
	function is_user_enrolled_to_course( $user_id = 0, $course_id = 0 ) {
		$enrolled_courses = learndash_user_get_enrolled_courses( $user_id );
		return ( is_array( $enrolled_courses ) && in_array( $course_id, $enrolled_courses ) );
	}

}

// Initiate.
add_action( 'init', function() {
	// Bail if WP Forms is not active.
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}
	// Bail if LearnDash is not active.
	if ( ! function_exists( 'learndash_get_course_id' ) ) {
		return;
	}
	new BB_WPForms_LearnDash_Courses;
});
