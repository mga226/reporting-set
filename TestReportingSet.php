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

	function testGroupBySorts(){

		$this->set->groupBy('sex');


		// correct number of groups?
		$this->assertEquals($this->set->getGroupValues(), array('f','m'));

	}



	function testSetGroupValues(){

		$this->set->groupBy('sex');
		
		$initialGroups = $this->set->getGroups();


		$this->set->setGroupValues(array('m','x'));
		
		$this->assertContainsOnly('ReportingSet',$this->set->getGroups());


		// correct number of groups?
		$this->assertEquals($this->set->getGroupValues(), array('m','x'));

	}


	function testSortGroupsBy(){

		$this->set->groupBy('sex');
		$this->set->sortGroups('age','sum','desc');
		$this->assertEquals($this->set->getGroupValues(), array('f','m'));

		$this->set->sortGroups('age','sum','asc');
		$this->assertEquals($this->set->getGroupValues(), array('m','f'));


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



	function testAggregations(){
		
		$this->assertEquals($this->set->getCount('name'), 3);
		$this->assertEquals($this->set->getAverage('age'), (31+34+32)/3);
		$this->assertEquals($this->set->getSum('age'), (31+34+32));

		$this->set->filter(array('sex'=>'m'));
		$this->assertEquals($this->set->getCount('name'), 2);
		$this->assertEquals($this->set->getAverage('age'), (31+32)/2);
		$this->assertEquals($this->set->getSum('age'), (31+32));


		$this->set->clearFilters();
		$this->assertEquals($this->set->getCount('name'), 3);
		$this->assertEquals($this->set->getAverage('age'), (31+34+32)/3);
		$this->assertEquals($this->set->getSum('age'), (31+34+32));


	}


	function testAggregationsAreUnaffectedByGrouping(){

		$this->set->groupBy('name');

		
		$this->assertEquals($this->set->getCount('name'), 3);
		$this->assertEquals($this->set->getAverage('age'), (31+34+32)/3);
		$this->assertEquals($this->set->getSum('age'), (31+34+32));

		$this->set->groupBy('sex');

		$this->set->filter(array('sex'=>'m'));
		$this->assertEquals($this->set->getCount('name'), 2);
		$this->assertEquals($this->set->getAverage('age'), (31+32)/2);
		$this->assertEquals($this->set->getSum('age'), (31+32));

		$this->set->ungroup();

		$this->set->clearFilters();
		$this->assertEquals($this->set->getCount('name'), 3);
		$this->assertEquals($this->set->getAverage('age'), (31+34+32)/3);
		$this->assertEquals($this->set->getSum('age'), (31+34+32));


	}




}