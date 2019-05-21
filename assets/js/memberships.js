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
