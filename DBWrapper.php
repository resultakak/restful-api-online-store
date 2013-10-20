<?php
require_once 'DBConfig.php';

class DBWrapper {
    
    //PDO Connection Object
    private $conn; 
    
    //Memcache Connection Object
	private $memcache;
	
    //Constructor
	public function __construct()
	{
		try 
	 	{
	 	//Connecting to the database host and database using the credentials    
     	$this->conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.'',DB_USER,DB_PASSWORD);
     	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	 	}
	 	catch(PDOException $e) 
	 	{
     	echo 'ERROR: ' . $e->getMessage();
		}
        
        //Making a new Memcache Class Object
        $this->memcache = new Memcache; 
        
        //Connecting to Memcache at the Host 
        $this->memcache->connect(DB_HOST,MEMCACHE_PORT);  
    }
	
    //Function to get the primary key column name from a table
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
	
    //Function returns a standard query string from the standard query parameters which can be parsed in SQL. 
    //Sample Input:- getQueryString('categories','*',Array(id->1,name->vehicles),'limit 0,10','id desc'
    //Sample Output:- select * from categories where id='1' and name='Vehicles' order by id desc limit 0,10
    public function getQueryString($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC)
    {
            $query_memcache = "SELECT $fields FROM $table";
            
            //Checking if any conditions are there for the 'Where' Clause
            if(count($conditionParams)>0)
            {
                $query_memcache.= " WHERE "; 
                
                //Fetching and appending the names of parameters and their values in the where clause
                //in the format "where id=1 and name=test" etc
                $keys=array_keys($conditionParams);
                for($i=0;$i<count($keys);$i++)
                {
                    $query_memcache.= $keys[$i];
                    $query_memcache.= ' = ';
                    $query_memcache.= ($i==count($keys)-1)? "'".$conditionParams[$keys[$i]]."'" : "'".$conditionParams[$keys[$i]]."'".' and ';
                }
            }
            
            //Checking if the request needs to be sorted
            if(isset($sort))
            {
            $query_memcache.= " order by $sort ";
            }
            
            //Applying the limit clause parameter
            $query_memcache.= "$limit";
            
            return $query_memcache;
    }
    
    //Function to get records from database
    public function select($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC) { //fetchArgs, etc
     
        //Getting the standard query string for the parameters from the getQueryString() function
        $query_memcache = $this->getQueryString($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
     
        //Condition to find an exact Memcache Match
        if($this->memcache->get(md5($query_memcache)))
        {
           $result =  $this->memcache->get(md5($query_memcache));
           return $result;
        }
        else {
            if($fields!='*') 
            {
                //To find a match with all fields. Required fields can be taken out of the corresponding result set
                $query_memcache_subset = $this->getQueryString($table, '*', $conditionParams, $limit, $sort, $fetchStyle);
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
            $query_memcache = $this->getQueryString($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
            $this->memcache->add(md5($query_memcache), $result,false, 60);
        }
        return $result;
    }
	
    //Function to insert records in table $table with parameters $params
	public function insert($table,$params)
	{
		$query = "INSERT INTO $table(";
		
        //finding and appending the query parameter names to the array in the format insert into table(id, name, description) 
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
        
        //Binding the query paramaters to their values
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $params[$keys[$i]]);
		}
                                    
		$stmt->execute(); 
		return $this->conn->lastInsertId();
    }
	
    //Function to update a record in $tabe, set updated data as in $updateParams, for the record matching $conditionsParams
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
	
    //Function to delete a record from table $table which matches $conditionParams 
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
		    //Binding the query paramaters to their values
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