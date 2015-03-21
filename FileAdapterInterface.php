<?php
namespace Pennline\Php;

interface FileAdapterInterface extends DataAdapterInterface {

	/**
	 * @param array $options
	 * @return bool
	 */
	public function copy( $params = array() );

	/**
	 * @param array $options
	 * @return bool
	 */
	public function unlink( $params = array() );

	/**
	 * @param array $options
	 * @return bool
	 */
	public function move( $params = array() );

}
