<?php

require_once 'APIUtil.php';

abstract class AbstractRestAPI
{
	// The HTTP method of the request, either GET, POST, PUT or DELETE
    protected $method = '';
    
    // The Resource requested in the URI. eg: /products/2/categories/1
    protected $resource = '';
	
    // Any query parameters appended with the URL for a PUT Request
    protected $query_params = Array();
    
    //Stores the input file of the POST or the PUT request
    protected $input_file = Null;
	
     //Constructor
    public function __construct($request) {
        header("Content-Type: application/json");
		
		$this->resource = rtrim($request,'/');
		
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		//since PHP is only capable of processing POST and GET and not PUT and DELETE. Try to find another code snippet.
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch($this->method) {
        case 'DELETE': //Breaking in case DELETE is method since resource ID can be fetched from the URL. Query parameters or input file not needed
                       break;  
        case 'POST':   //Storing JSON input into input_file variable
			           $this->input_file = file_get_contents("php://input");
			           break;
        case 'GET':    //Storing $_GET query parameters into query_params variable
			           $this->query_params = APIUtils::sanitizeInputs($_GET);
			           break;
        case 'PUT':    //Storing JSON input into input_file variable
                       $this->input_file = file_get_contents("php://input");
			           break;
		default:       $this->_response('Invalid Method', 405);
                       break;
        }
   }
    
    //Controller function of the Abstract API. Calls the main controller function of the extended class
	public function processAPI() 
	{
	       $this->controllerMain();
    }

    //Function to output response
    protected function _response($data, $status = 200) 
    {
        header("HTTP/1.1 " . $status . " " . $this->getStatusMessage($status));
        echo json_encode($data);
    }
    
    //Function to return the status message according to the Status Code
    private function getStatusMessage($code) 
    {
        $status = array( 
            200 => 'OK', 
            201 => 'Created',   
            204 => 'No Content',   
            400 => 'Bad Request',   
            401 => 'Unauthorized',   
            404 => 'Not Found',   
            405 => 'Method Not Allowed',   
            415 => 'Unsupported Media Type',   
            500 => 'Internal Server Error'   
            ); 
				return $status[$code];	
	}
}
?>