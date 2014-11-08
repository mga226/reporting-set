<?php

class ReportingSet {

	var $items;
	var $items_filtered;
	var $groups;

	var $aggregations = array();

	function __construct( $items = array() ){
		$this->items = $items;
	}


	public function group_by( $group_by ){

		$groups = $this->groups ? $this->groups : array($this);

		$items = $this->getItems();

		$groups = array();

		foreach( $items as $i=>$item ){

			if(!isset($groups[$item[$group_by]])){
				$groups[$item[$group_by]] = array();
			}
			$groups[$item[$group_by]][] = $item;

		}
 		
		foreach($groups as $i=>$group){
			$groups[$i] = new ReportingSet( $group );
		}

		$this->groups = $groups;

		return $this;

	}

	public function filter( $filters=array() ){

		// initialize with the full data set
		$items_filtered = $this->getItems();

		foreach( $items_filtered as $i=>$item ){

			foreach($filters as $key=>$value){
				if($item[$key] !== $value){
					unset($items_filtered[$i]);
					break;
				}
			}

		}

		$this->items_filtered = $items_filtered;

		return $this;

	}

	public function getItems(){
		if($this->items_filtered){
			return $this->items_filtered;
		} else {
			return $this->items;
		}

		
	}


	public function getGroups(){
		if($this->groups){
			return $this->groups;
		} else {
			return array($this);
		}

		
	}



	public function removeFilters(){
		$this->items_filtered = null;
		return $this;
	}

	public function aggregate($field, $type){

		if(!isset($this->aggregations[$field])){
			$this->aggregations[$field] = array();
		}

		$vals = $this->extract($field);

		$val = FALSE;

		switch($type){
			case 'count':
				$val = count($vals);
			break;
			case 'sum':
				$val = array_sum($vals);
			break;
		}

		$this->aggregations[$field][$type] = $val;

		return $val;

	}

	public function extract($field){
		
		$vals = array();

		foreach($this->getItems() as $item){
			$vals[] = $item[$field];
		}
		return $vals;
	}

}



$items = array(
	array(
		'name' => 'Mike',
		'sex' => 'm',
		'age' => 31
	),
	array(
		'name' => 'Katie',
		'sex' => 'f',
		'age' => 34
	),
	array(
		'name' => 'John',
		'sex' => 'm',
		'age' => 32
	)

);

$set = new ReportingSet($items);

print_r( $set->group_by('sex')->getGroups() );
