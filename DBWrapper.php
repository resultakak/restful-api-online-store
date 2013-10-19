<?php
class db {

    public $conn; //PDO
	public $memcache;
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
        
        $this->memcache = new Memcache; 
        $this->memcache->connect("localhost",11211);  
    
	}
	
	public function getPrimaryKey($table)
	{
		try
		{
			$stmt = $this->conn->prepare("SHOW KEYS FROM $table WHERE Key_name =  'PRIMARY'");
			$stmt->execute();
			$array = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $array[0]['Column_name'];
		}
		catch(PDOException $e)
		{
			echo 'ERROR: ' . $e->getMessage();
		}
	}
	
	public function getFields($table)
	{
		try
		{
			$stmt = $this->conn->prepare("DESCRIBE $table");
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_COLUMN);
		}
		catch(PDOException $e)
		{
			echo 'ERROR: ' . $e->getMessage();
		}
	}
	
    public function memcacheSelect($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC)
    {
        $query_memcache = "SELECT $fields FROM $table";
            
            if(count($conditionParams)>0)
            {
                $query_memcache.= " WHERE "; 
                $keys=array_keys($conditionParams);
                for($i=0;$i<count($keys);$i++)
                {
                    $query_memcache.= $keys[$i];
                    $query_memcache.= ' = ';
                    $query_memcache.= ($i==count($keys)-1)? "'".$conditionParams[$keys[$i]]."'" : "'".$conditionParams[$keys[$i]]."'".' and ';
                }
            }
            
            if(isset($sort))
            {
            $query_memcache.= " order by $sort ";
            }
            $query_memcache.= "$limit";
            return $query_memcache;
    }
    
    public function select($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC) { //fetchArgs, etc
        $query_memcache = $this->memcacheSelect($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
        
        //Exact Match
        if($this->memcache->get(md5($query_memcache)))
        {
           $result =  $this->memcache->get(md5($query_memcache));
           return $result;
        }
        else {
            if($fields!='*')
            {
                $query_memcache_subset = $this->memcacheSelect($table, '*', $conditionParams, $limit, $sort, $fetchStyle);
                if($this->memcache->get(md5($query_memcache_subset)))
                {
                    $fields_array=explode(',',$fields);
                    
                    $result =  $this->memcache->get(md5($query_memcache_subset));
                               
                    $count=count($result);
                    for($i=0;$i<$count;$i++)
                    {
                         $keys=array_keys($result[$i]);
                         for($j=0;$j<count($keys);$j++)
                         {
                            if(!(in_array($keys[$j], $fields_array)))
                            {
                                unset($result[$i][$keys[$j]]);
                            }
                         }        
                    }
                    
                    array_values($result);
                    $this->memcache->add(md5($query_memcache), $result,false, 60);
                    return $result;
                }
            }
        }
       
        $successful_execute=false;
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
		    $keys=array_keys($conditionParams);
            
			for($i=0;$i<count($keys);$i++)
			{
			    $stmt->bindParam(':'.$keys[$i], $conditionParams[$keys[$i]]);
        	}                            
		}
		
		if($stmt->execute())
        {
            $successful_execute = true;
        }
        
        $result = $stmt->fetchAll($fetchStyle);
        
        if($successful_execute)
        {
            $query_memcache = $this->memcacheSelect($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
            $this->memcache->add(md5($query_memcache), $result,false, 60);
        }
        return $result;
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
		
		return $stmt->rowCount();
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
		return $stmt->rowCount();
	}
}
?>