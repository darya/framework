<?php
namespace Darya\Http;

use Darya\Http\SessionInterface;

/**
 * Darya's HTTP request representation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Request {
	
	/**
	 * @var string Request URI
	 */
	private $uri;
	
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
	
	/**
	 * @var Darya\Http\SessionInterface
	 */
	protected $session;

	/**
	 * Create a new Request using PHP's super globals.
	 * 
	 * @param Darya\Http\SessionInterface $session [optional]
	 * @return Darya\Http\Request
	 */
	public static function createFromGlobals(SessionInterface $session = null) {
		$uri = $_SERVER['REQUEST_URI'];
		$method = strtolower($_SERVER['REQUEST_METHOD']);
		
		$request = new static($uri, $method, array(
			'get'    => $_GET,
			'post'   => $_POST,
			'cookie' => $_COOKIE,
			'file'   => $_FILES,
			'server' => $_SERVER,
			'header' => function_exists('getallheaders') ? getallheaders() : array()
		));
		
		if ($session) {
			$request->setSession($session);
		}
		
		return $request;
	}
	
	/**
	 * Create a new Request. Expects the elements of $data to have keys such
	 * as 'get', 'post', 'cookie', 'file', 'server', 'header'.
	 * 
	 * @param string $uri
	 * @param string $method
	 * @param array  $data
	 */
	public function __construct($uri, $method = 'get', $data = array()) {
		$this->uri = $uri;
		$this->method = $method;
		
		foreach ($data as $type => $values) {
			if (is_array($values) && is_array($this->data[$type])) {
				$values = array_merge($values, $this->data[$type]);
			}
			$this->data[$type] = $values;
		}
		
		$this->data['server']['REQUEST_URI'] = $uri;
		$this->data['server']['REQUEST_METHOD'] = $method;
	}
	
	/**
	 * Retrieve data of the given type with the given key. If no key is set, all
	 * data of the given type will be returned. If neither are set, all data
	 * will be returned.
	 */
	public function getData($type = null, $key = null) {
		if ($type) {
			$type = strtolower($type);
			if ($key) {
				return isset($this->data[$type][$key]) ? $this->data[$type][$key] : false;
			} else {
				return $this->data[$type];
			}
		} else {
			return $this->data;
		}
	}
	
	public function __get($property) {
		return property_exists($this, $property) ? $this->$property : $this->getData($property);
	}
	
	public function __call($method, $args) {
		return count($args) ? $this->getData($method, $args[0]) : $this->getData($method);
	}
	
	/**
	 * Retrieve a parameter from either the 'post' or 'get' data of the Request,
	 * with 'post' being checked first.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function any($key = null) {
		return $this->method('post') && isset($this->data['post'][$key]) ? $this->post($key) : $this->get($key);
	}
	
	/**
	 * Retrieve the URI of the Request.
	 * 
	 * @returns string
	 */
	public function uri() {
		return $this->uri;
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
	 * Retrieves the IP address of the client that made the request.
	 * 
	 * @return string
	 */
	public function ip() {
		return $this->server('REMOTE_ADDR');
	}
	
	/**
	 * Determine whether this is an ajax Request. This is determined by 'get' or
	 * 'post' data having an ajax parameter set or the 'X-Requested-With'
	 * parameter having the 'XMLHttpRequest' value.
	 * 
	 * @return bool
	 */
	public function ajax() {
		return isset($this->data['get']['ajax']) 
		|| isset($this->data['post']['ajax']) 
		|| $this->header('X-Requested-With') == 'XMLHttpRequest';
	}
	
	/**
	 * Determine whether this Request has a session interface.
	 * 
	 * @return bool
	 */
	public function hasSession() {
		return $this->session !== null;
	}
	
	/**
	 * Retrieve the session interface for the Request.
	 * 
	 * @return Darya\Http\SessionInterface
	 */
	public function getSession() {
		return $this->session;
	}
	
	/**
	 * Set the session interface for the Request. Starts the session if it
	 * hasn't been already.
	 * 
	 * @param Darya\Http\SessionInterface $session
	 */
	public function setSession(SessionInterface $session) {
		if (!$session->started()) {
			$session->start();
		}
		
		$this->session = $session;
	}
	
	/**
	 * Add flash data with the given key to the session.
	 * 
	 * @param string $key Flash data key
	 * @param string|array $value A single value or set of values to add
	 * @return bool
	 */
	public function flash($key, $values) {
		if ($this->hasSession()) {
			$flash = $this->session->get('flash') ?: array();
			
			foreach ((array)$values as $value) {
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
	 * Retrieve and clear flash data with the given key from the session. If no
	 * key is given, all data is retrieved and cleared. 
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

			if ($key) {
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
?>