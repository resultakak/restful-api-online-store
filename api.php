<?php

require_once 'abstract-api.php';
require_once 'db.php';

class MyAPI extends API
{
    protected $User;
	public $conn;
	private $db;
    public function __construct($request,$db) {
       	
		parent::__construct($request);
		$this->db = $db;
/*
        // Abstracted out for example
        $APIKey = new Models\APIKey();
        $User = new Models\User();

        if (!array_key_exists('apiKey', $this->request)) {
            throw new Exception('No API Key provided');
        } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        } else if (array_key_exists('token', $this->request) &&
             !$User->get('token', $this->request['token']))
			{
            throw new Exception('Invalid User Token');
			}
        

        $this->User = 'hello';*/
    }
	/*
     protected function example() {
        if ($this->method == 'GET') {
            return "Your name is " . $this->User->name;
        } else {
            return "Only accepts GET requests";
        }
     }
	 */
	 
	 public function controllerMain()
	 {
		$count = count($this->resourceHierarchy);
		
		if(is_numeric($this->resourceHierarchy[$count-1]))
		{
			if($this->resourceHierarchy[$count-2]=='products')
			{
				switch($this->method) {
					case 'GET': $this->getSingleProduct($this->resourceHierarchy[$count-1]);
								break;
					case 'POST':echo 'Invalid';
								break;
					case 'PUT': $this->updateProduct($this->resourceHierarchy[$count-1]);
								break;
					case 'DELETE': $this->deleteSingleProduct($this->resourceHierarchy[$count-1]);
								break;
					default: break;
				}
			}
		}
		else {
				switch($this->method) {
					case 'GET': $this->getProducts();
								break;
					case 'POST':$this->insertProduct();
								break;
					case 'PUT': echo 'Invalid';
								break;
					case 'DELETE': echo 'Invalid';
								break;
					default: break;
				}
		}
		
		die();
	 }
	 
	 public function getSingleProduct($product_id)
	 {
	 	$fields='*';
	 	$sort='user_id asc';
		$where="user_id='".$product_id."'";
		
		//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->request))
		{
			$fields=$this->request['fields'];
			unset($this->request['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->request))
		{
			$sort = $this->sortSerialize($this->request['sort']);
			unset($this->request['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->request))
		{
			
			unset($this->request['page']);
		}
		if(array_key_exists('per_page', $this->request))
		{
			unset($this->request['per_page']);
		}
		
		unset($this->request['request']);
		if(count($this->request)>0)
		{
			$where = $where." and ".$this->queryParameterSerialize($this->request);
		}
		
		echo json_encode($this->db->select('users',$fields,$where,null,'',$sort));	
	}

	public function getProducts()
	{
	    $fields='*';
	 	$sort='user_id asc ';
	 	$where='1=1';
	 	//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->request))
		{
			$fields=$this->request['fields'];
			unset($this->request['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->request))
		{
			$sort = $this->sortSerialize($this->request['sort']);
			unset($this->request['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->request))
		{
			
			unset($this->request['page']);
		}
		if(array_key_exists('per_page', $this->request))
		{
			
			unset($this->request['per_page']);
		}
		
		unset($this->request['request']);
		array_values($this->request);
		
		if(count($this->request)>0)
		{
		$where = $this->queryParameterSerialize($this->request);
		}
		echo json_encode($this->db->select('users',$fields,$where,null,'',$sort));	
	}
	
	public function insertProduct()
	{
			$this->db->insert('users',$this->request);
	}
	
	public function updateProduct($product_id)
	{
			$this->db->update('users',$this->request);
	}
	 
	 public function sortSerialize($string)
	 {
	 	$sort='';
		$temp=explode(',',$string);
			
			for($i=0;$i<count($temp);$i++)
			{
				$temp2 = str_split($temp[$i]);
				if($temp2[0]=='-')
				{
					array_shift($temp2);
					$sort = ($i==count($temp)-1) ? $sort.implode($temp2).' desc' : $sort.implode($temp2).' desc,';
				}	
				else {
					$sort = ($i==count($temp)-1) ? $sort.implode($temp2).' asc' : $sort.implode($temp2).' asc,';
				}			
			}
		return $sort;	
	 }
	 
	 public function queryParameterSerialize($array)
	 {
	 	$string='';
		$keys=array_keys($array);
		for($i=0;$i<count($keys);$i++)
		{
			$string = ($i==count($keys)-1) ? $string.$keys[$i]."='".$array[$keys[$i]]."' " : $string.$keys[$i]."='".$array[$keys[$i]]."' and ";
		}
		return $string;
	 }
	 
	 public function deleteSingleProduct($product_id)
	 {
		$where="user_id='".$product_id."'";
		
		//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->request))
		{
			$fields=$this->request['fields'];
			unset($this->request['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->request))
		{
			$sort = $this->sortSerialize($this->request['sort']);
			unset($this->request['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->request))
		{	
			unset($this->request['page']);
		}
		
		if(array_key_exists('per_page', $this->request))
		{
			unset($this->request['per_page']);
		}
		
		unset($this->request['request']);
		if(count($this->request)>0)
		{
			$where = $where." and ".$this->queryParameterSerialize($this->request);
		}
		
		$this->db->delete('users',$where);
		echo json_encode(array('deleted' => "true"));	
		
	}
	 
	 public function select()
	 {
	 	
	 }
 }
 	try {
 		$db=new db();
        $API = new MyAPI($_REQUEST['request'],$db);
		$API->controllerMain();
  //  	echo $API->processAPI();
		} 
		catch (Exception $e) {
    		echo json_encode(Array('error' => $e->getMessage()));
		}
?>