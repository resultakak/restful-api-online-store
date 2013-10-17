<?php
class db {

    public $conn; //PDO
	
	public function __construct()
	{
		try 
	 	{
     	$this->conn = new PDO('mysql:host=localhost;dbname=online-store', 'root', '');
     	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	 	}
	 	catch(PDOException $e) 
	 	{
     	echo 'ERROR: ' . $e->getMessage();
		}
	}
	
    public function select($table, $fields = '*' , $where = '1=1', $params = array(), $limit = '', $sort, $fetchStyle = PDO::FETCH_ASSOC) { //fetchArgs, etc

        //create query
        $query = "SELECT $fields FROM $table WHERE $where order by $sort $limit";
		
        //prepare statement
        $stmt = $this->conn->query($query);

        $stmt->execute($params);

        return $stmt->fetchAll($fetchStyle);
    }
	
}
?>