<?php
abstract class AbstractRestAPI
{
	/**
     * The HTTP method of the request, either GET, POST, PUT or DELETE
     */
    protected $method = '';
    /**
     * The Resource requested in the URI. eg: /products/2/categories/1
     */
    protected $resource = '';
	/**
     * Any query parameters appended with the URL for a PUT Request
     */
    protected $query_params = Array();
    /**
     * Stores the input of the POST or the PUT request
     */
    protected $input_file = Null;
	/**
     * Constructor: __construct
     */
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
        }//till here

        switch($this->method) {
        case 'DELETE': break;  
        case 'POST':
			$this->input_file = file_get_contents("php://input");
			break;
        case 'GET':
			$this->query_params = $this->_cleanInputs($_GET);
			break;
        case 'PUT':
            $this->input_file = file_get_contents("php://input");
			break;
		default:
            $this->_response('Invalid Method', 405);
            break;
        }
   }

	public function processAPI() {
			
           // return $this->_response($this->controllerMain());
        
       // return $this->_response('', 400);
    }

    protected function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        echo json_encode($data);
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $clean_input[$key] = $this->_cleanInputs($value);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code) {
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
			if(array_key_exists($code, $status))
			{
				return $status[$code];	
			}
			else 
			{
				return $status[500];
			}
    }
}
?>