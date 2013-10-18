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
	 	$resourceHierarchy = explode('/',$this->request['request']);
		
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
			$params_key = array_keys($this->request);
			for($i=0;$i<count($params_key);$i++)
			{
				$conditionParamsArray[$params_key[$i]]=$this->request[$params_key[$i]];
			}			
		}
		
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
			$page=$this->request['page'];
			unset($this->request['page']);
		}
		if(array_key_exists('per_page', $this->request))
		{
			$per_page=$this->request['per_page'];
			unset($this->request['per_page']);
		}
		$limit='limit '.(($page-1)*$per_page).','.$per_page;
		unset($this->request['request']);
		array_values($this->request);
		
		if(count($this->request)>0)
		{
		
			$params_key = array_keys($this->request);
			for($i=0;$i<count($params_key);$i++)
			{
				$conditionParamsArray[$params_key[$i]]=$this->request[$params_key[$i]];
			}			
		
		}
		echo json_encode($this->db->select('users',$fields,$conditionParamsArray,$limit,$sort));	
	}
	
	public function insertProduct()
	{
			$this->db->insert('users',$this->request);
	}
	
	public function updateProduct($product_id)
	{
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
		
		$this->db->update('users',$this->request,$conditionParamsArray);
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