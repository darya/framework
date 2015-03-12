<?php
namespace Darya\Mvc;

/**
 * Darya's simple PHP view.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class PhpView extends View {
	
	/**
	 * Render the template.
	 * 
	 * @return string
	 */
	public function render() {
		chdir($this->directory);
		extract($this->vars);
		
		$error_reporting = error_reporting();
		error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);
		
		ob_start();
		include $this->file;
		$output = ob_get_clean();
		
		error_reporting($error_reporting);
		return $output;
	}
	
}
