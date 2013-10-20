<?php

//including the Abstract Rest API and DB Wrapper class files
require_once 'Abstract-Rest-API.php';
require_once 'DBWrapper.php';
require_once 'ResourceData.php';

//extending the class AbstractRestAPI
class OnlineStoreAPI extends AbstractRestAPI
{
	//Database object
    private $db;
    private $resourceData;
	
	//Constructor
    public function __construct($request,$db,$resourceData) 
    {
    	//Calling the parent constructor
    	parent::__construct($request);
		
		//Assigning the database parameter object and resource data object to its own local objects
		$this->db = $db;
		$this->resourceData = $resourceData;
		
		//calling the authenticate function to authenticate the user
	    $this->authenticate();
    }
	 
	 public function authenticate()
	 {
	 	//checking whether authentication credentials have been entered or not
	 	if (!isset($_SERVER['PHP_AUTH_USER'])) 
	 	{
		    $this->authenticateDialog("Please login with admin credentials.");
		}
        
        $conditionParams=Array();
        $conditionParams['username']=$_SERVER['PHP_AUTH_USER'];
        $conditionParams['password']=$_SERVER['PHP_AUTH_PW']; 
		
		//checking whether the user credentials entered match the credentials of a user in the database
		if(!($this->authorizationUser($conditionParams)))
		{
			$this->authenticateDialog("Credentials Incorrect.");
		}      
		
		//checking whether the user logged in has administrative rights
		if(!($this->authorizationAdmin($conditionParams)))
		{
			$this->authenticateDialog("Only Administrators are allowed.");	
		}
    }
     
	 //Function to redirect to the Authentication Dialog box in which the user has to enter his credentials
	 public function authenticateDialog($realm)
	 {
	 	    header('WWW-Authenticate: Basic realm="'.$realm.'")');
		    header('HTTP/1.0 401 Unauthorized');
			echo 'Authentication Failed';
			exit;
	 }
	 
	 //function for checking whether the user exists in the database
     public function authorizationUser($conditionParams)
     {
        $resultArray = $this->db->select('users','*',$conditionParams,'',null);
        return (count($resultArray)==0) ? false : true;
     }
	 
	 //function for checking whether the user has administrative rights or not
	 public function authorizationAdmin($conditionParams)
	 {
	 	$conditionParams['role']='Administrator';
		$resultArray = $this->db->select('users','*',$conditionParams,'',null);
		return (count($resultArray)==0) ? false : true;
	 }
	 
     //Main controller function
	 public function controllerMain()
	 {
	 	//Generates a resource hierarchy array from the resource string 
	 	//Example:- /categories/2 to Array([0]->categories,[1]->2)
	 	$resourceHierarchy = explode('/',$this->resource);
		
		//Storing count of elements in array in variable $count
		$count = count($resourceHierarchy);
		
		//Checking whether the request has a numeral at the last, therefore the below if statement will match a resource request
		if(is_numeric($resourceHierarchy[$count-1]))
		{
			//Checking whether the request made is for resource of a collection
			if($resourceHierarchy[$count-2]==$this->resourceData->URLCollectionName)
			{
				switch($this->method) {
					case 'GET': //Calls the getResources function with input parameter as Resource ID given in the URL
					            $this->getResources($resourceHierarchy[$count-1]);
								break;
					case 'POST'://POST Method not applicable on a specified resource 
								$this->_response(array('error' => "Method Not Allowed on a Resource"),'405');
								break;
					case 'PUT': //Calls the updateResource function with input parameter as Resource ID given in the URL
					            $this->updateResource($resourceHierarchy[$count-1]);
								break;
					case 'DELETE': //Calls the deleteResource function with input parameter as Resource ID given in the URL 
					            $this->deleteResource($resourceHierarchy[$count-1]);
								break;
					default:    //Any other method not allowed 
					            $this->_response(array('error' => "Method not allowed"),'405');
								break;
				}
			}
			else {
				$this->_response(array('error' => "Bad Request"),'400');		
			}
            
		}
		//The below else statement will match a request made for a collection
		else {
			//Checking whether a request is made for a collection
			if($resourceHierarchy[$count-1]==$this->resourceData->URLCollectionName)
			{
				switch($this->method) {
					case 'GET': 	//Calls the getResources function
					                $this->getResources();
									break;
					case 'POST':	//Calls the insertResource function
					                $this->insertResource();
									break;
					case 'PUT': 	//PUT Method not applicable on a collection
									$this->_response(array('error' => "Method not allowed on a collection"),'405');
									break;
					case 'DELETE':  //Delete method not applicable on a collection 
								    $this->_response(array('error' => "Method not allowed on a collection"),'405');
								    break;
					default: 		//Any other method not allowed
					                $this->_response(array('error' => "Method not allowed"),'405');
									break;
				}
		}
			else {
				$this->_response(array('error' => "Bad Request"),'400');
			}
		}
		
	 }
	 
	//Function to get and display resource data according to certain criteria
	public function getResources($id=null)
	{
	    //By default, all fields get displayed.   
	    $fields='*';
        
        //By default, resources are show in the ascending order of their primary keys
	 	$sort=$this->resourceData->primarykey_field.' asc';
		
		//By default first page
		$page=1;
        
        //By default, 10 records per page.
		$per_page=10;
		
        //Adding the ID of the resource to an array if a single resource is requested.
		$conditionParamsArray = Array();
		if($id!=null)
		{
			$conditionParamsArray[$this->resourceData->primarykey_field]=$id;
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
			$sort = APIUtils::sortSerialize($this->query_params['sort']);
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
		
        //Setting up the limit clause for SQL Query
		$limit='limit '.(($page-1)*$per_page).','.$per_page;
        
        //Unsetting the 'request' key set in the $query->params array.
		if(array_key_exists('request', $this->query_params))
		{
		unset($this->query_params['request']);
		}
        
        //Rearranging the array
		array_values($this->query_params);
		
        //Checking if any query parameters need to be sent with the query
		if(count($this->query_params)>0)
		{
			$params_keys = array_keys($this->query_params);
			for($i=0;$i<count($params_keys);$i++)
			{
				$conditionParamsArray[$params_keys[$i]]=$this->query_params[$params_keys[$i]];
			}			
		}
		
        //Calling the select function in the Database Wrapper and returning the response returned from it
		$this->_response($this->db->select($this->resourceData->TableName,$fields,$conditionParamsArray,$limit,$sort),'200');	
	}

	//Function to insert the resource
	public function insertResource()
	{
	    //Decoding the JSON input into a PHP Array
		$array=json_decode($this->input_file,true);
        
        //Fetch the single dimensional array in case it was fetched as a multidimensional array from JSON_DECODE(Because of [] enclosing the JSON array)
        $array = APIUtils::fetch_Single_Array($array);
        
        //Calling the Insert Function of the DB Wrapper
		$last_inserted_id = $this->db->insert($this->resourceData->TableName,$array);
		
        if($last_inserted_id)
        {
        //Making the condition parameters array. Sending it as a parameter to the select function so as to fetch the data to display
		$conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceData->primarykey_field]=$last_inserted_id;
		
        //Calling the select function with the parameters which will also MemCache the newly inserted record.
		$this->_response($this->db->select($this->resourceData->TableName,'*',$conditionParamsArray,'limit 0,10',$this->resourceData->primarykey_field.' asc'),'201');
        }
        else {
            $this->_response(array('error' => "resource could not get inserted"),'400');
        }
	}
	
	
	//Function to update a resource
	public function updateResource($id)
	{
	    //Decoding the JSON input into a PHP Array
	    $array = json_decode($this->input_file,true);
        
        //Fetch the single dimensional array in case it was fetched as a multidimensional array from JSON_DECODE(Because of [] enclosing the JSON array)
        $array = APIUtils::fetch_Single_Array($array);
        
        //Checking if Resource ID in the URL matches the Resource ID in the input file
        if($array[$this->resourceData->primarykey_field]!=$id)
        {
                $this->_response(array('error' => "Bad Request"),'400');
                exit;
        }
        
        //Making the condition parameters array. Sending it as a parameter to the select function so as to check whether the given record exists or not
        $conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceData->primarykey_field]=$id;
		
		//Calling the select function to check whether the given record exists or not.
		$arrays = $this->db->select($this->resourceData->TableName,'*',$conditionParamsArray,'',null);
		if(count($arrays)>0)
		{
		    //If record exists, it will get updated.
			$count = $this->db->update($this->resourceData->TableName,$array,$conditionParamsArray);
			
            //If update successful
			if($count>0)
			{
			    //Select function is called to display the data just updated, will also Memcache the record just updated
			    $this->_response($this->db->select($this->resourceData->TableName,'*',$conditionParamsArray,'limit 0,10',$this->resourceData->primarykey_field.' asc'),'200');
			}
			else 
			{
				$this->_response(array('error' => "resource could not get updated"),'400');
			}
		}
		else {
			$this->_response(array('resource' => "not found"),'404');
		}
	}
	 
	 //Function to delete a resource. 
	 public function deleteResource($id)
	 {
	    //Making the condition parameters array. Sending it as a parameter to the select function so as to check whether the given record exists or not
        $conditionParamsArray = Array();
		$conditionParamsArray[$this->resourceData->primarykey_field]=$id;
		
        //Calling the select function to check whether the given record exists or not.
        $arrays = $this->db->select($this->resourceData->TableName,'*',$conditionParamsArray,'',null);
		if(count($arrays)>0)
		{
		    //If record exists, it will get updated.
			$count = $this->db->delete($this->resourceData->TableName,$conditionParamsArray);
			
            //If delete successful
			if($count==0)
			{
			$this->_response(array('error' => "could not get deleted"),'400');
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
}

 	  try { 
     	    //Making a new database wrapper Object
     		$db=new DBWrapper();
            
            //Making a new Resource Data Object for Categories
    		$r1 = new ResourceData('categories',$db,'categories');
            
            //Creating API Object and passing the parameter received from HTACCESS, the database object and the resource object
    	    $API = new OnlineStoreAPI($_REQUEST['request'],$db,$r1);
            
            //Calling the Controller Function
    		$API->processRequests();
		} 
    
		catch (Exception $e) {
    		echo json_encode(Array('error' => $e->getMessage()));
		}
?>