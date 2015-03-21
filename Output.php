<?php
namespace Pennline\Php;

class Output {

	/**
	 * @var Output
	 */
	protected static $instance;


	/**
	 * protected constructor to prevent creating a new instance with the ‘new’
	 * keyword from outside the class
	 */
	protected function __construct() {}

	/**
	 * private unserialize method to prevent cloning of the instance
	 */
	private function __clone() {}

	/**
	 * private unserialize method to prevent unserializing of the instance
	 */
	private function __wakeup() {}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param string $filename
	 * @return bool|string
	 */
	public function get_include_contents( $filename = '' ) {
		if ( empty( $filename ) ) {
			return false;
		}

		ob_start();

		if ( include $filename ) {
			return ob_get_clean();
		}

		ob_get_clean();
		return false;
	}

}