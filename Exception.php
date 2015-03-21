<?php
namespace Pennline\Php;

class Exception extends \Exception {

	/**
	 * @param string $message
	 * @param int code
	 * @param {null|Exception} $previous
	 */
	public function __construct( $message = '', $code = 0, Exception $previous = null ) {
		$message = $this->processMessage( $message, $code );
		parent::__construct( $message, $code, $previous );
	}

	/**
	 *Php\Exception->getBacktrace() /Users/dan/websites/europeana-data-exporter/app/lib/Php/Exception.php:76
	 * @return string
	 */
	protected function getBacktrace() {
		$i = -1;
		$result = '';
		$backtrace = debug_backtrace();

		foreach( $backtrace as $trace ) {
			$i += 1;

			if ( $i <= 2 ) {
				continue;
			}

			if ( !empty( $trace['class'] ) ) {
				$result .= $trace['class'] . '->';
			}

			if ( !empty( $trace['function'] ) ) {
				$result .= $trace['function'] . '() ';
			}

			if ( !empty( $trace['file'] ) ) {
				$result .= $trace['file'];
			}

			if ( isset( $trace['line'] ) ) {
				$result .= ':' . $trace['line'];
			}

			$result .= APPLICATION_EOL;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getLastError() {
		$result = '';
		$last_error = error_get_last();

		if ( isset( $last_error['type'] ) ) {
			$result .= 'Error type: ' . (int) $last_error['type'] . ' ';
		}

		if ( isset( $last_error['message'] ) ) {
			$result .= filter_var( $last_error['message'], FILTER_SANITIZE_STRING ) . ' ';
		}

		if ( isset( $last_error['file'] ) ) {
			$result .= filter_var( $last_error['file'], FILTER_SANITIZE_STRING );
		}

		if ( isset( $last_error['line'] ) ) {
			$result .= ':' . (int) $last_error['line'];
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getLibXmlErrors() {
		$result = '';

		foreach( libxml_get_errors() as $error ) {
			switch ( $error->level ) {
				case LIBXML_ERR_WARNING:
					$result .= 'LibXML Warning';
					break;

				case LIBXML_ERR_ERROR:
					$result .= 'LibXML Error';
					break;

				case LIBXML_ERR_FATAL:
					$result .= 'LibXML Fatal Error';
					break;
			}

			$result .= ' ' . $error->code;
			$result .= ' : ' . $error->message;
			$result .= ' - file ' . $error->file;
			$result .= ', line ' . $error->line;
		}

		// http://stackoverflow.com/questions/3760816/remove-new-lines-from-string#answer-3760830
		return trim( preg_replace( '/\s\s+/', ' ', $result ) );
	}

	/**
	 * @param string $message
	 *
	 * @param init $code
	 * 1 = parameter type error
	 * 2 = missing required property
	 * 3 = could not copy a resource
	 * 4 = could not create a resource
	 * 5 = could not delete a resource
	 * 6 = could not create a directory
	 * 7 = could not move a resource
	 * 8 = directory does not exist
	 * 9 = could not open a directory
	 * 10 = resource does not exist
	 * 11 = could not unlink a resource
	 * 12 = could not update a resource
	 * 13 = context provided is not a valid resource
	 * 14 = source path provided is not valid
	 * 15 = source filename provided is not valid
	 * 16 = destination path provided is not valid
	 * 17 = destination filename provided is not valid
	 * 18 = could not set curl option
	 * 19 = resource not provided
	 * 20 = could not init curl
	 * 21 = cookie directory does not exist
	 * 22 = could not create a cookie
	 * 23 = a curl error occurred
	 * 24 = invalid url provided
	 * 25 = missing required parameter
	 * 26 = input given not yet handled by the application
	 * 99 = user nessage
	 *
	 * @return string
	 */
	protected function processMessage( $message, $code ) {
		$result = '';

		// whether or not to allow the message coming in or substitute it with a standard error message
		switch ( $code ) {
			case 24: break;
			case 99: break;

			default:
				$message = 'Unfortunately a technical error occured. A web developer will correct the issue as soon as possible. error code (' . $code . ').';
				break;
		}

		if ( APPLICATION_ENV === 'development' ) {
			$last_error = $this->getLastError();
			$backtrace = $this->getBacktrace();
			$xml_errors = $this->getLibXmlErrors();

			if ( !empty( $last_error ) ) {
				$result .= $last_error . APPLICATION_EOL;
			}

			if ( !empty( $xml_errors ) ) {
				$result .= $xml_errors . APPLICATION_EOL;
			}

			$result .= filter_var( $message, FILTER_SANITIZE_STRING ) . APPLICATION_EOL;
			$result .= APPLICATION_EOL;

			if ( !empty( $backtrace ) ) {
				$result .= $backtrace;
			}
		} else {
			$result .= filter_var( $message, FILTER_SANITIZE_STRING );
		}

		return $result;
	}

}