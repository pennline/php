<?php
/**
 * @link http://php.net/manual/en/book.curl.php
 * @link http://curl.haxx.se/libcurl/
 */
namespace	Pennline\Php;

use Pennline\Http\RequestInterface;
use Pennline\Http\RequestServiceInterface;
use	Pennline\Php\Exception;

class Curl implements RequestInterface, RequestServiceInterface {

	/**
	 * @var string
	 */
	protected $cookiejar;

	/**
	 * @var string
	 */
	protected $cookie_directory;

	/**
	 * @var string
	 */
	protected $cookie_extension;

	/**
	 * @var string
	 */
	protected $cookie_name;

	/**
	 * @var resource
	 */
	protected $curl;

	/**
	 * @var int
	 */
	protected $curl_connecttimeout;

	/**
	 * @var int
	 */
	protected $curl_errno;

	/**
	 * @var string
	 */
	protected $curl_error;

	/**
	 * @var bool
	 */
	protected $curl_followlocation;

	/**
	 * @var bool
	 */
	protected $curl_header;

	/**
	 * @var array
	 */
	protected $curl_info;

	/**
	 * @var int
	 */
	protected $curl_max_redirects;

	/**
	 * @var bool
	 */
	protected $curl_returntransfer;

	/**
	 * @var int
	 */
	protected $curl_timeout;

	/**
	 * @var bool
	 */
	protected $curlinfo_header_out;

	/**
	 * @var
	 */
	protected $http_headers;

	/**
	 * @var bool
	 */
	protected $debug_on;

	/**
	 * @var string
	 */
	public $response_header;

	/**
	 * @var string
	 */
	public $useragent;


	/**
	 * @param array $properties
	 * @throws Exception
	 */
	public function __construct( $properties = array() ) {
		$this->init();
		$this->populate( $properties );

		$this->curl = curl_init();

		if ( !$this->curl ) {
			error_log( __METHOD__ . '() could not init curl' );
			throw new Exception( 'could not init curl', 20 );
		}

		$this->createCookie();
	}

	public function __destruct () {
		if ( is_resource( $this->curl ) ) {
			curl_close( $this->curl );
		}

		if ( file_exists( $this->cookiejar ) ) {
			unlink( $this->cookiejar );
		}
	}

	/**
	 * @throws Exception
	 */
	protected function createCookie() {
		if ( !file_exists( $this->cookie_directory ) ) {
			error_log( __METHOD__ . '() cookie directory does not exist' );
			throw new Exception( 'cookie directory does not exist', 21 );
		}

		$this->cookiejar = $this->cookie_directory . '/' . $this->cookie_name . '.' . dechex( rand( 0,99999999 ) ) . $this->cookie_extension;

		if ( !touch( $this->cookiejar ) ) {
			error_log( __METHOD__ . '() could not create a cookie' );
			throw new Exception( 'could not create a cookie', 22 );
		}

		chmod( $this->cookiejar, 0600 );
		curl_setopt( $this->curl, CURLOPT_COOKIEJAR, $this->cookiejar );
		curl_setopt( $this->curl, CURLOPT_COOKIEFILE, $this->cookiejar );
	}

	/**
	 * @throws Exception
	 *
	 * @return bool|string
	 * Returns true on success or false on failure. however, if the CURLOPT_RETURNTRANSFER
	 * option is set, it will return the result on success, and false on failure.
	 */
	protected function executeCurl() {
		$this->response_header = '';
		$result = curl_exec( $this->curl );

		$this->curl_info = curl_getinfo( $this->curl );
		$this->curl_error = curl_error( $this->curl );
		$this->curl_errno = curl_errno( $this->curl );

		if ( $this->curl_errno !== 0 ) {
			error_log( __METHOD__ . '() curl error [' . $this->curl_error . ']' . ', error nr. [' . $this->curl_errno . '] occurred' );
			error_log( print_r( $this->curl_info, true ) );
			throw new Exception( 'a curl error occured', 23 );
		}

		$this->curl_info['response_header'] = $this->response_header;
		return $result;
	}

	/**
	 * @param string $url
	 * the uri to get
	 *
	 * @param object|array|string $data
	 * data to send in the get
	 *
	 * @returns bool|string
	 * when CURLOPT_RETURNTRANSFER = true, it will return false or the text response
	 * when CURLOPT_RETURNTRANSFER = false, it will return false or true
	 **/
	public function get( $url, $data = array() ) {
		$this->isUrlValid( $url );

		if ( is_array( $data ) || is_object( $data ) ) {
			$data = http_build_query( $data );
		}

		if ( !empty( $data ) && is_string( $data ) ) {
			$url .= '?' . $data;
		}

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, $this->curl_followlocation );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->curl_max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, $this->curl_header );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, array( $this, 'storeResponseHeader' ) );
		$this->setCurlOption( CURLOPT_HTTPGET, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, $this->curl_returntransfer );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLINFO_HEADER_OUT, $this->curlinfo_header_out );

		return $this->executeCurl();
	}

	/**
	 * @return array
	 */
	public function getRequestInfo() {
		return $this->curl_info;
	}

	/**
	 * @param string $url
	 * the address of the page you are looking for
	 *
	 * @returns bool|string
	 * when CURLOPT_RETURNTRANSFER = true, it will return false or the text response
	 * when CURLOPT_RETURNTRANSFER = false, it will return false or true
	 **/
	public function getHeadersOnly( $url ) {
		$this->isUrlValid( $url );

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, $this->curl_followlocation );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->curl_max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, $this->curl_header );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, array( $this, 'storeResponseHeader' ) );
		$this->setCurlOption( CURLOPT_NOBODY, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, $this->curl_returntransfer );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLINFO_HEADER_OUT, $this->curlinfo_header_out );

		return $this->executeCurl();
	}

	public function init() {
		$this->cookiejar = '';
		$this->cookie_directory = '/tmp';
		$this->cookie_extension = '.dat';
		$this->cookie_name = 'http.cookie';
		$this->curl = null;
		$this->curl_timeout = 60;
		$this->curl_connecttimeout = 30;
		$this->curl_errno = 0;
		$this->curl_error = '';
		$this->curl_followlocation = false;
		$this->curl_header = false;
		$this->curl_info = array();
		$this->curl_returntransfer = true;
		$this->curl_max_redirects = 10;
		$this->curlinfo_header_out = true;
		$this->debug_on = false;
		$this->http_headers = array();
		$this->response_header = '';
		$this->useragent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36';
	}

	/**
	 * @param string $url
	 * @throws Exception
	 */
	protected function isUrlValid( $url ) {
		if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
			error_log( __METHOD__ . '() invalid url provided [' . filter_var( $url, FILTER_SANITIZE_STRING ) . ']' );
			throw new Exception( 'invalid url provided [' . filter_var( $url, FILTER_SANITIZE_STRING ) . ']', 24 );
		}

		return true;
	}

	/**
	 * @param string $url
	 * the uri to post to
	 *
	 * @param {object|array|string} $data
	 * data to send in the post
	 *
	 * @returns bool|string
	 * when CURLOPT_RETURNTRANSFER = true, it will return false or the text response
	 * when CURLOPT_RETURNTRANSFER = false, it will return false or true
	 **/
	public function post( $url, $data = array() ) {
		$this->isUrlValid( $url );

		if ( is_array( $data ) || is_object( $data ) ) {
			$data = http_build_query( $data );
		}

		if ( !empty( $data ) && is_string( $data ) ) {
			$this->setCurlOption( CURLOPT_POSTFIELDS, $data );
		}

		$this->setCurlOption( CURLOPT_URL, $url );
		$this->setCurlOption( CURLOPT_FOLLOWLOCATION, $this->curl_followlocation );
		$this->setCurlOption( CURLOPT_MAXREDIRS, $this->curl_max_redirects );
		$this->setCurlOption( CURLOPT_HEADER, $this->curl_header );
		$this->setCurlOption( CURLOPT_HEADERFUNCTION, array( $this, 'storeResponseHeader' ) );
		$this->setCurlOption( CURLOPT_POST, true );
		$this->setCurlOption( CURLOPT_RETURNTRANSFER, $this->curl_returntransfer );
		$this->setCurlOption( CURLOPT_CONNECTTIMEOUT, $this->curl_connecttimeout );
		$this->setCurlOption( CURLOPT_USERAGENT, $this->useragent );
		$this->setCurlOption( CURLOPT_TIMEOUT, $this->curl_timeout );
		$this->setCurlOption( CURLINFO_HEADER_OUT, $this->curlinfo_header_out );

		return $this->executeCurl();
	}

	/**
	 * header function set with CURLOPT_HEADERFUNCTION
	 * collects the response header and places it into a single variable
	 *
	 * @param resource $ch the curl resource
	 * @param string   $header_line a single response header line
	 *
	 * @throws Exception
	 *
	 * @return int
	 */
	public function storeResponseHeader( $ch = null, $header_line = '' ) {
		if ( !is_resource( $ch ) ) {
			error_log( __METHOD__ . '() $ch provided is not a resource' );
			throw new Exception( 'resource not provided', 19 );
		}

		$this->response_header .= $header_line;
		return strlen( $header_line );
	}

	/**
	 * @param array $properties
	 * @throws Exception
	 */
	protected function populate( $properties = array() ) {
		if ( !is_array( $properties ) ) {
			error_log( __METHOD__ . '() $properties provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		if ( isset( $properties['cookie-directory'] ) && is_string( $properties['cookie-directory'] ) ) {
			$this->cookie_directory = filter_var( $properties['cookie-directory'], FILTER_SANITIZE_STRING );
		}

		if ( isset( $properties['cookie-extension'] ) && is_string( $properties['cookie-extension'] ) ) {
			$this->cookie_extension = filter_var( $properties['cookie-extension'], FILTER_SANITIZE_STRING );
		}

		if ( isset( $properties['cookie-name'] ) && is_string( $properties['cookie-name'] ) ) {
			$this->cookie_name = filter_var( $properties['cookie-name'], FILTER_SANITIZE_STRING );
		}

		if ( isset( $properties['curl-connecttimeout'] ) && is_int( $properties['curl-connecttimeout'] ) ) {
			$this->curl_connecttimeout = (int) $properties['curl-connecttimeout'];
		}

		if ( isset( $properties['curl-followlocation'] ) && is_bool( $properties['curl-followlocation'] ) ) {
			$this->curl_followlocation = (bool) $properties['curl-followlocation'];
		}

		if ( isset( $properties['curl-header'] ) && is_bool( $properties['curl-header'] ) ) {
			$this->curl_header = (bool) $properties['curl-header'];
		}

		if ( isset( $properties['curl-max-redirects'] ) && is_int( $properties['curl-max-redirects'] ) ) {
			$this->curl_max_redirects = (int) $properties['curl-max-redirects'];
		}

		if ( isset( $properties['curl-returntransfer'] ) && is_bool( $properties['curl-returntransfer'] ) ) {
			$this->curl_returntransfer = (bool) $properties['curl-returntransfer'];
		}

		if ( isset( $properties['curl-timeout'] ) && is_int( $properties['curl-timeout'] ) ) {
			$this->curl_timeout = (int) $properties['curl-timeout'];
		}

		if ( isset( $properties['curlinfo-header-out'] ) && is_bool( $properties['curlinfo-header-out'] ) ) {
			$this->curlinfo_header_out = (bool) $properties['curlinfo-header-out'];
		}

		if ( isset( $properties['debug-on'] ) && is_bool( $properties['debug-on'] ) ) {
			$this->debug_on = $properties['debug-on'];
		}

		if ( isset( $properties['useragent'] ) && is_string( $properties['useragent'] ) ) {
			$this->useragent = filter_var( $properties['useragent'], FILTER_SANITIZE_STRING );
		}
	}

	/**
	 * @param int    $option a CURLOPT constant
	 * @param string $value
	 *
	 * @throws Exception
	 */
	protected function setCurlOption( $option, $value ) {
		if ( !curl_setopt( $this->curl, $option, $value ) ) {
			error_log( __METHOD__ . '() could not set curl option [' . filter_var( $option, FILTER_SANITIZE_STRING ) . '] to value [' . filter_var( $value, FILTER_SANITIZE_STRING ) . ']' );
			throw new Exception( 'could not set curl option', 18 );
		}
	}

	/**
	 * @para array $headers
	 */
	public function setHttpHeader( $headers = array() ) {
		if ( !is_array( $headers ) ) {
			error_log( __METHOD__ . '() $headers provided are not an array' );
			throw new Exception( 'parameter type error', 1 );
		}

		foreach ( $headers as $header ) {
			$this->http_headers[] = $header;
		}

		$this->setCurlOption( CURLOPT_HTTPHEADER, $this->http_headers );
	}

}