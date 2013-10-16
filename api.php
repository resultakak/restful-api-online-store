<?php

require_once 'abstract-api.php';

class MyAPI extends API
{
    protected $User;
	public $conn;
    public function __construct($request) {
       	
		parent::__construct($request);
		try 
	 	{
     	$this->conn = new PDO('mysql:host=localhost;dbname=online-store', 'root', '');
     	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	 	}
	 	catch(PDOException $e) 
	 	{
     	echo 'ERROR: ' . $e->getMessage();
		}
		
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

     protected function example() {
        if ($this->method == 'GET') {
            return "Your name is " . $this->User->name;
        } else {
            return "Only accepts GET requests";
        }
     }
	 
	 public function controllerMain()
	 {
		//echo 'Query Parameters';
		//print_r($this->request);
		
		//echo 'Resource Hierarchy';
		//print_r($this->resourceHierarchy);
		//echo $this->method;
				
		$count = count($this->resourceHierarchy);
		
		if(is_numeric($this->resourceHierarchy[$count-1]))
		{
			if($this->resourceHierarchy[$count-2]=='products')
			{
				switch($this->method) {
					case 'GET': $this->getSingleProduct($this->resourceHierarchy[$count-1]);
								break;
					case 'POST': echo 'Invalid';
								break;
					case 'PUT': echo 'Update a SIngle Product';
								break;
					case 'DELETE': echo 'Delete a SIngle Product';
								break;
					default: break;
				}
			}
		}
		else {
				switch($this->method) {
					case 'GET': echo 'Get Multiple Product';
								break;
					case 'POST': echo 'Create a Product';
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
	 	$statement=$this->conn->prepare("SELECT * FROM users");
		$statement->execute();
		$results=$statement->fetchAll(PDO::FETCH_ASSOC);
		$json=json_encode($results);
		echo $json;
	}
 }
 	try {
        $API = new MyAPI($_REQUEST['request']);
		$API->controllerMain();
  //  	echo $API->processAPI();
	} catch (Exception $e) {
    		echo json_encode(Array('error' => $e->getMessage()));
			}
		
?>