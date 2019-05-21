<?php

function bb_wpforms_is_registration_form( $form_data ) {
	return ( isset( $form_data['meta']['template'] ) && ( 'user_registration' === $form_data['meta']['template'] ) );
}
