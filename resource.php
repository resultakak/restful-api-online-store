<?php

require_once "DBWrapper.php";

class Resource
{
	private $db;
	public $TableName;
	public $fields=Array();
	public $primarykey_field;
	
    
	public function __construct($tablename,$db)
	{
		$this->db=$db;
		$this->TableName = $tablename;
		$this->fields=$this->db->getFields($this->TableName);
		$this->primarykey_field = $this->db->getPrimaryKey($this->TableName);
	}
    
    	
	
}

?>