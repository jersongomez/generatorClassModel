<?php
/**
 * CORE DATABASE FUNCTION
 * this Class handles the database connection manager and interface directly to the db
 * it provides a high level controls over the database without actually performing
 * SQL statement for insert and update.
 * however for selecting records and quering records in the db, SQL is still needed
 * the retrieved value are stored in an array to allow less DB hits per access.
 * it provides a more efficient way of access and quering db tables and controlling
 * data records.
 *
 * this class contains 3 main control
 * <pre>
 *   <li>doQuery</li> - for retriving data from the db using SQL statements
 *   <li>insert</li> - for inserting records from db
 *   <li>update</li> - for updating records from db
 * </pre>
 * Added functionalities:
 * 
 * doQuery() - querying function.  This function transfers the record into a dataset to reduce database hits.
 * This class uses the concept of internal table to manipulate data and store it in the memory for easy access.
 * 
 * getTable() - get all database fields for inserting data into the database
 * persisting the columns in the db.
 * 1.3 - The current fix resolves the table collection bug.
 *
 * @author John Paul de Guzman <jpdguzman@gmail.com>
 * @version 1.3
 * @package functions_db
 */
class DbConnection{
	/**#@+
	 *
	 * Database connection variable
	 * this can be accessed outside the class. but this variable is used only for 
	 * db configuration
	 * 
	 * @var string
	 * @uses $dbConn::variableName
	 */
	var $hostname;
	/**
	 * Connection paramter: database name
	 * @var string
	 **/
	var $databasename;
	/**
	 * Connection paramter: username
	 * @var string
	 **/
	var $username;
	/**
	 * Connection paramter: password
	 * @var string
	 **/
	var $password;
	/**#@-*/
	
	/**
	 * Collection of tables retrived from the database
	 * it contains the fields and the DB schema for inserting and
	 * updating data.  This array makes it easy to map attributes from the table
	 * 
	 * NOTE: this is an internal variable
	 *
	 * @var array
	 */
	var $tableListings;

	var $dbCon;

    /**
	 * Dependent on the core.classes definition
	 * this function uses the configuration array defined in the CONFIG.index.php
	 * instead of defining the variables one by one into the function, this function
	 * accepts an array that contains the naming Constants defined:
	 *
	 * naming CONSTANTS:
	 * <pre>
     *  <li><strong>DBNAME</strong>  - Database name</li>
     *  <li type="square"><strong>HOSTNAME</strong> - Hostname ei. localhost</li>
     *  <li type="square"><strong>USERNAME</strong> - Username</li>
     *  <li type="square"><strong>PASSWORD </strong>- password</li>
	 * </pre>
	 *
	 * @param array $configArray - array with index named base on the CONSTANTS specified
	 * @return void
     */
    function useConfigDefinition($configArray)
	{
		$this->hostname = $configArray['HOSTNAME'];
		$this->databasename = $configArray['DBNAME'];
		$this->username = $configArray['USERNAME'];
		$this->password = $configArray['PASSWORD'];
	}
	
 	/**
	 * Manual Database Configuration 
	 * if the connection setting has to be defined manually for greated flexibility of
	 * the system, then this function does the thing.
	 * this function don't require to follow the standard naming convetion provided when using 
	 * pre-configured configuration array
	 * 
	 * @param string $hn: hostname
	 * @param string $dbname: database name
	 * @param string $uname: username
	 * @param string $pass: password
	 * @return void
	 */
	function useManualDefinition($hn, $dbname, $uname, $pass)
	{
		/* the following lines performs the mapping for the db variables */
		$this->hostname = $hn;
		$this->databasename = $dbname;
		$this->username = $uname;
		$this->password = $pass;
	}
	
	/**
	 * Establish a database connection usign the database variable paramter values
	 * this connection is not using persistent connection
	 * this function is a private function used inside the class.
	 * for performing connection use $dbConn::doConnection() insted.
	 *
	 * @return object $dbCon - Database connection object
	 */
	function establishConnection()
	{
		$dbCon = mysqli_connect($this->hostname, $this->username, $this->password) or trigger_error(mysql_error(),E_USER_ERROR); 
		return $dbCon;
	}
	
	/**
	 * Do database connection
	 * this connection performs the connection using establishConnection function
	 * and then distribute the final value for usage.
	 *
	 * @return void
	 * @uses $dbConn::doConnection()
	 */
	function doConnection()
	{
		// call an internal function for performing connection
		$dbCon2 = $this->establishConnection();
		
		// connect to the database using dbConn variable
		//$this->dbCon = mysqli_select_db($dbCon2, $this->getDbname());
		$this->dbCon = mysqli_connect($this->hostname, $this->username, $this->password, $this->getDbname()) or trigger_error(mysql_error(),E_USER_ERROR); 
	}

    /**
	 * GET database name 
	 * 
	 * @return string $dbConn::getDbname() 
	 */
    function getDbname()
	{
		return $this->databasename;
	}
	
	/**
	 * Query a database using a defined SQL statement
	 * this function translates the records into an array for efficient data access
	 * it provides flexibility over the given data without performing greater hits on the database.
	 * This function also creates a virutal copy of the database.
	 * there are 2 ways to use this function
	 * <pre>
	 *   <li type="square">Using an index field to point on a specific data or data sets</li>
	 *       $dbConn::doQuery("SELECT * FROM table", "tble_field")
	 *	 <li type="square">Using auto number index field</li>
	 *       $dbConn::doQuery("SELECT * FROM table", "auto")
	 * </pre>
	 *
	 * Using table field as result set key
	 * <pre>
	 *    // array to handle the result of the query
	 *    $arrResultSet   = array();
	 *    // perform the query
	 *    $arrResultSet   = $dbConn->doQuery("SELECT * FROM table", "tble_field");
	 * </pre>
	 *
	 * Using auto as result key
	 * auto key has 0 base indexing
	 * <pre>
	 *    // array to handle the result of the query
	 *    $arrResultSet   = array();
	 *    $arrResultSet   = $dbConn->doQuery("SELECT * FROM table", "auto");
	 * </pre>
	 * 
	 * @param sqlStatement - SQL Statements
	 * @param indexField - array Index information, data pointer
	 * @return array dataSet
	 */
	function doQuery($sqlStatment, $indexField = 'auto')
	{
		// perform the query and put it into a temporary variable
		$dbQuery = mysqli_query($this->dbCon, $sqlStatment) or die(mysql_error());
		
		// create an array of queried objects
		$dataSet = array();
		
		// this variable is used for automatic counter
		$i = 0;
		
		// Structuring the internal table for the data
		// loop through the records retrieved 
		while ($rows = mysqli_fetch_assoc($dbQuery)) {
			
			if($indexField != 'auto'){					// if not automatic indexing
			
				// use the specified indexField specified
				// as index pointer and assign the value retrived from the database
				$dataSet[$rows[$indexField]] = $rows;	
				
			}
			else{										// if automatic indexing
				// assign current index count as a pointer for the current
				// data retrived form the databse.
				$dataSet[$i] = $rows;
				// increase index counter
				$i++;
			}
			
    	} 
		
		// free results from the databse
		mysqli_free_result($dbQuery);

		return $dataSet;
	}
	
	/**
	 * This function performs the insert function
	 * this is a high level controls to provide a faster way
	 * to insert data into databse, without actually knowing the fields names
	 * in this function, order of fields in the database is essential in making
	 * the function works.  the values should be equal to the number of
	 * fields found in the table.
	 * this function uses the table listing built during runtime.
	 *
	 * <pre>
	 * 		<li type="square">dbschema: user(index_num:PK, userid:UNIQUE, password, access_id:FK)</li>
	 *      this assumes that index_num is autonumbered
	 * 		<li type="square">$values = {'0', $frmUserid, $frmPassword, $frmAccessID};</li>
	 *      this assumes that you have specific values for: frmUserid, frmPassword & frmAccessId
	 *		<li type="square">$dbConn::insert("user", $values)</li>
	 *      Performs the insert function from the db using the values from the $values array
	 * </pre>
	 *
	 * Using insert Function (using the user dbschema)
	 * <pre>
	 *      // array of values to be inserted in to the db.
	 *      // NOTE: order is important in using this function
	 *      $arrValues = {'0', $frmUserid, $frmPassword, $frmAccessID};
	 *      // perform database insert
	 *      $dbConn->insert("user", $arrValues);
	 * </pre>
	 *
	 * NOTE: for autonumbers: provide '0' for the data needed an autonumber.
	 *
	 * @param string $table	- table needed to perform insert
	 * @param array $values - array of values to be inserted in the database
	 * @return int $dbQuery - query result
	 */
	function insert($table, $values)
	{
		// get all the table information base on the table provided
		$fields = $this->tableListings[$table];
		
		// set up the initial SQL statements for insert
		$strSQL = "INSERT INTO ".$table." (";
		
		// loop throughout the fields and add the table references for SQL statement
		// listings
		for($f=0; $f<sizeof($fields); $f++){
			
			// append the value of the fields to the current SQL statement
			$strSQL .= $fields[$f];
			
			// if the counter has already reached the last element then
			// don't added comma.
			if($f < sizeof($fields)-1){
				$strSQL .= ",";
			}
		}
		
		// define all the needed values
		$strSQL .= ") VALUES(";
		
		// loop through the values provided as input
		for($v=0; $v<sizeof($values); $v++){
			// append the values to the current SQL statement
			$strSQL .= "'$values[$v]'";
			
			// if the size of the values array has not reached the last element 
			// then add a comma at the end
			if($v < sizeof($values)-1){
				$strSQL .= ",";
			}
		}
		
		// the SQL has been done throughout the transformation process
		$strSQL .= ")";
		
		// perform the query
		$dbQuery = mysqli_query($this->dbCon, $strSQL) or die(mysql_error());
		
		// return query results.
		return $dbQuery;
	}
    
	/** 
	 * This functions update data from the database
	 *
	 * this function uses the same concepts on insert function.  the same protocol was
	 * used and implemented.
	 * However addValuePair function must be implemented first in order for this to work.
	 * 
	 * <pre>
	 *		<li type="square">dbschema: user(index_num, userid, password, access_id)</li>
	 *		<li>New Value needed to be updated: </li>
	 *      $dbConn::addValuePair("access_id", $value)	- access_id with a new value of $value
	 *		<li>Perform the update: $dbConn:update("user", "userid=$userid_val")</li>
	 * </pre>
	 *
	 * Using this function: (using the user dbschema)
	 * updating the access id of the user
	 * <pre>
	 *     // create a value pair mapping for access_id and its new value to be updated.
	 *     $dbConn->addValuePair("access_id", "4");
	 *     // update condition (no need to add a where clause)
	 *     $strCondition = "user_id = '5'";
	 *     // perform the update function
	 *     $dbConn->update("user", $strCondition);
	 *
	 *     // Where does addValuePair function goes?
	 *     // the value pair was stored into a global variable and then
	 *     // evaluated using the update function.
	 *     // therefore, the function won't work properly if there are no value pair function defined.
	 * </pre>
	 *
	 *
	 * @param string $table	- table needed to perform update
	 * @param array $values - array of values to be update in the database
	 * @return int $dbQuery - query result
	 */
	function update($table, $conditionals)
	{
		// SQL update statement initial setup
		$strSQL = "UPDATE ".$table." SET ";
		
		// reset counter
		$f = 0;
		
		// loop through the values of fieldpair globals
		while(list($key, $val) = each($GLOBALS['fieldpair'])){
			// add it to the SQL statement
			$strSQL .= $key." =  '".$val."' ";
			
			// check if the size of the fieldpair if it is less that the max size of the fieldpair
			if($f < sizeof($GLOBALS['fieldpair'])-1){
			
				// if less than then write a comma at the end of the statement.
				$strSQL .= " , ";
			}
			
			// increase counter
			$f++;
		}
		
		// append the conditional statment
		$strSQL .= 'WHERE '.$conditionals;
		
		// perform the query
		$dbQuery = mysql_query($strSQL) or die(mysql_error());
		
		// reset teh value of the fieldpair definition
		unset($GLOBALS['fieldpair']);
		
		// return query value
		return $dbQuery;
	}
	
	/**
	 * Add a field = value pair relationship for updating records into the DB
	 *
	 * this must be used first before updating.  This sets up the mapping of fields and
	 * the updated value.
	 *
	 * @uses $dbConn::update()
	 * @param string $field - field name needed to be mapped to a certain value, must be a db field
	 * @param string $values - "the value needed to be updated and mapped into the field specified"
	 * @return void
	 */
	function addValuePair($field, $values)
	{
		// add the value pair into a global variables with a key 'fieldpair'
		$GLOBALS['fieldpair'][$field] = $values;
	}
	
	/**
	 *  Delete record from database
	 *
	 * this function deletes a record from the database using 2 parameters.
	 * this is a high-level control that automatically generates the SQL statement.
	 * there are 2 variations in using this function:
	 * <pre>
	 *     <li type="square">Non-conditional deletion of records from the DB</li>
	 *     <li type="square">Conditional deletion of records from the DB using condition SQL</li>
	 * </pre>
	 *
	 * Using this function: (using the same dbschema)
	 * <pre>
	 *      // using non-conditional deletion
	 *      // delete all the records from the table user
	 *      $dbConn->delete("user", "");
	 *
	 *      // using conditional deletion
	 *      // deletes the user with id = 5
	 *      $strCondition   = "user_id = '5'";
	 *      // perform deletion
	 *      $dbConn->delete("user", $strCondtion):
	 * </pre>
	 *
	 * @param string $table - table to be deleted
	 * @param string $condition - SQL conditions for deletion (can be null)
	 * @return void
	 **/
	function delete($table, $conditionals)
	{
		if(empty($conditionals)){					// if the conditions specified is empty
			// perform non-conditional deletion
			$strSQL = "DELETE FROM ".$table;
		}else{										// if the conditions specified not empty
			// perform a conditional deletion
			$strSQL = "DELETE FROM ".$table." WHERE ".$conditionals;
		}
		
		// execute query
		$dbQuery = mysqli_query($this->dbCon, $strSQL) or die(mysql_error());
	}
	
	
    /**
     * Get Database listings
     * 
     * @author Marley Adriano de Souza Silva <marleyas@gmail.com>
     */
     function getDb(){
		// the query to perform to get all the tables
		$query = "SHOW DATABASES";
		$db = $this->doQuery($query,'Database');

			// the db list array
			$dbList = array();
			
			// array pointer
			$i = 0;

            while(list($fName, $fVal) = each($db)){
            // put it into the array
            $dbList[$i] =$fName;
            $i++;
			}
		return $dbList;
     }
     
     
	/**
	 * Get database table listings
	 *
	 * This function generates and collects all the table avaiable on a specified database name
	 * defined using the variable $databasename.
	 * this function collects the table and put it into an array for referencing during inserts
	 * it provides a faster way to insert records in the database without actually knowing the
	 * name of the fields.
	 * this fucntion uses SHOW TABLES query to generate all the table on the defined database
	 * then translated into an array.
	 *
	 * NOTE: this function is an internal function used in CONFIG.index.php
	 *
	 * @return mixed @tableList
	 */
	function getTable(){
		// the query to perform to get all the tables
		$query = "SHOW TABLES";
		$db = $this->doQuery($query,'Tables_in_'.$this->getDbName());		
		// perform the query using key named as: Tables_in_#specified database name#
		$db = $this->doQuery($query,'Tables_in_'.$this->getDbName());
		echo "<pre>";
		// get the table listings collected and generated in the query
		while(list($key, $val) = each($db)){
			// DESCRIBE query, gets all the field in a table
			$tableInfoQuery = "DESCRIBE ".$key;
			// perform the query having Field as key
			$dbQuery = $this->doQuery($tableInfoQuery, 'Field');
			
			// the field list array
			$fieldList = array();
			
			// array pointer
			$i = 0;
			// loop through the generated field list
			
			while(list($fKey, $fVal) = each($dbQuery)){
				// put it into the array
				$fieldList[$i] =$fKey;
				$i++;
			}
			// after completing the loop, put the results into the table list array	
			$tableList[$key] = $fieldList;
		}
		
		// put the final value into the class variable $tableListings
		$this->tableListings = $tableList;
		
		// returned value
		return $tableList;
	}
    
    /**
    * This query is a simple query execution command
    * 
    * @param string SQL statement to execute
    */
    function _iQuery($sqlStatment)
	{
		$dbQuery = mysqli_query($this->dbCon, $sqlStatment) or die(mysql_error());
                
        return $dbQuery;
    }
}
?>