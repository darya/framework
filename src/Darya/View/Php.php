<?php
namespace Darya\View;

/**
 * Darya's simple PHP view.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Php extends AbstractView
{
	/**
	 * Render the template.
	 *
	 * @return string
	 */
	public function render()
	{
		// Ensure that the directory exists
		if (!is_dir($this->directory)) {
			throw new \Exception("Could not find directory when rendering view: \"{$this->directory}\"");
		}

		// Ensure that the selected template file exists
		$path = $this->directory . '/' . $this->file;

		if (!is_file($path)) {
			throw new \Exception("Could not find file when rendering view: \"$path\"");
		}

		// Change the working directory
		$cwd = null;

		if ($this->directory) {
			$cwd = getcwd();
			chdir($this->directory);
		}

		// Change error reporting
		$error_reporting = error_reporting();
		error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);

		// Extract view arguments
		extract($this->arguments);

		// Run the template
		ob_start();
		include $this->file;
		$output = ob_get_clean();

		// Restore the working directory
		if ($this->directory && $cwd) {
			chdir($cwd);
		}

		// Restore error reporting
		error_reporting($error_reporting);

		return $output;
	}
}
