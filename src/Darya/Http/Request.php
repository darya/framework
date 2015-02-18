<?php
namespace Darya\Http;

use Darya\Http\SessionInterface;

/**
 * Darya's HTTP request representation.
 * 
 * @property array $get
 * @property array $post
 * @property array $cookie
 * @property array $file
 * @property array $server
 * @property array $header
 * @method mixed get(string $key)
 * @method mixed post(string $key)
 * @method mixed cookie(string $key)
 * @method mixed file(string $key)
 * @method mixed server(string $key)
 * @method mixed header(string $key)
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Request {
	
	/**
	 * @var string Request URI
	 */
	private $uri;
	
	/**
	 * @var string Request hostname
	 */
	private $host;
	
	/**
	 * @var string Request path
	 */
	private $path;
	
	/**
	 * @var string Request method
	 */
	private $method;
	
	/**
	 * @var array Request data
	 */
	protected $data = array(
		'get'    => array(),
		'post'   => array(),
		'cookie' => array(),
		'file'   => array(),
		'server' => array(),
		'header' => array()
	);
	
	protected static $caseInsensitive = array('server', 'header');
	
	/**
	 * @var \Darya\Http\SessionInterface
	 */
	protected $session;
	
	/**
	 * @var \Darya\Routing\Router Router that matched this request
	 */
	public $router;
	
	/**
	 * @var \Darya\Routing\Route Route that this request was matched with
	 */
	public $route;
	
	/**
	 * Create a new request using PHP's super globals.
	 * 
	 * @param \Darya\Http\SessionInterface $session [optional]
	 * @return \Darya\Http\Request
	 */
	public static function createFromGlobals(SessionInterface $session = null) {
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'get';
		
		$request = new static($uri, $method, array(
			'get'    => $_GET,
			'post'   => $_POST,
			'cookie' => $_COOKIE,
			'file'   => $_FILES,
			'server' => $_SERVER,
			'header' => static::headersFromGlobals($_SERVER)
		));
		
		if ($session) {
			$request->setSession($session);
		}
		
		return $request;
	}
	
	/**
	 * Extract HTTP request headers from a given set of $_SERVER globals.
	 * 
	 * @param array $server
	 * @return array
	 */
	public static function headersFromGlobals(array $server) {
		$headers = array();
		
		foreach ($server as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$key = strtolower(substr($key, 5));
				$key = ucwords(str_replace('_', ' ', $key));
				$key = str_replace(' ', '-', $key);
				$headers[$key] = $value;
			}
		}
		
		return $headers;
	}
	
	/**
	 * Parse the given URI and return its components.
	 * 
	 * Any components not satisfied will be null instead of non-existent, so you
	 * can safely expect the keys 'scheme', 'host', 'port', 'user', 'pass',
	 * 'query' and 'fragment' to exist.
	 * 
	 * @param string $uri
	 * @return array
	 */
	protected static function parseUri($uri) {
		return array_merge(array(
			'scheme' => null,
			'host'   => null,
			'port'   => null,
			'user'   => null,
			'pass'   => null,
			'query'  => null,
			'fragment' => null
		), parse_url($uri));
	}
	
	/**
	 * Parse the given query string and return its key value pairs.
	 * 
	 * @param string $query
	 * @return array
	 */
	protected static function parseQuery($query) {
		$values = array();
		parse_str($query, $values);
		
		return $values;
	}
	
	/**
	 * Determine whether the given data type's keys can be treated
	 * case-insensitively.
	 * 
	 * @param string $type
	 * @return bool
	 */
	protected static function caseInsensitiveDataType($type) {
		return in_array($type, static::$caseInsensitive);
	}
	
	/**
	 * Prepare the given request data where necessary.
	 * 
	 * Lower-cases keys of `server` and `header` data so they can be treated
	 * case-insensitively.
	 * 
	 * @param array $data
	 * @return array
	 */
	protected static function prepareData(array $data) {
		foreach (array_keys($data) as $type) {
			if (static::caseInsensitiveDataType($type)) {
				$data[$type] = array_change_key_case($data[$type]);
			}
		}
		
		return $data;
	}
	
	/**
	 * Instantiate a new request with the given data.
	 * 
	 * Expects $data to have keys such as 'get', 'post', 'cookie', 'file',
	 * 'server', 'header' and the same general structure as PHP superglobals.
	 * 
	 * @param string $uri
	 * @param string $method
	 * @param array  $data
	 */
	public function __construct($uri, $method = 'get', $data = array()) {
		foreach (static::prepareData($data) as $type => $values) {
			$type = strtolower($type);
			
			if (is_array($values) && is_array($this->data[$type])) {
				$values = array_merge($values, $this->data[$type]);
			}
			
			$this->data[$type] = $values;
		}
		
		$components = static::parseUri($uri);
		$path = $components['path'];
		
		$this->data['get'] = array_merge(
			$this->data['get'],
			static::parseQuery($components['query'])
		);
		
		$this->uri = $uri;
		$this->host = $host;
		$this->path = $path;
		$this->method = strtolower($method);
		
		$this->data['server']['path_info'] = $path;
		$this->data['server']['request_uri'] = $uri;
		$this->data['server']['request_method'] = $method;
	}
	
	/**
	 * Retrieve request data of the given type using the given key.
	 * 
	 * If no key is set, all request data of the given type will be returned. If
	 * neither are set, all request data will be returned.
	 * 
	 * @param string $type [optional]
	 * @param string $key  [optional]
	 * @return mixed
	 */
	public function data($type = null, $key = null) {
		$type = strtolower($type);
		
		if (isset($this->data[$type])) {
			if (static::caseInsensitiveDataType($type)) {
				$key = strtolower($key);
			}
			
			if (!empty($key)) {
				return isset($this->data[$type][$key]) ? $this->data[$type][$key] : null;
			}
			
			return $this->data[$type];
		}
		
		return $this->data;
	}
	
	/**
	 * Magic method implementation that provides read-only array access to
	 * request data.
	 * 
	 * @param string $property
	 * @return array
	 */
	public function __get($property) {
		return $this->data($property);
	}
	
	/**
	 * Magic method implementation that provides read-only functional access
	 * to request data.
	 * 
	 * @param string $method
	 * @param array  $args
	 */
	public function __call($method, $args) {
		return count($args) ? $this->data($method, $args[0]) : $this->data($method);
	}
	
	/**
	 * Determine whether a given parameter is set in the request's post or get
	 * data.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has($key) {
		return isset($this->data['get'][$key]) || isset($this->data['post'][$key]);
	}
	
	/**
	 * Retrieve a parameter from either the post or get data of the request,
	 * checking post data if the request method is post.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function any($key = null) {
		return $this->method('post') && isset($this->data['post'][$key]) ? $this->post($key) : $this->get($key);
	}
	
	/**
	 * Retrieve the URI of the request.
	 * 
	 * @return string
	 */
	public function uri() {
		return $this->uri;
	}
	
	/**
	 * Retrieve the hostname of the request.
	 * 
	 * @return string
	 */
	public function host() {
		return $this->host;
	}
	
	/**
	 * Retrieve the path of the request.
	 * 
	 * @return string
	 */
	public function path() {
		return $this->path;
	}
	
	/**
	 * Retrieve the method of the request or, if $method is set, determine
	 * whether the method of the request is the same as the given method.
	 * 
	 * @param string $method [optional]
	 * @return string|bool
	 */
	public function method($method = null) {
		return $method ? strtolower($this->method) == strtolower($method) : $this->method;
	}
	
	/**
	 * Retrieve the IP address of the client that made the request.
	 * 
	 * @return string
	 */
	public function ip() {
		return $this->server('remote_addr');
	}
	
	/**
	 * Determine whether this is an ajax Request. This is determined by 'get' or
	 * 'post' data having an ajax parameter set or the 'X-Requested-With'
	 * parameter having the 'XMLHttpRequest' value.
	 * 
	 * @return bool
	 */
	public function ajax() {
		return $this->has('ajax')
		|| strtolower($this->server('http_x_requested_with')) == 'xmlhttprequest'
		|| strtolower($this->header('x-requested-with')) == 'xmlhttprequest';
	}
	
	/**
	 * Determine whether this Request has a session interface.
	 * 
	 * @return bool
	 */
	public function hasSession() {
		return !is_null($this->session);
	}
	
	/**
	 * Set the session interface for the request. Starts the session if it
	 * hasn't been already.
	 * 
	 * @param \Darya\Http\SessionInterface $session
	 */
	public function setSession(SessionInterface $session) {
		if (!$session->started()) {
			$session->start();
		}
		
		$this->session = $session;
		
		$this->data['session'] = $this->session;
	}
	
	/**
	 * Flash data with the given key to the session.
	 * 
	 * @param string       $key    Flash data key
	 * @param string|array $values A single value or set of values to add
	 * @return bool
	 */
	public function flash($key, $values) {
		if ($this->hasSession()) {
			$flash = $this->session->get('flash') ?: array();
			
			foreach ((array) $values as $value) {
				if (!is_null($value)) {
					$flash[$key][] = $value;
				}
			}
			
			$this->session->set('flash', $flash);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Retrieve and clear flashed data with the given key from the session. If
	 * no key is given, all data is retrieved and cleared.
	 * 
	 * Returns an empty array if this request has no session or flash variables
	 * were not found with the given key.
	 * 
	 * @param string $key [optional] Flash data key
	 * @return array
	 */
	public function flashes($key = null) {
		$data = array();
		
		if ($this->hasSession()) {
			$flash = $this->session->get('flash');
			
			if (!empty($key)) {
				if (isset($flash[$key])) {
					$data = $flash[$key];
					unset($flash[$key]);
				}
			} else {
				$data = $flash;
				$flash = array();
			}
			
			$this->session->set('flash', $flash);
		}
		
		return $data;
	}
	
	/**
	 * Transforms post request data of the form entity[property][n] to the form
	 * entity[n][property].
	 * 
	 * @param string $key Entity key (post parameter name)
	 * @return array
	 */
	public function postObjectData($key = null) {
		$post = $this->post($key);
		$data = array();
		
		if (is_array($post)) {
			foreach ($post as $field => $keys) {
				foreach ($keys as $key => $value) {
					$data[$key][$field] = $value;
				}
			}
		}
		
		return $data;
	}
	
}
