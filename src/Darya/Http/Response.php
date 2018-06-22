<?php
namespace Darya\Http;

/**
 * Darya's HTTP response representation.
 *
 * TODO: Support content streams.
 *
 * @property-read Cookies $cookies
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Response
{
	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	private $status = 200;

	/**
	 * HTTP headers.
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Cookie key/values.
	 *
	 * @var Cookies
	 */
	private $cookies;

	/**
	 * Response content.
	 *
	 * @var string
	 */
	private $content = null;

	/**
	 * Whether the response headers have been sent.
	 *
	 * @var bool
	 */
	private $headersSent = false;

	/**
	 * Whether the response content has been sent.
	 *
	 * @var bool
	 */
	private $contentSent = false;

	/**
	 * Whether the response has been redirected.
	 *
	 * @var bool
	 */
	private $redirected = false;

	/**
	 * Properties that can be read dynamically.
	 *
	 * @var array
	 */
	private $properties = array(
		'status', 'headers', 'cookies', 'content', 'redirected'
	);

	/**
	 * Prepare the given response content as a string.
	 *
	 * Invokes `__toString()` on objects if exposed. Encodes arrays as JSON.
	 * Anything else is casted to a string.
	 *
	 * @param mixed $content
	 * @return string
	 */
	public static function prepareContent($content)
	{
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
	 * Instantiate a new response with the optionally given content and headers.
	 *
	 * @param mixed $content [optional]
	 * @param array $headers [optional]
	 */
	public function __construct($content = null, array $headers = array())
	{
		if ($content !== null) {
			$this->content($content);
		}

		$this->headers($headers);

		$this->cookies = new Cookies;

		$this->properties = array_flip($this->properties);
	}

	/**
	 * Dynamically retrieve a property.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->properties[$property])) {
			return $this->$property;
		}
	}

	/**
	 * Get and optionally set the HTTP status code of the response.
	 *
	 * @param int|string $status [optional]
	 * @return int
	 */
	public function status($status = null)
	{
		if (is_numeric($status)) {
			$this->status = (int) $status;
		}

		return $this->status;
	}

	/**
	 * Add a header to send with the response.
	 *
	 * @param string $header
	 */
	public function header($header)
	{
		$header = (string) $header;

		if (strlen($header)) {
			list($name, $value) = array_pad(explode(':', $header, 2), 2, null);
			$this->headers[$name] = ltrim($value);
		}
	}

	/**
	 * Retrieve and optionally add headers to send with the response.
	 *
	 * @param array|string $headers [optional]
	 * @return array
	 */
	public function headers($headers = array())
	{
		foreach ((array) $headers as $header) {
			$this->header($header);
		}

		return $this->headers;
	}

	/**
	 * Get and optionally set the response content.
	 *
	 * @param mixed $content [optional]
	 * @return string
	 */
	public function content($content = null)
	{
		if (is_array($content)) {
			$this->header('Content-Type: application/json');
		}

		if ($content !== null) {
			$this->content = $content;
		}

		return $this->content;
	}

	/**
	 * Retrieve the response content as a string.
	 *
	 * @return string
	 */
	public function body()
	{
		return static::prepareContent($this->content);
	}

	/**
	 * Determines whether any response content has been set.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return $this->content !== null && $this->content !== false;
	}

	/**
	 * Redirect the response to another location.
	 *
	 * This redirect will only happen when the response headers have been sent.
	 *
	 * @param string $url
	 * @return $this
	 */
	public function redirect($url)
	{
		$this->header("Location: $url");
		$this->redirected = true;

		return $this;
	}

	/**
	 * Determines whether the response has been redirected or not.
	 *
	 * @return bool
	 */
	public function redirected()
	{
		return $this->redirected;
	}

	/**
	 * Determine whether any headers have been sent by this response or another.
	 *
	 * @return bool
	 */
	protected function headersSent()
	{
		return $this->headersSent || headers_sent();
	}

	/**
	 * Determine whether the response content has been sent.
	 *
	 * @return bool
	 */
	 protected function contentSent()
	 {
	 	return $this->contentSent;
	 }

	/**
	 * Sends the current HTTP status of the response.
	 */
	protected function sendStatus()
	{
		if (function_exists('http_response_code')) {
			http_response_code($this->status);
		} else {
			header(':', true, $this->status);
		}
	}

	/**
	 * Sends all the currently set cookies.
	 */
	protected function sendCookies()
	{
		$this->cookies->send();
	}

	/**
	 * Send the response headers to the client, provided that they have not yet
	 * been sent.
	 *
	 * HTTP status and cookies are sent before the headers.
	 *
	 * @return bool
	 */
	public function sendHeaders()
	{
		if (!$this->headersSent()) {
			$this->sendStatus();
			$this->sendCookies();

			foreach ($this->headers as $name => $value) {
				header("$name: $value", true);
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
	public function sendContent()
	{
		if ($this->headersSent() && !$this->contentSent() && !$this->redirected) {
			echo $this->body();

			$this->contentSent = true;
		}

		return $this->contentSent();
	}

	/**
	 * Sends the response to the client.
	 *
	 * Sends the response headers and response content.
	 *
	 * If the response has been redirected, only headers will be sent.
	 */
	public function send()
	{
		$this->sendHeaders();

		if (!$this->redirected) {
			$this->sendContent();
		}
	}
}
