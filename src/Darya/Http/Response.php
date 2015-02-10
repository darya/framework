<?php
namespace Darya\Http;

/**
 * Darya's HTTP response representation.
 * 
 * TODO: Header key/value pairs.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Response {
	
	/**
	 * @var int HTTP status code
	 */
	private $status = 200;
	
	/**
	 * @var array HTTP headers
	 */
	private $headers = array();
	
	/**
	 * @var array Cookie key/values
	 */
	private $cookies = array();
	
	/**
	 * @var string Response content
	 */
	private $content = null;
	
	/**
	 * @var bool Whether the response headers have been sent
	 */
	private $headersSent = false;
	
	/**
	 * @var bool Whether the response content has been sent
	 */
	private $contentSent = false;
	
	/**
	 * @var bool Whether the response has been redirected
	 */
	private $redirected = false;
	
	/**
	 * Instantiate a new response with the given content and headers.
	 * 
	 * @param mixed $content
	 * @param array $headers
	 */
	public function __construct($content = null, $headers = array()) {
		if ($content) {
			$this->setContent($content);
		}
		
		if ($headers) {
			$this->addHeaders($headers);
		}
	}
	
	/**
	 * Get the HTTP status code of the response.
	 * 
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Set the HTTP status code of the response.
	 * 
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}
	
	/**
	 * Add a header to send with the response.
	 * 
	 * @param string $header
	 */
	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	/**
	 * Add headers to send with the response.
	 * 
	 * @param array $headers
	 */
	public function addHeaders(array $headers) {
		foreach ($headers as $header) {
			$this->headers[] = $header;
		}
	}
	
	/**
	 * Set a cookie to send with the response.
	 * 
	 * @param string $key
	 * @param string $value
	 * @param int $expire
	 */
	public function setCookie($key, $value, $expire, $path = '/') {
		$this->cookies[$key] = compact('value', 'expire', 'path');
	}
	
	/**
	 * Get the value of a cookie that's been added to the response.
	 * 
	 * @param string $key
	 * @return string
	 */
	public function getCookie($key) {
		return isset($this->cookies[$key]) && isset($this->cookies[$key]['value']) ? $this->cookies[$key]['value'] : null;
	}
	
	/**
	 * Add a cookie to be deleted with the response.
	 * 
	 * @param string $key
	 */
	public function deleteCookie($key) {
		if (isset($this->cookies[$key])) {
			$this->cookies[$key]['value'] = '';
			$this->cookies[$key]['expire'] = 0;
		}
	}
	
	/**
	 * Prepare the given response content as a string.
	 * 
	 * Uses __toString() on objects if exposed. Encodes arrays as JSON.
	 * Everything else is casted to string.
	 * 
	 * @param mixed $content
	 * @return string
	 */
	public function prepareContent($content) {
		if (is_object($content) && method_exists($content, '__toString')) {
			$content = $content->__toString();
		} else if (is_array($content)) {
			$content = json_encode($content);
		} else {
			$content = (string) $content;
		}
		
		return $content;
	}
	
	/**
	 * Set the response content.
	 * 
	 * @param mixed $content
	 */
	public function setContent($content) {
		if (is_array($content)) {
			$this->addHeader('Content-Type: text/json');
		}
		
		$this->content = $this->prepareContent($content);
	}
	
	/**
	 * Determines whether any response content has been set.
	 * 
	 * @return bool
	 */
	public function hasContent() {
		return !is_null($this->content) && $this->content !== false;
	}
	
	/**
	 * Retrieve the current response content.
	 * 
	 * @return string
	 */
	public function getContent() {
		return $this->content;
	}
	
	/**
	 * Redirect the response to another location.
	 * 
	 * This redirect will only happen when the response headers have been sent.
	 * 
	 * @param string $url
	 * @return $this
	 */
	public function redirect($url) {
		$this->addHeader("Location: $url");
		$this->redirected = true;
		
		return $this;
	}
	
	/**
	 * Determines whether the response has been redirected or not.
	 * 
	 * @return bool
	 */
	public function redirected() {
		return $this->redirected;
	}
	
	/**
	 * Helper method for encapsulating setting the current HTTP status.
	 */
	protected function sendStatus() {
		if (function_exists('http_response_code')) {
			http_response_code($this->status);
		} else {
			header(':', true, $this->status);
		}
	}
	
	/**
	 * Sends all the currently set cookies.
	 */
	protected function sendCookies() {
		foreach ($this->cookies as $key => $values) {
			setcookie($key, $values['value'], $values['expire'], $values['path'] ?: '/');
		}
	}
	
	/**
	 * Send the response headers to the client, provided that they have not yet
	 * been sent.
	 * 
	 * HTTP status and cookies are sent before the headers.
	 * 
	 * @return bool
	 */
	public function sendHeaders() {
		if (!$this->headersSent && !headers_sent()) {
			$this->sendStatus();
			$this->sendCookies();
			
			foreach ($this->headers as $header) {
				header($header, true);
			}
			
			$this->headersSent = true;
		}
		
		return $this->headersSent;
	}
	
	/**
	 * Send the response content to the client.
	 * 
	 * This will only succeed if response headers have been sent, response
	 * content has not yet been sent, and the response has not been redirected.
	 * 
	 * @return bool
	 */
	public function sendContent() {
		if ($this->headersSent && !$this->contentSent && !$this->redirected) {
			echo $this->content;
			
			$this->contentSent = true;
		}
		
		return $this->contentSent;
	}
	
	/**
	 * Sends the response to the client.
	 * 
	 * Sends the response headers and response content.
	 * 
	 * If the response has been redirected, only headers will be sent.
	 */
	public function send() {
		$this->sendHeaders();
		
		if (!$this->redirected) {
			$this->sendContent();
		}
	}
	
}
