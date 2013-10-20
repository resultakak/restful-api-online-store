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

    //Function to get a parameuterized PDO Query String
    public function getPDOParameuterizedSelectQuery($table, $fields = '*' ,  $conditionParams, $limit = '', $sort=null, $fetchStyle = PDO::FETCH_ASSOC)
    {
        $query = "SELECT $fields FROM $table";
        
        //Checking if any conditions are there for the 'Where' Clause
        if(count($conditionParams)>0)
        {
            $query.= " WHERE "; 
            //Fetching and appending the names of parameters and their parameuterized names in the where clause
            //in the format "where id=:id and name=:name" etc
            $keys=array_keys($conditionParams);
            for($i=0;$i<count($keys);$i++)
            {
                $query.= $keys[$i];
                $query.= ' = ';
                $query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].' and ';
            }
        }
        
        //checking if the result needs to be sorted
        if(isset($sort))
        {
          $query.= " order by $sort ";
        }
        
        //Applying the limit clause parameter
        $query.= "$limit";
        
        $stmt = $this->conn->prepare($query);
        
        //Binding the condition parameters with their walues
        if(count($conditionParams)>0)
        {
            $keys=array_keys($conditionParams);
            
            for($i=0;$i<count($keys);$i++)
            {
                $stmt->bindParam(':'.$keys[$i], $conditionParams[$keys[$i]]);
            }                            
        }
        
        //returning object of type PDO Statement
        return $stmt;
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
                //To find a match with all fields('*'). Required fields can then be taken out of the corresponding result set. 
                //For example:- In MemCache, md5(select * from categories) is present as a key
                //Current Query:- select id,name from categories
                //If no exact match, replace 'id,name' with '*' and search its md5 in Memcache.
                //Result found. Therefore, subset of fields can be retrieved from the MemCache.
        
                //Getting the standard query string for the parameters(fields replaced with '*') from the getQueryString() function
                $query_memcache_subset = $this->getQueryString($table, '*', $conditionParams, $limit, $sort, $fetchStyle);
                
                //Checking if a match is found in memcache for the query string with fields as ('*')-all fields
                if($this->memcache->get(md5($query_memcache_subset)))
                {
                    //If result found, explode the subset fields of fields required into an array 
                    $fields_array=explode(',',$fields);
                    
                    $result =  $this->memcache->get(md5($query_memcache_subset));
                    
                    //Counting no of records in the result set.           
                    $count=count($result);
                    
                    //Getting the subset of fields required from the result
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
                    
                    //Rearranging the result, adding it to the memcache and returning it.
                    array_values($result);
                    $this->memcache->add(md5($query_memcache), $result,false, 60);
                    return $result;
                }
            }
        }
       
        //Code reaches here only if no match is found above. Now the query gets executed in the database
        $successful_execute=false;
        
        //Gets the parameteurized PDO String 
        $stmt = $this->getPDOParameuterizedSelectQuery($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
        
        //If the parameuterized String gets executed
		if($stmt->execute())
        {
            $successful_execute = true;
        }
        
        $result = $stmt->fetchAll($fetchStyle);
        
        //Add to Memcache if the query gets successfully executed
        if($successful_execute)
        {
            $query_memcache = $this->getQueryString($table, $fields, $conditionParams, $limit, $sort, $fetchStyle);
            $this->memcache->add(md5($query_memcache), $result,false, 60);
        }
        
        //Returning the result object
        return $result;
    }
	
    //Function to insert records in table $table with parameters $params
	public function insert($table,$params)
	{
		$query = "INSERT INTO $table(";
		
        //Fetching and appending the names of parameters
        //in the format "insert into table(name,description,parent_id)
        $keys = array_keys($params);
		for($i=0;$i<count($keys);$i++)
		{
			$query.= ($i==count($keys)-1)? $keys[$i] : $keys[$i].',';
		}
		
        //Fetching and appending the parameuterzied names of parameters
        //in the format "values(:name,:description,:parent_id)"	
		$query.=") VALUES (";
		for($i=0;$i<count($keys);$i++)
		{
			$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].',';
		}
		$query.=")";
		
		$stmt = $this->conn->prepare($query);
        
        //Binding the  parameters to their values
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $params[$keys[$i]]);
		}
                      
        //Executing the prepared statement              
		$stmt->execute(); 
        
        //Returning the last inserted id
		return $this->conn->lastInsertId();
    }
	
    //Function to update a record in $table, set updated data as in $updateParams, for the record matching $conditionsParams
	public function update($table,$updateParams,$conditionParams)
	{
	    $query = "UPDATE $table SET ";
	    
		//Fetching and appending the names of update parameters and their parameuterized names in the where clause
        //in the format "set id=:id, name=:name" etc
		$keys=array_keys($updateParams);
		for($i=0;$i<count($keys);$i++)
		{
			$query.= $keys[$i];
			$query.= ' = ';
			$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].', ';
		}
			
		$query.=" where ";
		
        //Fetching and appending the names of condition parameters and their parameuterized names in the where clause
        //in the format "where id=:id and description=:description" etc
		$conditionKeys=array_keys($conditionParams);
		for($i=0;$i<count($conditionKeys);$i++)
		{
			$query.= $conditionKeys[$i];
			$query.= ' = ';
			$query.= ($i==count($conditionKeys)-1)? ':D'.$conditionKeys[$i] : ':D'.$conditionKeys[$i].' and ';
		}
		
		$stmt = $this->conn->prepare($query);
        
        //Binding the Update parameters to their values
        for($i=0;$i<count($keys);$i++)
		{
			$stmt->bindParam(':'.$keys[$i], $updateParams[$keys[$i]]);
		}                            
        
        //Binding the Condition params to their values
		for($i=0;$i<count($conditionKeys);$i++)
		{
			$stmt->bindParam(':D'.$conditionKeys[$i], $conditionParams[$conditionKeys[$i]]);
		}                            
			
        //Executing the prepared statement    
		$stmt->execute(); 
		
        //Returning the number of rows affected
		return $stmt->rowCount();
	}
	
    //Function to delete a record from table $table which matches $conditionParams 
	public function delete($table, $conditionParams)
	{
        $query = "DELETE FROM $table";
		
        //Checking if any conditions are there for the 'Where' Clause
        if(count($conditionParams)>0)
		{
			$query.= " WHERE "; 
			$keys=array_keys($conditionParams);
		    
		    //Fetching and appending the names of parameters and their parameuterized names in the where clause
            //in the format "where id=:id and name=:name" etc
            for($i=0;$i<count($keys);$i++)
			{
				$query.= $keys[$i];
				$query.= ' = ';
				$query.= ($i==count($keys)-1)? ':'.$keys[$i] : ':'.$keys[$i].' and ';
			}
		}
		
        $stmt = $this->conn->prepare($query);
		
		if(count($conditionParams)>0)
		{
		    //Binding the query paramaters to their values
			for($i=0;$i<count($keys);$i++)
			{
				$stmt->bindParam(':'.$keys[$i], $conditionParams[$keys[$i]]);
			}                            
		}

        //Executing the prepared statement
        $stmt->execute();
        
        //Returning the number of rows affected
		return $stmt->rowCount();
	}
}
?>