<?php
namespace Darya\Mvc;

/**
 * Darya's simple PHP view (uses PHP as a templating language).
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class PhpView extends View {
	
	public function render() {
		chdir($this->dir);
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
