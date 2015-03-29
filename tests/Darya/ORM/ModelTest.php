<?php
use Darya\ORM\Model;

class ModelTest extends PHPUnit_Framework_TestCase {
	
	public function testAttributes() {
		$model = new ModelStub(array(
			'id'   => 1,
			'name' => 'Stub'
		));
		
		$this->assertEquals('Stub', $model->get('name'));
		$this->assertEquals($model->get('name'), $model['name']);
		$this->assertEquals($model['name'], $model->name);
		
		$model->set('name', 'Other Name');
		$this->assertEquals('Other Name', $model->name);
		
		$model->name = 'Another Name';
		$this->assertEquals('Another Name', $model->name);
		
		$this->assertEquals(1, $model->id());
		$this->assertEquals($model->id(), $model->id);
		
		$model->id = 2;
		$this->assertEquals(2, $model->id);
		$this->assertEquals($model->id, $model->id());
	}
	
	public function testDifferentKey() {
		$model = new KeyStub(array('id' => 1));
		$this->assertTrue(isset($model->id));
		$this->assertTrue(isset($model->stub_id));
		$this->assertEquals(1, $model->id);
		$this->assertEquals($model->id, $model->stub_id);
		
		$model->id = 2;
		$this->assertEquals(2, $model->id);
		$this->assertEquals($model->id, $model->stub_id);
		
		unset($model->id);
		$this->assertFalse(isset($model->id));
		$this->assertFalse(isset($model->stub_id));
	}
	
	public function testAttributeMutation() {
		$model = new AttributeStub(array(
			'value'   => 'something',
			'date'    => '2015-03-28 23:54',
			'options' => array('some' => 'value')
		));
		
		$this->assertTrue(isset($model->value));
		$this->assertTrue(isset($model->date));
		$this->assertTrue(isset($model->options));
		
		$this->assertEquals('something', $model->value);
		$this->assertEquals('2015-03-28 23:54:00', $model->date);
		$this->assertEquals(array('some' => 'value'), $model->options);
		
		$this->assertEquals(array(
			'value'   => 'something',
			'date'    => strtotime('2015-03-28 23:54'),
			'options' => '{"some":"value"}'
		), $model->data());
	}
	
}

class ModelStub extends Model {
	
}

class KeyStub extends Model {
	
	protected $key = 'stub_id';
	
}

class AttributeStub extends Model {
	
	protected $attributes = array(
		'value' => 'string',
		'date'  => 'datetime',
		'options'  => 'json'
	);
	
}
