<?php
namespace Darya\Mvc;

/**
 * Darya's interface for views.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
interface ViewInterface {
	
	public function select($file, $vars = array(), $config = array());
	
	public function getConfig();
	
	public function setConfig(array $config);
	
	public function assign(array $vars);
	
	public function getAssigned($key = null);
	
	public function render();
	
}