<?php

//including the Abstract Rest API and DB Wrapper class files
require_once 'Abstract-Rest-API.php';
require_once 'DBWrapper.php';
require_once 'resource.php';

//extending class AbstractRestAPI
class OnlineStoreAPI extends AbstractRestAPI
{
	//Database object
    private $db;
    private $resourceDatabase;
	
	//Constructor
    public function __construct($request,$db,$resourceDatabase) 
    {
    	//Calling the parent constructor
    	parent::__construct($request);
		
		//Assigning the database parameter object to its own local database object
		$this->db = $db;
		$this->resourceDatabase = $resourceDatabase;
		
		//calling the authenticate function to authenticate the user
	    $this->authenticate();
    }
	 
	 public function authenticate()
	 {
	 	//checking whether authentication credentials have been entered or not
	 	if (!isset($_SERVER['PHP_AUTH_USER'])) 
	 	{
		    $this->authenticateDialog("Please login with admin credentials");
		} 
		
		//checking whether the user exists in the database
		if(!($this->authorizationUser($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])))
		{
			$this->authenticateDialog("Credentials Incorrect");
		}      
		
		//checking whether the user logged in has administrative rights
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
	 
	 //function for checking whether the user has administrative rights or not
	 public function authorizationAdmin($username,$password)
	 {
	 	$conditionParams=Array();
		$conditionParams['username']=$username;
		$conditionParams['password']=$password;
		$conditionParams['role']='Administrator';
		
	 	$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		
		return (count($resultArray)==0) ? false : true;
	 }
	 
	 //function for checking whether the user exists in the database
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
	 	//Generates a resource hierarchy array from the resource string 
	 	//Example:- /categories/2 to Array([0]->categories,[1]->2)
	 	$resourceHierarchy = explode('/',$this->resource);
		
		//Storing count of elements in array in variable $count
		$count = count($resourceHierarchy);
		
		//Checking whether the request is made for a resource or a collection. The Below If statement will match a Resource
		if(is_numeric($resourceHierarchy[$count-1]))
		{
			//Checking whether the request made is for which resource of which collection
			if($resourceHierarchy[$count-2]==$this->resourceDatabase->TableName)
			{
				switch($this->method) {
					case 'GET': $this->getResources($resourceHierarchy[$count-1]);
								break;
					case 'POST'://POST Method not applicable on a specified resource 
								$this->_response(array('error' => "Method Not Allowed on a Resource"),'405');
								break;
					case 'PUT': $this->updateResource($resourceHierarchy[$count-1]);
								break;
					case 'DELETE': $this->deleteResource($resourceHierarchy[$count-1]);
								break;
					default: $this->_response(array('error' => "Method not allowed"),'405');
								break;
				}
			}
			else {
				$this->_response(array('error' => "Bad Request"),'400');		
			}
		}
		//The below else statement will match a request made for a collection
		else {
			//Checking request is made for which collection
			if($resourceHierarchy[$count-1]==$this->resourceDatabase->TableName)
			{
				switch($this->method) {
					case 'GET': 	$this->getResources();
									break;
					case 'POST':	$this->insertResource();
									break;
					case 'PUT': 	//PUT Method not applicable on a collection
									$this->_response(array('error' => "Method not allowed on a collection"),'405');
									break;
					case 'DELETE':  //Delete method not applicable on a collection 
								    $this->_response(array('error' => "Method not allowed on a collection"),'405');
								    break;
					default: 		$this->_response(array('error' => "Method not allowed"),'405');
									break;
				}
		}
			else {
				$this->_response(array('error' => "Bad Request"),'400');
			}
		}
		die();
	 }
	 
	//Function to get and display resource data according to certain criteria
	public function getResources($id=null)
	{
	    $fields='*';
	 	$sort=$this->resourceDatabase->primarykey_field.' asc ';
		$page=1;
		$per_page=10;
		
		$conditionParamsArray = Array();
		if($id!=null)
		{
			$conditionParamsArray[$this->resourceDatabase->primarykey_field]=$id;
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
		
		//Checking if the request needs pagination
		if(array_key_exists('per_page', $this->query_params))
		{
			$per_page=$this->query_params['per_page'];
			unset($this->query_params['per_page']);
		}
		
		$limit='limit '.(($page-1)*$per_page).','.$per_page;
		if(array_key_exists('request', $this->query_params))
		{
		unset($this->query_params['request']);
		}
		array_values($this->query_params);
		
		if(count($this->query_params)>0)
		{
			$params_key = array_keys($this->query_params);
			for($i=0;$i<count($params_key);$i++)
			{
				$conditionParamsArray[$params_key[$i]]=$this->query_params[$params_key[$i]];
			}			
		}
		
		$this->_response($this->db->select($this->resourceDatabase->TableName,$fields,$conditionParamsArray,$limit,$sort),'200');	
	}
	
	//Function to insert the resource
	public function insertResource()
	{
		$array=json_decode($this->input_file,true);
		$last_inserted_id = $this->db->insert($this->resourceDatabase->TableName,$array);
		
		$conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceDatabase->primarykey_field]=$last_inserted_id;
		
		$this->_response($this->db->select($this->resourceDatabase->TableName,'*',$conditionParamsArray,'',null),'201');
	}
	
	
	//Function to update a resource
	public function updateResource($id)
	{
	    $array = json_decode($this->input_file,true);
        
        if($array[$this->resourceDatabase->primarykey_field]!=$id)
        {
                $this->_response(array('error' => "Bad Request"),'400');
                exit;
        }
        
		$conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceDatabase->primarykey_field]=$id;
		
		$arrays = $this->db->select($this->resourceDatabase->TableName,'*',$conditionParamsArray,'',null);
		if(count($arrays)>0)
		{
			
			$count = $this->db->update($this->resourceDatabase->TableName,$array,$conditionParamsArray);
			
			if($count>0)
			{
			    
			    $this->_response($this->db->select($this->resourceDatabase->TableName,'*',$conditionParamsArray,'',null),'200');
			}
			else 
			{
				$this->_response(array('error' => "resource could not get updated"),'200');
			}
		}
		else {
			$this->_response(array('resource' => "not found"),'404');
		}
	}
	 
	 //Function to delete a resource. 
	 public function deleteResource($id)
	 {
		$conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceDatabase->primarykey_field]=$id;
			
		$arrays = $this->db->select($this->resourceDatabase->TableName,'*',$conditionParamsArray,'',null);
		if(count($arrays)>0)
		{
			$count = $this->db->delete($this->resourceDatabase->TableName,$conditionParamsArray);
			
			if($count==0)
			{
			$this->_response(array('error' => "could not get deleted"),'200');
			}
			else 
			{
				$this->_response(array('deleted' => "true"),'200');
			}
		}
		else {
			$this->_response(array('resource' => "not found"),'404');
		}
	}
	 
	 //Converts query parameter sort='id,-name' to the format 'id asc, name desc' so that the string can be parsed in an 'order by' clause in SQL.
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
		$r1 = new Resource('categories',$db);
	    $API = new OnlineStoreAPI($_REQUEST['request'],$db,$r1);
		$API->controllerMain();
  //  	echo $API->processAPI();
		} 
		catch (Exception $e) {
    		echo json_encode(Array('error' => $e->getMessage()));
		}
?>