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
		
	echo $this->resource = rtrim($request,'/');
		
		/*$temp = strpos($request,'?') ? explode('&',end(explode('?',$request))) : null;
		
		if($temp!=null)
		{
        	for($i=0;$i<count($temp);$i++)
			{
			$this->query_params[$i][0]=current(explode('=',$temp[$i]));
			$this->query_params[$i][1]=end(explode('=',$temp[$i]));
			}
		}*/
		
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
			print_r($_GET);
			die();
            break;
        case 'PUT':
            $this->request = $this->_cleanInputs($_GET);
            $this->input_file = file_get_contents("php://input");
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