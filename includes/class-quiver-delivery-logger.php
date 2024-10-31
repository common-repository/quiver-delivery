<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class QuiverDeliveryLogger {


	public function __construct() {}

	/**
	 * Write the message to log
	 *
	 * @param String $message
	 */
	public function write( $message ) {
        // Logger object
        $wc_logger = new WC_Logger();

        // Add to logger
        $wc_logger->add( 'quiver_delivery', $message );
	}
}