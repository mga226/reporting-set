<?php

class ReportingSet {

	protected $items = array();
	protected  $items_filtered;
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

				$val = $this->generateGroupKey($val);

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

	public function ungroup(){
		if($this->hasGroups()){
			foreach($this->getGroups() as $group){
				$group->ungroup();
			}
		} else {
			$this->groups = null;
		}
	}

	public function hasGroups(){
		if(empty($this->groups))
			return false;
		else return true;
	}

	public function filter( $filters=array() ){


		// initialize with the full data set
		$items_filtered = $this->getItems();

		foreach($filters as $key=>$value){
			foreach( $items_filtered as $i=>$item ){

				if($this->dotNotationExtract($item, $key) !== $value){
					unset($items_filtered[$i]);
					break;
				}
			}
		}

		$this->items_filtered = $items_filtered;

		$this->update();

		return $this;

	}

	protected function update(){
		
		$this->aggregations = array();
		
		
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
					break;
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

	public function getGroup($val){
		
		$val = $this->generateGroupKey($val);
		
		if(isset($this->groups[$val])){
			return $this->groups[$val]; 
		}
		
		return FALSE;
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


	public function stitch(ReportingSet $set, $key_field_incoming, $key_field_receiving){
		$items = array();
		foreach($set->getItems() as $item){
			$items[ $item->dotNotationExtract($key_field_incoming) ] = $item;
		}

	}

	public function updateItem($id, $key, $value){
		$this->dotNotationSet($id, $key, $value);
	}






}


$data = '[
  {
    "_id": "545ea417b10db383d1bd2813",
    "index": 0,
    "guid": "53810d28-bd7c-42df-ad7d-b2fd90a2bb2d",
    "isActive": true,
    "balance": "$1,296.43",
    "picture": "http://placehold.it/32x32",
    "age": 31,
    "eyeColor": "brown",
    "name": "Blankenship Perkins",
    "gender": "male",
    "company": "ROBOID",
    "email": "blankenshipperkins@roboid.com",
    "phone": "+1 (998) 546-3272",
    "address": "208 Dahlgreen Place, Geyserville, Michigan, 8496",
    "about": "Veniam elit minim dolor consectetur ut exercitation reprehenderit adipisicing do voluptate incididunt. Cupidatat minim pariatur eiusmod labore dolor elit pariatur ex pariatur. Voluptate esse tempor esse anim. Eu est eiusmod commodo nostrud sit aute ullamco dolore cillum officia irure duis sunt cillum.\r\n",
    "registered": "2014-09-20T06:48:26 +04:00",
    "latitude": -4.295034,
    "longitude": 55.448452,
    "tags": [
      "anim",
      "consectetur",
      "incididunt",
      "ullamco",
      "laborum",
      "laborum",
      "non"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Irene Kirk"
      },
      {
        "id": 1,
        "name": "Frieda Lane"
      },
      {
        "id": 2,
        "name": "Tameka Watson"
      }
    ],
    "greeting": "Hello, Blankenship Perkins! You have 8 unread messages.",
    "favoriteFruit": "apple"
  },
  {
    "_id": "545ea417dce32c440908fb25",
    "index": 1,
    "guid": "7da1f945-6980-498b-92be-074a1ed5a2ef",
    "isActive": false,
    "balance": "$3,389.51",
    "picture": "http://placehold.it/32x32",
    "age": 27,
    "eyeColor": "brown",
    "name": "Araceli Mckay",
    "gender": "female",
    "company": "RODEOCEAN",
    "email": "aracelimckay@rodeocean.com",
    "phone": "+1 (856) 457-2894",
    "address": "717 Williams Court, Bendon, Wyoming, 6067",
    "about": "Sunt veniam sint tempor minim amet aliquip sit sit quis ullamco. Do sunt magna esse laborum excepteur. Ipsum ipsum anim nisi occaecat duis aute. Aute reprehenderit sit aliquip ad quis occaecat cillum sint ipsum eu Lorem id consectetur.\r\n",
    "registered": "2014-08-04T05:22:34 +04:00",
    "latitude": -24.967369,
    "longitude": 145.732145,
    "tags": [
      "incididunt",
      "laboris",
      "ad",
      "anim",
      "fugiat",
      "enim",
      "amet"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Myrtle Odonnell"
      },
      {
        "id": 1,
        "name": "Janell Cole"
      },
      {
        "id": 2,
        "name": "Roman Burke"
      }
    ],
    "greeting": "Hello, Araceli Mckay! You have 4 unread messages.",
    "favoriteFruit": "banana"
  },
  {
    "_id": "545ea417d57880fcea84eef6",
    "index": 2,
    "guid": "9c9530ab-464b-48b2-a5c2-a306a849d9f8",
    "isActive": true,
    "balance": "$2,136.59",
    "picture": "http://placehold.it/32x32",
    "age": 22,
    "eyeColor": "blue",
    "name": "Jones Garza",
    "gender": "male",
    "company": "OPTICON",
    "email": "jonesgarza@opticon.com",
    "phone": "+1 (972) 579-3278",
    "address": "659 Willoughby Avenue, Hinsdale, Alaska, 1144",
    "about": "Reprehenderit irure nulla commodo occaecat sunt fugiat enim occaecat eu consequat voluptate dolore reprehenderit nisi. Ut est labore elit pariatur aute. Commodo Lorem consequat cillum nulla exercitation cillum consectetur ipsum. Veniam nostrud ipsum fugiat ea deserunt ea magna adipisicing dolor consequat sit quis fugiat. Duis laborum ut ullamco enim et exercitation. Quis nulla occaecat pariatur est esse amet quis anim deserunt magna. Cupidatat voluptate officia voluptate proident in aliquip officia ad duis.\r\n",
    "registered": "2014-09-06T03:24:07 +04:00",
    "latitude": 14.031592,
    "longitude": 163.484604,
    "tags": [
      "nulla",
      "do",
      "eiusmod",
      "non",
      "minim",
      "duis",
      "nulla"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Janelle Pearson"
      },
      {
        "id": 1,
        "name": "Perez David"
      },
      {
        "id": 2,
        "name": "Mcleod Weaver"
      }
    ],
    "greeting": "Hello, Jones Garza! You have 8 unread messages.",
    "favoriteFruit": "banana"
  },
  {
    "_id": "545ea4176fe5cb2365955432",
    "index": 3,
    "guid": "4b61e56d-be80-41ed-b7c9-9c4caefc38e8",
    "isActive": true,
    "balance": "$1,689.81",
    "picture": "http://placehold.it/32x32",
    "age": 37,
    "eyeColor": "blue",
    "name": "Eleanor Compton",
    "gender": "female",
    "company": "ROCKABYE",
    "email": "eleanorcompton@rockabye.com",
    "phone": "+1 (936) 492-3754",
    "address": "498 Horace Court, Muir, American Samoa, 8774",
    "about": "Officia quis deserunt laboris ad et cupidatat Lorem elit labore velit nisi enim duis ad. Non nulla dolore amet cupidatat nulla tempor id. Elit velit cillum dolor id pariatur esse laborum. Aliqua commodo duis veniam laborum sunt deserunt exercitation fugiat ut ad duis. Labore irure ipsum ipsum est. Consequat Lorem eiusmod Lorem magna adipisicing nisi eiusmod anim anim aliqua nostrud id dolor.\r\n",
    "registered": "2014-08-31T12:25:00 +04:00",
    "latitude": -89.78444,
    "longitude": 167.447475,
    "tags": [
      "laborum",
      "eu",
      "commodo",
      "pariatur",
      "laborum",
      "esse",
      "id"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Kristi Robertson"
      },
      {
        "id": 1,
        "name": "Ferguson Rodgers"
      },
      {
        "id": 2,
        "name": "Butler Odom"
      }
    ],
    "greeting": "Hello, Eleanor Compton! You have 10 unread messages.",
    "favoriteFruit": "banana"
  },
  {
    "_id": "545ea417f8a10cfb4969208b",
    "index": 4,
    "guid": "b3f3dccd-7ee2-47f9-87a2-032e9111b7dd",
    "isActive": true,
    "balance": "$1,841.09",
    "picture": "http://placehold.it/32x32",
    "age": 20,
    "eyeColor": "brown",
    "name": "Vargas Ellis",
    "gender": "male",
    "company": "TWIGGERY",
    "email": "vargasellis@twiggery.com",
    "phone": "+1 (986) 508-3345",
    "address": "761 Eldert Street, Noxen, Hawaii, 1119",
    "about": "Magna consectetur ex et voluptate culpa culpa cillum officia magna nisi. Reprehenderit aliquip sunt veniam consectetur veniam fugiat ut quis sit veniam anim. Aliqua nostrud Lorem anim reprehenderit enim enim cillum nulla nulla dolore sunt ea sint. In do nisi eu sint.\r\n",
    "registered": "2014-07-30T14:09:12 +04:00",
    "latitude": 36.837084,
    "longitude": 178.193177,
    "tags": [
      "exercitation",
      "consectetur",
      "ad",
      "ut",
      "id",
      "magna",
      "ut"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Perry Mayo"
      },
      {
        "id": 1,
        "name": "Lakisha Tran"
      },
      {
        "id": 2,
        "name": "Rutledge Hodge"
      }
    ],
    "greeting": "Hello, Vargas Ellis! You have 7 unread messages.",
    "favoriteFruit": "banana"
  },
  {
    "_id": "545ea4176b67f17c29c803e8",
    "index": 5,
    "guid": "71e2d3de-43b8-418e-9921-0c0058a7b211",
    "isActive": false,
    "balance": "$2,809.14",
    "picture": "http://placehold.it/32x32",
    "age": 30,
    "eyeColor": "blue",
    "name": "Marisol Todd",
    "gender": "female",
    "company": "NURPLEX",
    "email": "marisoltodd@nurplex.com",
    "phone": "+1 (829) 406-2564",
    "address": "515 Landis Court, Ferney, Montana, 8186",
    "about": "Consectetur eiusmod labore in minim consectetur nulla. Officia culpa nisi cillum anim officia deserunt aliqua. Ullamco cillum sint ipsum commodo occaecat irure cillum ullamco. Adipisicing id elit in laboris laborum.\r\n",
    "registered": "2014-02-01T05:11:51 +05:00",
    "latitude": 56.123433,
    "longitude": 84.851849,
    "tags": [
      "excepteur",
      "duis",
      "proident",
      "amet",
      "ea",
      "ex",
      "consequat"
    ],
    "friends": [
      {
        "id": 0,
        "name": "House West"
      },
      {
        "id": 1,
        "name": "Mollie Ewing"
      },
      {
        "id": 2,
        "name": "Warren Dodson"
      }
    ],
    "greeting": "Hello, Marisol Todd! You have 5 unread messages.",
    "favoriteFruit": "banana"
  },
  {
    "_id": "545ea4178f746c81344de2b8",
    "index": 6,
    "guid": "4a8cf802-184b-4285-a079-6c3fd3445e00",
    "isActive": false,
    "balance": "$3,365.91",
    "picture": "http://placehold.it/32x32",
    "age": 37,
    "eyeColor": "brown",
    "name": "Jacobson Price",
    "gender": "male",
    "company": "COMFIRM",
    "email": "jacobsonprice@comfirm.com",
    "phone": "+1 (827) 418-2310",
    "address": "256 Auburn Place, Osmond, Arizona, 2114",
    "about": "Amet nulla culpa cupidatat sit aliquip sint. Deserunt nisi amet mollit esse ullamco cupidatat nisi irure magna pariatur amet. Laboris tempor dolore nostrud irure dolor cupidatat adipisicing ad nisi adipisicing duis occaecat. Fugiat nostrud esse id qui ad sunt quis exercitation esse sint proident. Pariatur amet eu non enim duis ex fugiat velit qui amet veniam cupidatat. Nostrud et laborum proident sunt enim laborum duis reprehenderit laborum laboris velit sunt mollit voluptate.\r\n",
    "registered": "2014-04-21T00:54:10 +04:00",
    "latitude": -46.17048,
    "longitude": -75.385155,
    "tags": [
      "voluptate",
      "culpa",
      "nostrud",
      "Lorem",
      "irure",
      "aute",
      "incididunt"
    ],
    "friends": [
      {
        "id": 0,
        "name": "Mcconnell Rutledge"
      },
      {
        "id": 1,
        "name": "Ofelia Guthrie"
      },
      {
        "id": 2,
        "name": "Horton Kirby"
      }
    ],
    "greeting": "Hello, Jacobson Price! You have 8 unread messages.",
    "favoriteFruit": "apple"
  }
]';
$data = json_decode($data, TRUE);

$set = new ReportingSet($data);

echo $set->aggregate('average','age')."\n";

$set->group_by('favoriteFruit');
foreach($set->getGroups() as $k=>$group){
	echo "$k average age: ".$group->aggregate('average','age') ."\n";
}

$set->ungroup();
echo $set->aggregate('average','age')."\n";


//$set->group_by('favoriteFruit');


//print_r($set->getGroups());

/*


$items = array(
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

$orders = array(

	array(
		'order_id' => 20,
		'product_id' => 1000,
		'description' => 'hello world'
	)

);

$set = new ReportingSet($items);

$set2 = clone $set;

$set->search('sex','m');
$set->filter(array('address.city'=>'brooklyn'));

echo "Average age: ".$set2->aggregate('average','age')."\n";
echo "Average age (male): ".$set->aggregate('average','age')."\n";

$set->clearFilters();

echo "Average age (all): ".$set->aggregate('average','age')."\n";


/*
$set->updateItem(0, 'address.city', 'Melbourne');
$set->group_by('address.city');
$set->group_by('sex');

print_r($set->getGroups());


print_r($set->getItems());die();



print_r($set->extract('address.city'));

$set->filter(array('address.city'=>'nyc'));
//print_r($set->)


print_r( $set->group_by('address')->getGroups() );
//print_r($set->getUnique('age'));

//print_r($set->getUnique('sex'));

//echo $set->aggregate('sex', 'mode');
*/