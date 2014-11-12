<?php
/**
 * Object type for reporting on large sets of data,
 * with support for filtering, searching, grouping,
 * and aggregations.
 */
class ReportingSet {
	protected $items = array();
	protected $items_filtered;
	protected $groups;
	protected  $label;
	protected $aggregations = array();
	
	protected $extractionSeparator = '.'; 
	function __construct( $items = array(), $label = null ){
		$this->items = $items;
		if($label){
			$this->label = $label;
		}
	}
	public function groupBy( $group_by ){
		
		$groups = $this->groups ? $this->groups : array($this);
		$group_identifiers = array();
		foreach($groups as $group){
			$items = $group->getItems();
			
			$groups_temp = array();
			foreach( $items as $i=>$item ){
				$val  = $this->dotNotationExtract( $item, $group_by );
				if(is_null($val)){
					continue;
				}
				$key = $this->generateGroupKey($val);
				$group_identifiers[$key] = $val;
				
				if(!isset($groups_temp[$key])){
					$groups_temp[$key] = array();
				}
				$groups_temp[$key][] = $item;
			}
			foreach($groups_temp as $i=>$group_temp){
				$groups_temp[$i] = new ReportingSet( $group_temp, $group_identifiers[$i] );
			}
			$group->groups = $groups_temp;
			
			$group->sortGroups();



		}

		return $this;
	}
	public function ungroup( $all = FALSE ){
		$nested = FALSE;
		foreach($this->getGroups() as $group){
			if($group->hasGroups()){
				$nested = TRUE;
				$group->ungroup();
			}
		}
		if(!$nested || $all){
			$this->groups = null;
		}
		
	}
	public function ungroupAll(){
		$this->ungroup(TRUE);
	}
	public function hasGroups(){
		if(empty($this->groups))
			return false;
		else return true;
	}
	public function filter( $filters=array() ){
		// initialize with the full data set
		$items_filtered = $this->getItems();
		foreach($filters as $key=>$values){

			if(empty($values))
				continue;

			if(!is_array($values) && !empty($values)){
				$values = array($values);
			}
			
			foreach( $items_filtered as $i=>$item ){
				if(!in_array($this->dotNotationExtract($item, $key),$values)){
					unset($items_filtered[$i]);
				}
			}
		}
		$this->items_filtered = $items_filtered;
		$this->update();
		return $this;
	}
	protected function update(){
		
		// clear any cached aggregation values
		$this->aggregations = array();
		
		// also update any groups
		foreach($this->getGroups() as $group){
			$group->update();
		}
	}
	public function clearFilters(){
		$this->items_filtered = null;
		$this->update();
	}
	public function search( $key, $value ){
		$items_filtered = $this->getItems();
		foreach( $items_filtered as $i=>$item ){
			
				if( stripos((string) $this->dotNotationExtract($item, $key), $value) === FALSE){
					unset($items_filtered[$i]);
					
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
	public function getAllItems(){
		return $this->items;
	}
	public function getGroups(){
		if($this->groups){
			return $this->groups;
		}
		else return array();
		
	}
	public function getGroup($val, $sort_by=FALSE){
		
		$val = $this->generateGroupKey($val);
		
		if(isset($this->groups[$val])){
			return $this->groups[$val]; 
		}
		
		return FALSE;
	}
	
	public function sortGroups($by = FALSE, $aggregation='sum', $direction='desc'){
		$groups = $this->groups;
		if(!$by){
			
			uasort($groups, array($this, 'sortGroupsCallback'));
		} else {
			$sorter = new ReportSetSorter($by, $aggregation, $direction);
			uasort($groups, array($sorter, 'sort'));
		}
			$this->groups = $groups;

	}

	protected function sortGroupsCallback(ReportingSet $a, ReportingSet $b){
		
		return strcmp($a->getLabel(), $b->getLabel());

	}

	public function getGroupValues(){
		$vals = array();
		foreach($this->getGroups() as $group){
			$vals[] = $group->getLabel();
		}
		//sort($vals);
		return $vals;
	}

	public function setGroupValues($arr){
		
		// kill any nonmatching groups
		foreach($this->getGroups() as $group){
			if(!in_array($group->getLabel(), $arr)){
				$this->removeGroup($group->getLabel());
			}
		}

		// create empty groups for any that don't exist
		foreach($arr as $val){
			$group = $this->getGroup($val);
			if ($group === FALSE){
				$this->groups[ $this->generateGroupKey($val) ] = new ReportingSet(array(), $val);
			}
		}

		$this->sortGroups();

	}

	protected function removeGroup($val){
		$key = $this->generateGroupKey($val);
		if(isset($this->groups[$key])){
			unset($this->groups[$key]);
		}
	}



	public function removeFilters(){
		$this->items_filtered = null;
		return $this;
	}
	public function aggregate($type, $field){
		if(!isset($this->aggregations[$field])){
			$this->aggregations[$field] = array();
		}
		if(isset($this->aggregations[$field][$type])){
			return $this->aggregations[$field][$type];
		}
		$vals = $this->extract($field);
		switch($type){
			
			case 'count':
				$val = count($vals);
			break;
			case 'unique_count':
				$val = count(array_unique($vals));
			break;
			
			case 'sum':
			case 'total':
				$val = array_sum($vals);
			break;
			
			case 'average':
			case 'mean':
				$val = array_sum($vals) / count($vals);
			break;
			
			default:
			case 'mode':
				$val = array_count_values($vals);
				asort($val);
				$val = array_keys($val);
				$val = end($val);
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
			$fields = explode($this->extractionSeparator, $fields);
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
	protected function generateGroupKey($value){
		if(is_scalar($value)){
			return $value;
		} else {
			return md5(serialize($value));
		}
	}
	protected function dotNotationSet($id, $fields, $value){
	
		if(is_string($fields)){
			$fields = explode('.', $fields);
		}
		$item = $this->items[$id];
		$pointer =& $item;
		foreach($fields as $field){	
			if(!isset($pointer[$field])){
				return FALSE;
			}
			else $pointer =& $pointer[$field];
		}
	
		$pointer = $value;
		unset($pointer);
		
		$this->items[$id] = $item;
		return TRUE;
	}
	public function getUnique($field){
		return array_unique( $this->extract($field) );
	}
	public function getLabel(){
		if($this->label)
			return $this->label;
		else return false;
	}
	public function stitch(ReportingSet $set, $field_name, $key_field_incoming, $key_field_receiving){
		
		$incoming_items = array();
		foreach($set->getItems() as $item){



			$key = $this->generateGroupKey($this->dotNotationExtract($item, $key_field_incoming));
			$incoming_items[ $key ] = $item;

		}
		foreach($this->items as $i=>$item){
			if( isset($item[$key_field_receiving]) ){
				$key = $this->generateGroupKey( $item[$key_field_receiving] );
				if( isset( $incoming_items[ $key ] )){
					$this->items[$i][$field_name] = $incoming_items[ $this->generateGroupKey( $item[$key_field_receiving] ) ];
				}
			}
		}
	}
	public function updateItem($id, $key, $value){
		$this->dotNotationSet($id, $key, $value);
	}
	static function flattenItems($arr, $num = 1){
		for($i = 0; $i < $num; $i++){
			$result = array();
			foreach($arr as $j=>$item){
				$item_flattened = array();
				foreach($item as $key=>$value){
					if(is_array($value)){
						$item_flattened = array_merge($item_flattened, $value);
					} else {
						$item_flattened[$key] = $value;
					}
					$result[$j] = $item_flattened;
				}
			}
			$arr = $result;
		}
		return $arr;
	}
	// aggregation helpers
	public function getAverage($field){
		return $this->aggregate('average', $field);
	}
	public function getSum($field){
		return $this->aggregate('sum', $field);
	}
	public function getCount($field){
		return $this->aggregate('count', $field);
	}
	public function getUniqueCount($field){
		return $this->aggregate('unique_count', $field);
	}
}




class ReportSetSorter{

	private $agg_type;
	private $direction;
	private $field;

	function __construct($field, $agg_type = 'sum', $direction='desc'){
		$this->field = $field;
		$this->add_type = $agg_type;
		$this->direction = $direction;
	}

	function sort($a, $b){
		$s = strcmp(
			$a->aggregate($this->agg_type, $this->field), 
			$b->aggregate($this->agg_type, $this->field)
		);
		if($this->direction == 'desc'){
			$s = $s*-1;
		}
		return $s;
	}
}