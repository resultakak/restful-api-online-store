<?php
abstract class API
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
     * Stores the input of the PUT request
     */
    protected $input_file = Null;
	/**
     * Constructor: __construct
     */
    public function __construct($request) {
        header("Content-Type: application/json");
		
		
        $this->args = explode('/', rtrim($request, '/'));
        $this->resource = array_shift($this->args);
		
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }
		
		//the below code needs review. unsatisfactory.
        $this->method = $_SERVER['REQUEST_METHOD'];
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
        case 'DELETE':
        case 'POST':
            $this->request = $this->_cleanInputs($_POST);
            break;
        case 'GET':
            $this->request = $this->_cleanInputs($_GET);
            break;
        case 'PUT':
            $this->request = $this->_cleanInputs($_GET);
            $this->file = file_get_contents("php://input");
            break;
        default:
            $this->_response('Invalid Method', 405);
            break;
        }
   }

	public function processAPI() {
        if ((int)method_exists($this->resource) > 0) {
            return $this->_response($this->{$this->resource}($this->args));
        }
        return $this->_response('', 400);
    }

    private function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode($data);
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
            202 => 'Accepted',   
            204 => 'No Content',   
            400 => 'Bad Request',   
            401 => 'Unauthorized',   
            403 => 'Forbidden',   
            404 => 'Not Found',   
            405 => 'Method Not Allowed',   
            406 => 'Not Acceptable',   
            407 => 'Proxy Authentication Required',   
            408 => 'Request Timeout',   
            409 => 'Conflict',   
            410 => 'Gone',   
            411 => 'Length Required',   
            412 => 'Precondition Failed',   
            413 => 'Request Entity Too Large',   
            414 => 'Request-URI Too Long',   
            415 => 'Unsupported Media Type',   
            500 => 'Internal Server Error'   
            ); 
        return ($status[$code])?$status[$code]:$status[500];
}
}
?>