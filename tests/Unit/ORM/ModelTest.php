<?php
namespace Darya\Tests\Unit\ORM;

use PHPUnit\Framework\TestCase;
use Darya\ORM\Model;

class ModelTest extends TestCase {

	protected function generationData() {
		return array(
			array(
				'value'    => 'something',
				'date'     => '2015-05-15 11:28',
				'options'  => array('one', 'two')
			),
			array(
				'value'    => 'another_thing',
				'date'     => '2015-05-15 11:30',
				'options'  => null
			)
		);
	}

	protected function expectedArrayData() {
		return array(
			array(
				'value'    => 'something',
				'date'     => '2015-05-15 11:28:00',
				'options'  => array('one', 'two')
			),
			array(
				'value'    => 'another_thing',
				'date'     => '2015-05-15 11:30:00',
				'options'  => null
			)
		);
	}

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
		$model = new KeyStub(array('stub_id' => 1));
		$this->assertTrue(isset($model->stub_id));
		$this->assertEquals(1, $model->id());
		$this->assertEquals($model->id(), $model->stub_id);

		$model->stub_id = 2;
		$this->assertEquals(2, $model->id());
		$this->assertEquals($model->id(), $model->stub_id);

		unset($model->stub_id);
		$this->assertFalse(isset($model->stub_id));
	}

	public function testAttributeNames() {
		$model = new AttributeStub;

		$this->assertEquals(array('value', 'date', 'options'), $model->attributes());

		$model = new ModelStub;

		$this->assertEmpty($model->attributes());

		$model->setMany(array('value' => 1, 'options' => array()));

		$this->assertEquals(array('value', 'options'), $model->attributes());
	}

	public function testAttributeTypes() {
		$model = new AttributeStub;

		$expected = array(
			'value' => 'string',
			'date'  => 'datetime',
			'options'  => 'json'
		);

		$this->assertEquals($expected, $model->attributeTypes());
	}

	public function testAttributeMutation() {
		$data = array(
			'value'   => 'something',
			'date'    => '2015-03-28 23:54',
			'options' => array('some' => 'value')
		);

		$expectedData = array(
			'value'   => 'something',
			'date'    => '2015-03-28 23:54:00',
			'options' => array('some' => 'value')
		);

		$expectedRawData = array(
			'value'   => 'something',
			'date'    => strtotime('2015-03-28 23:54'),
			'options' => '{"some":"value"}'
		);

		$model = new AttributeStub($data);

		$this->assertTrue(isset($model->value));
		$this->assertTrue(isset($model->date));
		$this->assertTrue(isset($model->options));

		$this->assertEquals('something', $model->value);
		$this->assertEquals('2015-03-28 23:54:00', $model->date);
		$this->assertEquals(array('some' => 'value'), $model->options);

		$this->assertEquals($expectedData, $model->data());

		$this->assertEquals($expectedRawData, $model->rawData());
	}

	protected function assertGeneratedModels($models) {
		$this->assertEquals('something', $models[0]->value);
		$this->assertEquals('2015-05-15 11:28:00', $models[0]->date);
		$this->assertEquals(array('one', 'two'), $models[0]->options);

		$this->assertEquals('another_thing', $models[1]->value);
		$this->assertEquals('2015-05-15 11:30:00', $models[1]->date);
		$this->assertEquals(null, $models[1]->options);
	}

	public function testGeneration() {
		$data = $this->generationData();

		$models = AttributeStub::generate($data);

		$this->assertGeneratedModels($models);

		$this->assertEquals(array_keys($data[0]), array_keys($models[0]->changed()));
	}

	public function testHydration() {
		$data = $this->generationData();

		$hydrated = AttributeStub::hydrate($data);

		$this->assertGeneratedModels($hydrated);

		$this->assertEmpty($hydrated[0]->changed());
	}

	public function testToArray() {
		$data = $this->generationData();
		$expected = $this->expectedArrayData();

		$models = AttributeStub::hydrate($data);

		$this->assertEquals($expected[0], $models[0]->toArray());
		$this->assertEquals($expected[1], $models[1]->toArray());
		$this->assertEquals($expected, Model::convertToArray($models));
	}

	public function testToJson() {
		$expectedData = $this->expectedArrayData();

		$models = AttributeStub::hydrate($this->generationData());

		$expected = json_encode($expectedData);
		$actual = AttributeStub::convertToJson($models);
		// $actual = json_encode($models); // Can't do this without a collection class

		$this->assertEquals($expected, $actual);
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
