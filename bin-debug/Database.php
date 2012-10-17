<?php
/**
* @package FM6 Kernel
* @author Bob de Wit
* @copyright 2006 active6.com. All rights reserved.
* @version 1.0.0
* 
*/

/**
 * Base class for MySQL Databases. Derived from universal active6 Base Classes and 
 * adapted for the FM6 kernel.
 *
 */
class Database
{
	var $tableName;
	/**
	 * MySQL Database connection handler
	 *
	 * @var handle
	 * @access private
	 */
	var $dbLink;
	
	/**
	 * Array of field => value pairs for easy SQL statement generation
	 *
	 * @var array
	 */
	var $fields;
	
	/**
	 * ID Field value = record ID of the data in the table
	 *
	 * @var integer
	 */
	var $idField;
	
	
	/**
	 * Field Name of the Unique ID field. Defaults to 'id'
	 *
	 * @var unknown_type
	 */
	var $idFieldName;
		
	/**
	 * Last Error message returned by MySQL
	 *
	 * @var string
	 */
	var $error;

	/**
	 * Constructor for the Database class
	 *
	 * @param string $tableName Optional name of the table to initialize with
	 * @param string $idFieldName Optional name of the Unique ID field (defaults to 'id')
	 * @return Database
	 */
	function Database( $tableName = '', $idFieldName = 'id')
	{
		$this->connect();
		$this->tableName = $tableName;
		$this->idFieldName = $idFieldName;
		$this->fields = array();
	}
	
	/**
	 * Create a paged SQL statement with filter and ordering
	 *
	 * @param string $sql Base SQL SELECT statement
	 * @param string $filter WHERE conditions (empty by default)
	 * @param string $order ORDER BY conditions (empty by default)
	 * @param integer $start Start record offset (0 by default)
	 * @param integer $limit Number of records to return (0 by default = ALL records)
	 * @return string
	 */
	function pagedSQL( $sql, $filter = '', $order = '', $start = 0, $limit = 0 )
	{
		$wsql = $sql;
		if ($filter <> '')		
		{
			$wsql .= " WHERE $filter ";			
		}
		if ($order <> '' )
		{
			$wsql .= " ORDER BY $order ";
		}
		if ($limit > 0)
		{
			$wsql .= " LIMIT $start, $limit ";
		}
		return $wsql;
	}

	/**
	 * Read and return a single MySQL record object
	 *
	 * @param integer $aID Unique Record ID
	 * @return object
	 */
	function read($aID)
	{
		$obj = $this->getObject( "SELECT * from $this->tableName WHERE $this->idFieldName = $aID");
		$this->idField = $aID;
		return $obj;
	}

	/**
	 * Write a set of field => value sets defined in the {@link fields} property into
	 * the database. The method will verify if the {@link idField} value already exists
	 * in the database table. If it does, it will {@link update) the record. If it cannot
	 * find the {@link idfield} value, it will {@link insert} a new record.
	 *
	 * @return object
	 */
	function write()
	{
		$obj = null;
		if (( $this->idField <> null ) && $this->exists( $this->idField ))
		{
			return $this->update();
		}
		else
		{
			return $this->insert();
		}
	}

	/**
	 * Check if a record with the passed unique id already exists
	 *
	 * @param integer $aID Record ID to look for
	 * @return boolean
	 */
	function exists( $aID )
	{
		$obj = $this->getObject( "SELECT $this->idFieldName from $this->tableName WHERE $this->idFieldName = $aID");
		return ($obj <> null );
	}

	
	/**
	 * Delete a record with the passed unique ID
	 *
	 * @param integer $aID
	 */
	function delete( $aID )
	{
		$this->execute( "DELETE from $this->tableName WHERE $this->idFieldName = $aID");
	}

	/**
	 * Set a field=>value pair in the {@link fields} property array
	 *
	 * @param string $fieldName Field Name to set
	 * @param string $fieldValue Field Value to set
	 */
	function setField( $fieldName, $fieldValue )
	{
		$fields =& $this->fields;
		$fields[$fieldName] = $fieldValue;
	}

	
	/**
	 * Reset all values in the {@link fields} property array
	 *
	 */
	function clearFields()
	{
		$this->fields = array();
	}

	
	/**
	 * Insert a new record into the table based on the values in the {@link fields} property
	 *
	 * @return object
	 */
	function insert()
	{
		$this->connect();
		$this->idField = 'NULL';
		$wFieldList = array();
		$wFieldValues = array();
		foreach( $this->fields as $key => $value )
		{
			array_push( $wFieldList, $key);
			array_push( $wFieldValues, "'".addslashes($value)."'");
		}
		$wFields = implode( ',', $wFieldList );
		$wValues = implode( ',', $wFieldValues );
		$sql = "INSERT INTO $this->tableName ($wFields) VALUES ($wValues)";
		LogBook( "insert SQL: $sql" );
		$result = mysql_query( $sql );
		$this->idField = $this->getInsertID();
		if (!$result)
		{
			$this->error = 'Could not Execute Insert Query';
		}
		return $this->read( $this->idField );
	}

	
	/**
	 * Update a record based on the values in the {@link fields} property
	 *
	 * @return object
	 */
	function update()
	{
		$this->connect();
		$wUpdateList = array();
		foreach( $this->fields as $key => $value )
		{
			array_push( $wUpdateList, $key . "= '" . addslashes($value) ."'");
		}
		$wValues = implode( ',', $wUpdateList );
		$sql = "UPDATE $this->tableName SET $wValues where $this->idFieldName = $this->idField";
		$result = mysql_query( $sql );
		if (!$result)
		{
			$this->error = 'Could not Execute Update Query';
		}
		return $this->read( $this->idField );
	}

	/**
	 * Connect to the MySQL database specified in the configuration
	 *
	 */
	function connect()
	{
		if (!$this->dbLink)
		{
			$this->dbLink = mysql_connect(DB_HOST, DB_USER, DB_PW);
		}
		if (!$this->dbLink )
		{
			$this->error = "Could not connect to MySQL";
		}
		else
		{
			mysql_select_db(DB_DBNAME, $this->dbLink);
		}
	}

	/**
	 * Disconnect from the MySQL database
	 *
	 */
	function disconnect()
	{
		if ($this->dbLink )
		{
			mysql_close($this->dbLink);
		}
	}

	
	/**
	 * Execute a MySQL statement
	 *
	 * @param string  $aSQL
	 * @return integer
	 */
	function execute( $aSQL )
	{
		$this->connect();
		$result = mysql_query($aSQL, $this->dbLink);
		if (!$result)
		{
			$this->error = 'Could not Execute Query';
		}
		return $result;
	}

	/**
	 * Get an array of Query result objects from the MySQL Database
	 *
	 * @param string $aSQL
	 * @return array
	 */
	function getObjects( $aSQL )
	{
		$this->connect();
		$wList	= array();
		$result = mysql_query($aSQL, $this->dbLink);
		if (!$result)
		{
			return false;
		}
		else
		{
			while ($row = mysql_fetch_object($result))
			{
				array_push($wList, $row);
			}
			mysql_free_result($result);
			return $wList;
		}
	}

	/**
	 * Get a result object from a MySQL Query
	 *
	 * @param string $aSQL
	 * @return object
	 */
	function getObject( $aSQL )
	{
		$this->connect();
		$result = mysql_query($aSQL, $this->dbLink);
		if (!$result)
		{
			return false;
		}
		else
		{
			$row = mysql_fetch_object($result);
			mysql_free_result($result);
			return $row;
		}
	}

	/**
	 * Get a RAW object result from a MySQL query
	 *
	 * @param string $aSQL
	 * @return object
	 */
	function getRawObject( $aSQL )
	{
		$this->connect();
		$result = mysql_query($aSQL, $this->dbLink);
		return ($result);
	}

	/**
	 * Get the number of rows returned from a MySQL Query
	 *
	 * @param string $aSQL
	 * @return integer
	 */
	function getRowCount( $aSQL )
	{
		$this->connect();
		$result = mysql_query($aSQL, $this->dbLink);
		if (!$result)
		{
			$this->error = 'Could not Execute GetRowCount Query';
		}
		else
		{
			$rowcount = mysql_num_rows($result);
			mysql_free_result($result);
			return $rowcount;
		}
	}

	/**
	 * Get the unique ID from the last {@link insert} statement
	 *
	 * @return integer
	 */
	function getInsertID()
	{
		$this->connect();
		return mysql_insert_id($this->dbLink);
	}

	/**
	 * Serialize a result set of values into a urlencoded string to send to a 
	 * Flash LoadVars() object.
	 *
	 * @param unknown_type $values
	 * @return unknown
	 */
	function serialize( $values )
	{
		return( "result=".urlencode(utf8_encode(serialize($values))));
	}
}
?>
