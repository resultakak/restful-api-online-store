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
        $this->authenticate();
    }
	 
	 public function authenticate()
	 {
	 	if (!isset($_SERVER['PHP_AUTH_USER'])) 
	 	{
		    header('WWW-Authenticate: Basic realm="Please login with Admin Credentials"');
		    header('HTTP/1.0 401 Unauthorized');
		    echo 'Authentication Failed';
		    exit;
		} 
		if(!($this->authorizationUser($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])))
		{
			header('WWW-Authenticate: Basic realm="Credentials Incorrect."');
		    header('HTTP/1.0 401 Unauthorized');
			echo 'Authentication Failed';
			exit;
		}      
		if(!($this->authorizationAdmin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])))
		{
			header('WWW-Authenticate: Basic realm="Only Administrators allowed"');
		    header('HTTP/1.0 401 Unauthorized');
			echo 'Authentication Failed';
			exit;
		}
    }
	 
	 public function authorizationAdmin($username,$password)
	 {
	 	$conditionParams=Array();
		$conditionParams['username']=$username;
		$conditionParams['password']=$password;
		$conditionParams['role']='Administrator';
	 	$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		if(count($resultArray)==0)
		{
			return false;
		}
		else if(count($resultArray)==1)
		{
			return true;
		}
	 }
	 
	 public function authorizationUser($username,$password)
	 {
	 	$conditionParams=Array();
		$conditionParams['username']=$username;
		$conditionParams['password']=$password;
		
	 	$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		if(count($resultArray)==0)
		{
			return false;
		}
		else if(count($resultArray)==1)
		{
			return true;
		}
	 }
	 
	 public function controllerMain()
	 {
	 	$resourceHierarchy = explode('/',$this->resource);
		
		$count = count($resourceHierarchy);
		
		if(is_numeric($resourceHierarchy[$count-1]))
		{
			if($resourceHierarchy[$count-2]=='products')
			{
				switch($this->method) {
					case 'GET': $this->getSingleProduct($resourceHierarchy[$count-1]);
								break;
					case 'POST':echo 'Invalid';
								break;
					case 'PUT': $this->updateProduct($resourceHierarchy[$count-1]);
								break;
					case 'DELETE': $this->deleteSingleProduct($resourceHierarchy[$count-1]);
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
		
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
		
		//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->query_params))
		{
			$fields=$this->query_params['fields'];
			unset($this->query_params['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->query_params))
		{
			$sort = $this->sortSerialize($this->query_params['sort']);
			unset($this->query_params['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->query_params))
		{
			
			unset($this->query_params['page']);
		}
		if(array_key_exists('per_page', $this->query_params))
		{
			unset($this->query_params['per_page']);
		}
		
		unset($this->query_params['request']);
		array_values($this->query_params);
		
		if(count($this->query_params)>0)
		{
			$params_key = array_keys($this->query_params);
			for($i=0;$i<count($params_key);$i++)
			{
				$conditionParamsArray[$params_key[$i]]=$this->query_params[$params_key[$i]];
			}			
		}
		
		print_r($conditionParamsArray);
		echo $fields;
		echo $sort;
		
		
		echo json_encode($this->db->select('users',$fields,$conditionParamsArray,'',$sort));	
	}

	public function getProducts()
	{
	    $fields='*';
	 	$sort='user_id asc ';
		$page=1;
		$per_page=10;
		
		$conditionParamsArray = Array();
	 	//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->query_params))
		{
			$fields=$this->query_params['fields'];
			unset($this->query_params['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->query_params))
		{
			$sort = $this->sortSerialize($this->query_params['sort']);
			unset($this->query_params['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->query_params))
		{
			$page=$this->query_params['page'];
			unset($this->query_params['page']);
		}
		if(array_key_exists('per_page', $this->query_params))
		{
			$per_page=$this->query_params['per_page'];
			unset($this->query_params['per_page']);
		}
		$limit='limit '.(($page-1)*$per_page).','.$per_page;
		unset($this->query_params['request']);
		array_values($this->query_params);
		
		if(count($this->query_params)>0)
		{
		
			$params_key = array_keys($this->query_params);
			for($i=0;$i<count($params_key);$i++)
			{
				$conditionParamsArray[$params_key[$i]]=$this->query_params[$params_key[$i]];
			}			
		
		}
		echo json_encode($this->db->select('users',$fields,$conditionParamsArray,$limit,$sort));	
	}
	
	public function insertProduct()
	{
			$array=json_decode($this->input_file,true);
			$this->db->insert('users',$array);
	}
	
	public function updateProduct($product_id)
	{
		$array = json_decode($this->input_file,true);
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
		
		$this->db->update('users',$array,$conditionParamsArray);
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
	  
	 public function deleteSingleProduct($product_id)
	 {
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
		
		//Checking if the request needs response to be filtered
	 	if(array_key_exists('fields', $this->query_params))
		{
			$fields=$this->query_params['fields'];
			unset($this->query_params['fields']);
		}
		
		//Checking if the request needs response to be sorted
		if(array_key_exists('sort', $this->query_params))
		{
			$sort = $this->sortSerialize($this->query_params['sort']);
			unset($this->query_params['sort']);
		}
		
		//Checking if the request needs pagination
		if(array_key_exists('page', $this->query_params))
		{	
			unset($this->query_params['page']);
		}
		
		if(array_key_exists('per_page', $this->query_params))
		{
			unset($this->query_params['per_page']);
		}
		
		unset($this->query_params['request']);
		if(count($this->query_params)>0)
		{
			$where = $where." and ".$this->queryParameterSerialize($this->query_params);
		}
		
		$this->db->delete('users',$conditionParamsArray);
		echo json_encode(array('deleted' => "true"));	
		
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