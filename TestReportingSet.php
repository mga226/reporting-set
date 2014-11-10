<?php

require_once('ReportingSet.php');

class TestReportingSet extends PHPUnit_Framework_TestCase{

	var $data;
	var $set;

	function setUp(){
		$this->data = array(
			array(
				'id' => 1,
				'name' => 'Mike',
				'sex' => 'm',
				'age' => 31,
				'address' => array(
					'city' =>'brooklyn'
				)
			),
			array(
				'id' => 2,
				'name' => 'Katie',
				'sex' => 'f',
				'age' => 34,
				'address' => array(
					'city' =>'brooklyn'
				),
				'order_id' => 20
			),
			array(
				'id' => 3,
				'name' => 'John',
				'sex' => 'm',
				'age' => 32,
				'address' => array(
					'city' =>'nyc'
				)
			),
		);
		$this->set = new ReportingSet($this->data);

	}

	function testGetItems(){
		$this->assertEquals( $this->data, $this->set->getItems());
	}

	function testGroupBy(){

		$this->set->groupBy('sex');
		
		// correct number of groups?
		$this->assertEquals(count($this->set->getGroups()),2);

		// each group is a ReportingSet?
		$this->assertContainsOnly('ReportingSet',$this->set->getGroups());

	}

	function testUngroup(){

		$this->set->groupBy('sex');
		$this->set->ungroup();

		// each group is a ReportingSet?
		$this->assertEmpty($this->set->getGroups());
		
	}

	function testHasGroups(){

		$this->set->groupBy('sex');
		
		// each group is a ReportingSet?
		$this->assertTrue($this->set->hasGroups());

		$this->set->ungroup();
		
		// each group is a ReportingSet?
		$this->assertFalse($this->set->hasGroups());


		
	}

	function testFilter(){

		$this->set->filter(array('sex' => 'm'));

		$expected = $this->data;
		unset($expected[1]);
		$this->assertEquals($this->set->getItems(), $expected);

	}

	function testClearFilters(){

		$this->set->clearFilters();
		$this->assertEquals( $this->data, $this->set->getItems());

	}


	function testSearch(){

		$this->set->search('name', 'oh');

		$expected = $this->data;
		unset($expected[0]);
		unset($expected[1]);
		
		$this->assertEquals($this->set->getItems(), $expected);
	
	}

	function testFlattenItems(){
		$arr = array(
			array(
				'a'=>1,
				'b'=>2,
				'c'=>3,
				'd'=>4
			),
			array(
				'xxx' => array(
				'a'=>1,
				),
				'yyy' => array(
				'b'=>2,
				),
				'zzz' => array(
				'c'=>3
				),
				'd' => 4
			),
		);
		$expected = array(
			$arr[0],
			$arr[0]
		);

		$this->assertEquals(ReportingSet::flattenItems($arr), $expected);

	}




}