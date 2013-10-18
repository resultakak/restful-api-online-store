<?php

require_once 'Abstract-Rest-API.php';
require_once 'DBWrapper.php';

class OnlineStoreAPI extends AbstractRestAPI
{
    protected $User;
	private $db;
    
    public function __construct($request,$db) 
    {
    	parent::__construct($request);
		$this->db = $db;
	
	    $this->authenticate();
    }
	 
	 public function authenticate()
	 {
	 	if (!isset($_SERVER['PHP_AUTH_USER'])) 
	 	{
		    $this->authenticateDialog("Please login with admin credentials");
		} 
		if(!($this->authorizationUser($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])))
		{
			$this->authenticateDialog("Credentials Incorrect");
		}      
		if(!($this->authorizationAdmin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])))
		{
			$this->authenticateDialog("Only Administrators can do it");	
		}
    }
	 
	 public function authenticateDialog($realm)
	 {
	 	    header('WWW-Authenticate: Basic realm="'.$realm.'")');
		    header('HTTP/1.0 401 Unauthorized');
			echo 'Authentication Failed';
			exit;
	 }
	 
	 public function authorizationAdmin($username,$password)
	 {
	 	$conditionParams=Array();
		$conditionParams['username']=$username;
		$conditionParams['password']=$password;
		$conditionParams['role']='Administrator';
	 	$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		
		return (count($resultArray)==0) ? false : true;
	 }
	 
	 public function authorizationUser($username,$password)
	 {
	 	$conditionParams=Array();
		$conditionParams['username']=$username;
		$conditionParams['password']=$password;
		
	 	$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		
		return (count($resultArray)==0) ? false : true;
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
					case 'GET': $this->getProducts($resourceHierarchy[$count-1]);
								break;
					case 'POST': $this->_response(array('error' => "Bad Request"),'400');
								break;
					case 'PUT': $this->updateProduct($resourceHierarchy[$count-1]);
								break;
					case 'DELETE': $this->deleteProduct($resourceHierarchy[$count-1]);
								break;
					default: $this->_response(array('error' => "Method not allowed"),'405');
								break;
				}
			}
			else {
				$this->_response(array('error' => "Bad Request"),'400');		
			}
		}
		else {
			if($resourceHierarchy[$count-1]=='products')
			{
				switch($this->method) {
					case 'GET': $this->getProducts();
								break;
					case 'POST':$this->insertProduct();
								break;
					case 'PUT': $this->_response(array('error' => "Bad Request"),'400');
								break;
					case 'DELETE': $this->_response(array('error' => "Bad Request"),'400');
								break;
					default: $this->_response(array('error' => "Method not allowed"),'405');
							break;
				}
		}
			else {
				$this->_response(array('error' => "Bad Request"),'400');
			}
		}
		die();
	 }
	 
	 
	public function getProducts($product_id=null)
	{
	    $fields='*';
	 	$sort='user_id asc ';
		$page=1;
		$per_page=10;
		
		$conditionParamsArray = Array();
		if($product_id!=null)
		{
			$conditionParamsArray['user_id']=$product_id;
		}
		
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
		
		$this->_response($this->db->select('users',$fields,$conditionParamsArray,$limit,$sort),'200');	
	}
	
	public function insertProduct()
	{
		$array=json_decode($this->input_file,true);
		$last_inserted_id = $this->db->insert('users',$array);
		
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$last_inserted_id;
		
		$this->_response($this->db->select('users','*',$conditionParamsArray,'',null),'201');
	}
	
	public function updateProduct($product_id)
	{
		$array = json_decode($this->input_file,true);
		
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
		
		$this->db->update('users',$array,$conditionParamsArray);
		$this->_response($this->db->select('users','*',$conditionParamsArray,'',null),'200');
	}
	 
	 public function deleteProduct($product_id)
	 {
		$conditionParamsArray = Array();
		$conditionParamsArray['user_id']=$product_id;
			
		$this->db->delete('users',$conditionParamsArray);
		$this->_response(array('deleted' => "true"),'200');
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
			else 
			{
				$sort = ($i==count($temp)-1) ? $sort.implode($temp2).' asc' : $sort.implode($temp2).' asc,';
			}			
		}
		return $sort;	
	 }
	 
 }

 	try {
 		$db=new db();
	    $API = new OnlineStoreAPI($_REQUEST['request'],$db);
		$API->controllerMain();
  //  	echo $API->processAPI();
		} 
		catch (Exception $e) {
    		echo json_encode(Array('error' => $e->getMessage()));
		}
?>