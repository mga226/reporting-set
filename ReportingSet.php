<?php

class ReportingSet {

	var $items;
	var $items_filtered;
	var $groups;

	var $label;

	var $aggregations = array();

	function __construct( $items = array(), $label = null ){
		$this->items = $items;
		if($label){
			$this->label = $label;
		}
	}


	public function group_by( $group_by ){

		$groups = $this->groups ? $this->groups : array($this);

		foreach($groups as $group){

			$items = $group->getItems();
			
			$groups_temp = array();

			foreach( $items as $i=>$item ){
				$val  = $this->dotNotationExtract( $item, $group_by );
				if(is_null($val)){
					continue;
				}
				if(!isset($groups_temp[$val])){
					$groups_temp[$val] = array();
				}

				$groups_temp[$val][] = $item;
				

			}

			foreach($groups_temp as $i=>$group_temp){
				$groups_temp[$i] = new ReportingSet( $group_temp );
			}
			$group->groups = $groups_temp;
		}

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
			case 'unique_count':
				$val = count(array_unique($vale));
			break;
			case 'sum':
			case 'total':
				$val = array_sum($vals);
			break;
			case 'average':
			case 'mean':
				$val = array_sum($vals) / count($vals);
			break;
			case 'mode':
				$val = array_count_values($vals);
				asort($val);
				$val = array_keys($val);
				$val = end($val);
//				$val = end( asort( array_count_values($vals) ) );
			break;
		}

		$this->aggregations[$field][$type] = $val;

		return $val;

	}

	public function extract($field, $item = false){
		
		$vals = array();

		foreach($this->getItems() as $item){
			if(! is_null($val = $this->dotNotationExtract($item, $field))){
				$vals[] = $val;
			}
		}
		return $vals;
	}

	protected function dotNotationExtract($item, $fields){
		
		if(is_string($fields)){
			$fields = explode('.', $fields);
		}

		$val = $item;
		foreach($fields as $segment){
			if(isset($val[$segment])){
				$val = $val[$segment];
			} else {
				$val = null;
				break;
			}
		}
		return $val;

	}

	public function getUnique($field){
		return array_unique( $this->extract($field) );
	}


	public function getLabel(){
		if($this->label)
			return $this->label;

		else return false;
	}

}



$items = array(
	array(
		'name' => 'Mike',
		'sex' => 'm',
		'age' => 31,
		'address' => array(
			'city' =>'brooklyn'
		)
	),
	array(
		'name' => 'Katie',
		'sex' => 'f',
		'age' => 34,
		'address' => array(
			'city' =>'brooklyn'
		)
	),
	array(
		'name' => 'John',
		'sex' => 'm',
		'age' => 32,
		'address' => array(
			'city' =>'nyc'
		)
	),
);

$set = new ReportingSet($items);



print_r($set->extract('address.city'));


print_r( $set->group_by('address')->getGroups() );
//print_r($set->getUnique('age'));

//print_r($set->getUnique('sex'));

//echo $set->aggregate('sex', 'mode');