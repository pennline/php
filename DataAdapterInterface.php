<?php
namespace Pennline\Php;

interface DataAdapterInterface {

	/**
	 * @param array $options
	 */
	public function create( $options = array() );

	/**
	 * @param array $options
	 */
	public function delete( $options = array() );

	/**
	 * @param array $options
	 */
	public function retrieve( $options = array() );

	/**
	 * @param array $options
	 */
	public function update( $options = array() );

}
