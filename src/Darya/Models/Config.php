<?php
namespace Darya\Models;

use Darya\Mvc\Model;

/**
 * A model for storing application configuration data.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Config extends Model {
	
	public function set($key, $value) {
		if (!$this->$key) {
			parent::set($key, $value);
		}
	}
	
}