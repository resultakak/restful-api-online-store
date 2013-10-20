<?php

require_once "DBWrapper.php";

class ResourceData
{
	private $db;
	public $TableName;
    public $URLCollectionName;
	public $fields=Array();
	public $primarykey_field;
	
    public function __construct($tablename,$db,$urlCollectionName)
	{
		$this->db=$db;
		$this->TableName = $tablename;
		$this->fields=$this->db->getFields($this->TableName);
		$this->primarykey_field = $this->db->getPrimaryKey($this->TableName);
        $this->URLCollectionName = $urlCollectionName;
	}
  
}

?>