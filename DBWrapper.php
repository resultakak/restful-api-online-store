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
	
    public function select($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC) { //fetchArgs, etc

        //create query
        $query = "SELECT $fields FROM $table";
		
		if(count($conditionParams)>0)
		{
			$query.= " WHERE "; 
			$keys=array_keys($conditionParams);
			for($i=0;$i<count($keys);$i++)
			{
				$query.= $keys[$i];
				$query.= ' = ';
				$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].' and ';
			}
		}
		if(isset($sort))
		{
		$query.= " order by $sort ";
		}
		$query.= "$limit";
		
		$stmt = $this->conn->prepare($query);
        
		if(count($conditionParams)>0)
		{
			for($i=0;$i<count($keys);$i++)
			{
				$stmt->bindParam(':'.$keys[$i], $conditionParams[$keys[$i]]);
			}                            
		}
		
		$stmt->execute();
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
		return $this->conn->lastInsertId(); 
	}
	
	public function update($table,$updateParams,$conditionParams)
	{
		$query = "UPDATE $table SET ";
		
		$keys=array_keys($updateParams);
		for($i=0;$i<count($keys);$i++)
		{
			$query.= $keys[$i];
			$query.= ' = ';
			$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].', ';
		}
			
		$query.=" where ";
		
		$conditionKeys=array_keys($conditionParams);
		for($i=0;$i<count($conditionKeys);$i++)
		{
			$query.= $conditionKeys[$i];
			$query.= ' = ';
			$query.= ($i==count($conditionKeys)-1)? ':D'.$conditionKeys[$i] : ':D'.$conditionKeys[$i].' and ';
		}
		
		$stmt = $this->conn->prepare($query);
        
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $updateParams[$keys[$i]]);
		}                            
		for($i=0;$i<count($conditionKeys);$i++)
		{
			$stmt->bindParam(':D'.$conditionKeys[$i], $conditionParams[$conditionKeys[$i]]);
		}                            
			
		$stmt->execute(); 
	}
	
	public function delete($table, $conditionParams)
	{
        //create query
        $query = "DELETE FROM $table";
		
		if(count($conditionParams)>0)
		{
			$query.= " WHERE "; 
			$keys=array_keys($conditionParams);
			for($i=0;$i<count($keys);$i++)
			{
				$query.= $keys[$i];
				$query.= ' = ';
				$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].' and ';
			}
		}
		
        //prepare statement
        $stmt = $this->conn->prepare($query);
		
		if(count($conditionParams)>0)
		{
			for($i=0;$i<count($keys);$i++)
			{
				$stmt->bindParam(':'.$keys[$i], $conditionParams[$keys[$i]]);
			}                            
		}

        $stmt->execute();
	}
}
?>