<?php
namespace Darya\View;

/**
 * Darya's interface for views.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ViewInterface {
	
	/**
	 * Select a template and optionally assign variables and configuration.
	 * 
	 * @param string $file 	 The template file to be used
	 * @param array  $vars 	 [optional] Variables to assign to the template immediately
	 * @param array  $config [optional] Config variables for the view
	 */
	public function select($file, array $vars = array(), array $config = array());
	
	/**
	 * Get and optionally set view configuration variables.
	 * 
	 * This merges given variables with any that have been previously set.
	 * 
	 * @param array $config [optional]
	 * @return array
	 */
	public function config(array $config = array());
	
	/**
	 * Assign an array of key/value pairs to the template.
	 * 
	 * @param array $vars
	 */
	public function assign(array $vars);
	
	/**
	 * Get all variables or a specific variable assigned to the template.
	 * 
	 * @param string $key [optional] Key of the variable to return
	 * @return mixed The value of variable $key if set, all variables otherwise
	 */
	public function assigned($key = null);
	
	/**
	 * Render the view.
	 * 
	 * @return string The rendered view
	 */
	public function render();
	
}