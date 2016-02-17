<?php
namespace Darya\Http;

use Darya\Http\Session;

/**
 * Darya's HTTP request representation.
 * 
 * @property array $get
 * @property array $post
 * @property array $cookie
 * @property array $file
 * @property array $server
 * @property array $header
 * @method mixed get(string $key, mixed $default = null)
 * @method mixed post(string $key, mixed $default = null)
 * @method mixed cookie(string $key, mixed $default = null)
 * @method mixed file(string $key, mixed $default = null)
 * @method mixed server(string $key, mixed $default = null)
 * @method mixed header(string $key, mixed $default = null)
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Request {
	
	/**
	 * @var array Request data types to treat case-insensitively
	 */
	protected static $caseInsensitive = array('server', 'header');
	
	/**
	 * @var array Request data
	 */
	protected $data = array(
		'get'     => array(),
		'post'    => array(),
		'cookie'  => array(),
		'file'    => array(),
		'server'  => array(),
		'header'  => array(),
		'session' => null
	);
	
	/**
	 * @var string Request body content
	 */
	protected $content;
	
	/**
	 * @var \Darya\Http\Session
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
	 * Determine whether the given data type's keys can be treated
	 * case-insensitively.
	 * 
	 * @param string $type
	 * @return bool
	 */
	protected static function isCaseInsensitive($type) {
		return in_array($type, static::$caseInsensitive);
	}
	
	/**
	 * Prepare the given request data where necessary.
	 * 
	 * Lowercases data type keys and the keys of `server` and `header` data so
	 * they can be treated case-insensitively.
	 * 
	 * Any expected data types not satisfied will contain an empty array apart
	 * from `session`, which will be null.
	 * 
	 * @param array $data
	 * @return array
	 */
	protected static function prepareData(array $data) {
		$data = array_change_key_case($data);
		
		foreach (array_keys($data) as $type) {
			if (static::isCaseInsensitive($type)) {
				$data[$type] = array_change_key_case($data[$type]);
			}
		}
		
		return array_merge(array(
			'get'     => array(),
			'post'    => array(),
			'cookie'  => array(),
			'file'    => array(),
			'server'  => array(),
			'header'  => array(),
			'session' => null
		), $data);
	}
	
	
	/**
	 * Parse the given URI and return its components.
	 * 
	 * Any components not satisfied will be null instead of non-existent, so you
	 * can safely expect the keys 'scheme', 'host', 'port', 'user', 'pass',
	 * 'query' and 'fragment' to exist.
	 * 
	 * @param string $url
	 * @return array
	 */
	protected static function parseUrl($url) {
		$components = parse_url($url);
		
		return array_merge(array(
			'scheme' => null,
			'host'   => null,
			'port'   => null,
			'user'   => null,
			'pass'   => null,
			'path'   => null,
			'query'  => null,
			'fragment' => null
		), $components ?: array());
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
	 * Create a new request with the given URL, method and data.
	 * 
	 * @param string  $url
	 * @param string  $method  [optional]
	 * @param array   $data    [optional]
	 * @param Session $session [optional]
	 * @return Request
	 */
	public static function create($url, $method = 'GET', $data = array(), Session $session = null) {
		$components = static::parseUrl($url);
		$data = static::prepareData($data);
		
		$data['get'] = array_merge(
			$data['get'],
			static::parseQuery($components['query'])
		);
		
		if ($components['host']) {
			$data['server']['http_host'] = $components['host'];
			$data['server']['server_name'] = $components['host'];
		}
		
		if ($components['path']) {
			$data['server']['path_info'] = $components['path'];
			$data['server']['request_uri'] = $components['path'];
		}
		
		$data['server']['request_method'] = strtoupper($method);
		
		if ($components['query']) {
			$data['server']['request_uri'] .= '?' . $components['query'];
		}
		
		$request = new Request(
			$data['get'],
			$data['post'],
			$data['cookie'],
			$data['file'],
			$data['server'],
			$data['header']
		);
		
		$request->setSession($session);
		
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
	 * Create a new request using PHP's super globals.
	 * 
	 * @param \Darya\Http\Session $session [optional]
	 * @return \Darya\Http\Request
	 */
	public static function createFromGlobals(Session $session = null) {
		$request = Request::create($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], array(
			'get'    => $_GET,
			'post'   => $_POST,
			'cookie' => $_COOKIE,
			'file'   => $_FILES,
			'server' => $_SERVER,
			'header' => static::headersFromGlobals($_SERVER)
		), $session);
		
		return $request;
	}
	
	/**
	 * Instantiate a new request with the given data.
	 * 
	 * Expects request data in the same format as PHP superglobals.
	 * 
	 * @param array $get
	 * @param array $post
	 * @param array $cookie
	 * @param array $file
	 * @param array $server
	 * @param array $header
	 */
	public function __construct(array $get, array $post, array $cookie, array $file, array $server, array $header) {
		$this->data = static::prepareData(compact('get', 'post', 'cookie', 'file', 'server', 'header'));
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
	 * @param \Darya\Http\Session $session
	 */
	public function setSession(Session $session = null) {
		if (is_object($session) && !$session->started()) {
			$session->start();
		}
		
		$this->session = $session;
		$this->data['session'] = $this->session;
	}
	
	/**
	 * Retrieve request data of the given type using the given key.
	 * 
	 * If $key is not set, all request data of the given type will be returned.
	 * 
	 * If neither $type or $key are set, all request data will be returned.
	 * 
	 * If a $default value is given along with $key, it is returned if $key is
	 * not set in the data of the given $type.
	 * 
	 * @param string $type    [optional]
	 * @param string $key     [optional]
	 * @param mixed  $default [optional]
	 * @return mixed
	 */
	public function data($type = null, $key = null, $default = null) {
		$type = strtolower($type);
		
		if (isset($this->data[$type])) {
			if (static::isCaseInsensitive($type)) {
				$key = strtolower($key);
			}
			
			if (!empty($key)) {
				return isset($this->data[$type][$key]) ? $this->data[$type][$key] : $default;
			}
			
			return $this->data[$type];
		}
		
		return $this->data;
	}
	
	/**
	 * Dynamically retrieve all request data of the given type.
	 * 
	 * @param string $property
	 * @return array
	 */
	public function __get($property) {
		return $this->data($property);
	}
	
	/**
	 * Dynamically retrieve request data.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 */
	public function __call($method, $arguments) {
		$arguments = array_merge(array($method), array_slice($arguments, 0, 2));
		
		return call_user_func_array(array($this, 'data'), $arguments);
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
		return $this->server('request_uri');
	}
	
	/**
	 * Retrieve the hostname of the request.
	 * 
	 * @return string
	 */
	public function host() {
		return $this->server('server_name') ?: $this->server('server_addr');
	}
	
	/**
	 * Retrieve the path of the request.
	 * 
	 * @return string
	 */
	public function path() {
		$path = $this->server('path_info');
		
		if ($path) {
			return $path;
		}
		
		$components = static::parseUrl($this->uri());
		
		return $components['path'];
	}
	
	/**
	 * Retrieve the method of the request or determine whether the method of the
	 * request is the same as the one given.
	 * 
	 * @param string $method [optional]
	 * @return string|bool
	 */
	public function method($method = null) {
		$method = strtolower($method);
		$requestMethod = strtolower($this->server('request_method'));
		
		return $method ? $requestMethod == $method : $this->server('request_method');
	}
	
	/**
	 * Retrieve the request body content.
	 * 
	 * @return string
	 */
	public function content() {
		if ($this->content === null) {
			$this->content = file_get_contents('php://input');
		}
		
		return $this->content;
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
