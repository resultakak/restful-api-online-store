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
	
	public function insert($table,$params)
	{
		$query = "INSERT INTO $table(";
		
		$keys=array_keys($params);
		for($i=0;$i<count($keys);$i++)
		{
			$query.= ($i==count($keys)-1)? $keys[$i] : $keys[$i].',';
		}
			
		$query.=") VALUES (";
		for($i=0;$i<count($keys);$i++)
		{
			$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].',';
		}
		$query.=")";
		
		$stmt = $this->conn->prepare($query);
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $params[$keys[$i]]);
		}                            
		$stmt->execute(); 
	}
	
	public function update($table,$updateParams,$conditionParams)
	{
		$query = "UPDATE $table SET ";
		
		$keys=array_keys($updateParams);
		for($i=0;$i<count($keys);$i++)
		{
			$query.= $keys[$i];
			$query.= '=';
			$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].',';
		}
			
		echo $query.=" where 1=1";
		
		$stmt = $this->conn->prepare($query);
        
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $updateParams[$keys[$i]]);
		}                            
		$stmt->execute(); 
	}
	
	public function delete($table, $where = '1=1', $params = array())
	{
        //create query
        $query = "DELETE FROM $table WHERE $where";
		
        //prepare statement
        $stmt = $this->conn->query($query);

        $stmt->execute($params);
				
	}
	
}
?>