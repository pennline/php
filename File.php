<?php
namespace Pennline\Php;

use Pennline\Php\Exception;

class File implements FileAdapterInterface {

	/**
	 * @var File
	 */
	protected static $instance;


	/**
	 * protected constructor to prevent creating a
	 * new instance with the ‘new’ keyword from outside the class
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
	 * warning: if the destination file already exists, it will be overwritten.
	 *
	 * @param array    $params
	 * @param string   $params['source_path']
	 * @param string   $params['source_filename']
	 * @param string   $params['dest_path']
	 * @param string   $params['dest_filename']
	 * @param resource $params['context']
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function copy( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$params = $this->validateSourceAndDestination( $params );
		$source = $params['source_path'] . '/' . $params['source_filename'];
		$dest = $params['dest_path'] . '/' . $params['dest_filename'];

		$this->fileExists( $source );

		if ( is_resource( $params['context'] ) ) {
			$result = @copy( $source, $dest, $params['context'] );
		} else {
			$result = @copy( $source, $dest );
		}

		if ( !$result ) {
			error_log( __METHOD__ . '() could not copy [' . $source . '] ' . 'to [' . $dest . ']' );
			throw new Exception( 'could not copy a resource', 3 );
		}

		return true;
	}

	/**
	 * creates a new file
	 *
	 * @param array $params
	 * @param array $params['content']
	 * @param array $params['filename']
	 * @param array $params['storage_path']
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function create( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$fp = @fopen( $params['storage_path'] . '/' . $params['filename'] , 'w' );

		if ( $fp ) {
			$bytes = fwrite( $fp, $params['content'] );
		} else {
			error_log( __METHOD__ . '() could not create [' . $params['filename'] . ']' );
			throw new Exception( 'could not create a resource', 4 );
		}

		return fclose( $fp );
	}

	/**
	 * @param array $params
	 * @throws Exception
	 */
	public function delete( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		if ( !$this->fileExists( $filename ) ) {
			return false;
		}

		if ( !unlink( $filename ) ) {
			error_log( 'could not delete [' . $filename . ']' );
			throw new Exception( 'could not delete a resource', 5 );
		}

		return true;
	}

	/**
	 *  @return bool
	 */
	public function isDir( $pathname, $mkdir = false, $mode = 0755 ) {
		if ( !is_dir( $pathname ) ) {
			if ( $mkdir ) {
				$this->mkDir( $pathname, $mode );
			} else {
				return false;
			}
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return bool
	 */
	public function mkDir( $pathname, $mode ) {
		if ( !mkdir( $pathname, $mode ) ) {
			error_log( 'could not create the directory [' . $pathname . ']' );
			throw new Exception( 'could not delete a directory', 6 );
		}

		return true;
	}

	/**
	 * @param array    $params
	 * @param string   $params['source_path']
	 * @param string   $params['source_filename']
	 * @param string   $params['dest_path']
	 * @param string   $params['dest_filename']
	 * @param resource $params['context']
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function move( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$method_options = array();
		$source = $params['source_path'];
		$dest = $params['dest_path'];

		if ( isset( $params['source_filename'] ) ) {
			$source = $params['source_path'] . '/' . $params['source_filename'];
		} else {
			$method_options['source_filename_required'] = false;
		}

		if ( isset( $params['dest_filename'] ) ) {
			$dest = $params['dest_path'] . '/' . $params['dest_filename'];
		} else {
			$method_options['dest_filename_required'] = false;
		}

		$params = $this->validateSourceAndDestination(
			$params,
			$method_options
		);

		$this->fileExists( $source );

		if ( is_resource( $params['context'] ) ) {
			$result = @rename( $source, $dest, $params['context'] );
		} else {
			$result = @rename( $source, $dest );
		}

		if ( !$result ) {
			error_log( __METHOD__ . '() could not move [' . $source . '] ' . 'to [' . $dest . ']' );
			throw new Exception( 'could not move a resource', 7 );
		}

		return true;
	}

	/**
	 * @throws Exception
	 * @return bool|resource
	 */
	public function openDir( $path ) {
			$result = null;

			if ( !$this->isDir( $path ) ) {
				error_log( __METHOD__ . '() [' . $path . '] is not a directory' );
				throw new Exception( 'directory does not exist', 8 );
			}

			$result = opendir( $path );

			if ( empty( $result ) ) {
				error_log( __METHOD__ . '() could not open [' . $path . ']' );
				throw new Exception( 'could not open a directory', 9 );
			}

			return $result;
	}


	/**
	 * @throws Exception
	 * @return bool
	 */
	public function fileExists( $filename ) {
		if ( !file_exists( $filename ) ) {
			error_log( __METHOD__ . '() file [' . $filename . '] does not exist' );
			throw new Exception( 'resource does not exist', 10 );
		}

		return true;
	}

	/**
	 * @param array $params
	 */
	public function retrieve( $params = array() ) {
	}

	/**
	 * @param string $path
	 * a directory, filename or full filename path
	 * note does not allow for unicode characters
	 */
	protected function sanitizePath( $path = '' ) {
		return preg_replace( '/[^a-zA-Z0-9\-._\/]/', '', $path );
	}

	/**
	 * @param array    $params
	 * @param string   $params['source_path']
	 * @param string   $params['source_filename']
	 * @param resource $params['context']
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function unlink( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$params = $this->validateSourceAndDestination( $params, array( 'validate_dest' => false ) );
		$filename = $params['source_path'] . '/' . $params['source_filename'];
		$this->fileExists( $filename );

		if ( is_resource( $params['context'] ) ) {
			$result = @unlink( $filename, $params['context'] );
		} else {
			$result = @unlink( $filename );
		}

		if ( !$result ) {
			error_log( __METHOD__ . '() could not unlink [' . $filename . '] ' );
			throw new Exception( 'could not unlink a resource', 11 );
		}

		return true;
	}

	/**
	 * appends content to an existing file
	 *
	 * @param array $params
	 * @param array $params['content']
	 * @param array $params['filename']
	 * @param array $params['mode']
	 * @param array $params['storage_path']
	 *
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function update( $params = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$mode = 'a';

		if ( !empty( $params['mode'] ) && is_string( $params['mode'] ) ) {
			$mode = filter_var( $params['mode'], FILTER_SANITIZE_STRING );
		}

		$fp = @fopen( $params['storage_path'] . '/' . $params['filename'], $mode );

		if ( $fp ) {
			$bytes = fwrite( $fp, $params['content'] );
		} else {
			error_log( __METHOD__ . '(), could not update [' . $params['filename'] . ']' );
			throw new Exception( 'could not update a resource', 12 );
		}

		return fclose( $fp );
	}

	/**
	 * @param array $params
	 * @param array $method_options
	 *
	 * @throws Exception
	 *
	 * @return array
	 */
	protected function validateSourceAndDestination( $params = array(), $method_options = array() ) {
		if ( !is_array( $params ) ) {
			error_log( __METHOD__ . '() $params provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		if ( !is_array( $method_options ) ) {
			error_log( __METHOD__ . '() $method_options provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		$default_options = array(
			'source_filename_required' => true,
			'dest_filename_required' => true,
			'validate_dest' => true
		);

		$method_options = array_merge( $default_options, $method_options );

		if ( !isset( $params['context'] ) ) {
			$params['context'] = null;
		} elseif ( !is_resource( $params['context'] ) ) {
			error_log( __METHOD__ . '() context provided is not a valid resource' );
			throw new Exception( 'context provided is not a valid resource', 13 );
		}

		if ( !isset( $params['source_path'] ) || !is_string( $params['source_path'] ) ) {
			error_log( __METHOD__ . '() source path provided is not valid' );
			throw new Exception( 'source path provided is not valid', 13 );
		} else {
			$params['source_path'] = $this->sanitizePath( $params['source_path'] );
		}

		if (
			$method_options['source_filename_required'] &&
			( !isset( $params['source_filename'] ) || !is_string( $params['source_filename'] ) )
		) {
			error_log( __METHOD__ . '() source filename provided is not valid' );
			throw new Exception( 'source filename provided is not valid', 15 );
		} elseif ( isset( $params['source_filename'] ) ) {
			$params['source_filename'] = filter_var( $params['source_filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
		}

		if ( !$method_options['validate_dest'] ) {
			return $params;
		}

		if ( !isset( $params['dest_path'] ) || !is_string( $params['dest_path'] ) ) {
			error_log( __METHOD__ . '() dest_path provided is not valid' );
			throw new Exception( 'destination path provided is not valid', 16 );
		} else {
			$params['dest_path'] = $this->sanitizePath( $params['dest_path'] );
		}

		if (
			$method_options['dest_filename_required'] &&
			( !isset( $params['dest_filename'] ) || !is_string( $params['dest_filename'] ) )
		) {
			error_log( __METHOD__ . '() dest_filename provided is not valid.' );
			throw new Exception( 'destination filename provided is not valid', 17 );
		} elseif ( isset( $params['dest_filename'] ) ) {
			$params['dest_filename'] = filter_var( $params['dest_filename'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );
		}

		return $params;
	}

}