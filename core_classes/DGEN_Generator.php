<?php
/**
 * DB Class Generator
 * This class handles the Code Generation.
 * It retrives the selected table to be generated
 * and then creates a file as a generated output
 * 
 * It uses CRUD function list for all the generated class
 * This class was inspired by Php Object Generator for its
 * method implementation and idea.
 * 
 * @version 0.1
 * @package DBClassGenerator
 * */
class DGEN_Generator {
	
	/**
	 * Database Connection Object
	 * Internal Varaible for retriving field information
	 * and data.
	 * 
	 * @var DbConnection
	 * @access private	
	 * */
	var $__dbConn;
	
	/**
	 * The path where generated class would be located
	 * the default generated class would be located on
	 * '/objects/'
	 * 
	 * @var string
	 * @access private
	 * */
	var $__generatedClassPath;
	
	/**
	 * Field Listings
	 * 
	 * @var array
	 * */
	var $__fieldListings;
	
	/**
	 * Get Methods Functions
	 * 
	 * @var array
	 * */
	var $__getMethods;
	
	/**
	 * Set Methods
	 * 
	 * @var array
	 * */
	var $__setMethods;
	
	/**
	 * Generated Methods
	 * 
	 * @var array
	 * 
	 * */
	var $__methodListings;
	
	
	/**
	 * Default Constructor for the Generator Class
	 * This constructor 
	 * 
	 * 
	 * */
	function __construct($dbConn, $generatedClassPath = '') {
		// Sets the Database Connection Object
		$this->__dbConn = $dbConn;
		
		// Initialize generator array
		$this->__fieldListings = array();
		$this->__getMethods = array();
		$this->__setMethods = array();
		$this->__methodListings = array();
		
		// If the generatedClassPath is not set
		// use a default class path location
		if(empty($generatedClassPath)) {
			$generatedClassPath = "objects/";		
		}
		
		// Sets the location of the generated Class Path
		$this->__generatedClassPath = $generatedClassPath;
		
	}
	
	/**
	 * Generate method stubs
	 * 
	 * @param methodName string
	 * @param defaultParameters array
	 * */
	function generateMethods($methodName, $defaultParameters = array(), $comments = ''){
		$__method = "";
		
		// method parameters
		$methodParameters = "";
		if(count($defaultParameters) > 0) {
			
			// total number of parameters
			$totalParameters = count($defaultParameters);
			
			for($i=0; $i<$totalParameters; $i++) {
				$methodParameters .= " $$defaultParameters[$i],";
			} 
			
			// length of the string -1
			$__strlen = strlen($methodParameters)-1;
			$methodParameters = substr($methodParameters, 0, $__strlen);
			
		}
		
		// generate a new method stub		
		$__method .= "    function $methodName($methodParameters ) {\n";
		
		// return the generated method stub
		return $__method;
	}
	
	/**
	 * Generate an accessor method stubs for the fields provided
	 * 
	 * @param fieldName
	 * @return void
	 * */
	function generateAccessor($fieldName) {
		
		// generate a GET method
		$accessorMethod = $this->generateDocumentation("Get value for field: $fieldName", array(), array("$fieldName"));
		$accessorMethod .= $this->generateMethods("get_$fieldName");
		$accessorMethod .= "        // returns the value of $fieldName\n";
		$accessorMethod .= "        return \$this->$fieldName;\n";
		$accessorMethod .= "    }\n";
		
		// add method to get Method stack
		array_push($this->__getMethods, $accessorMethod);
		
		// generate a SET method
		$parameters = array($fieldName);
		
		$accessorMethod = $this->generateDocumentation("Set value for field: $fieldName", $parameters, array("void"));
		$accessorMethod .= $this->generateMethods("set_$fieldName", $parameters);
		$accessorMethod .= "        // sets the value of $fieldName\n";
		$accessorMethod .= "        \$this->$fieldName = $$fieldName;\n";
		$accessorMethod .= "    }";
		
		// add method to the set Method stack
		array_push($this->__setMethods, $accessorMethod);
	}
	
	/**
	 * Generated Fields method
	 * 
	 * @param fieldname string
	 * @return void
	 * */
	function generateFields($fieldName) {
		
		$__field = $this->generateDocumentation("DB Fields: $fieldName");
		$__field .= "    var $$fieldName;";
		
		// add the field to the fieldListings
		array_push($this->__fieldListings, $__field);
	}
	
	/** 
	 * GENERATE CRUD: Create a new entry
	 * Generate a method that creates a new item inside the database
	 * This method collects all the items and the fields listing in
	 * a table then use it as default parameter.
	 * 
	 * @param tableName
	 * */
	function generateCreateNew($tableName) {
		$tableList = $this->__dbConn->getTable();
		
		// parameters for creating new item
		$parameters = $tableList[$tableName];
		$createNewStub = $this->generateDocumentation("Create a new Record: $tableName", $parameters, array("void"));
		
		// generate a new method stub
		$createNewStub .= $this->generateMethods("createnew_$tableName", $parameters);
		
		// counter
		// used for presentation
		$ctr = 0;
		
		// construct an array string to be inserted into the
		// database
		$_insertItems = "array(";
		
		// loop through all the parameters		
		foreach($parameters as $param) {
			// create spaces and indention
			// if counter value is set to 0
			if($ctr == 0) {
				// no indention
				$_insertItems .= "";
			} else {
				// create indention
				$_insertItems .= "                      ";
			}
			
			$_insertItems .= "$$param,\n";
			$ctr++;
		}
		
		$_insertItems = substr($_insertItems, 0, strlen($_insertItems) -2);
		$_insertItems .= "); \n";
		
		
		// perform a collation for parameters to be inserted in the database
		$createNewStub .= "\n        // items to be inserted in the database \n" .
				          "        \$_obj = $_insertItems\n" .
				          "        // database object connection\n" .
				          "        \$dbConn = \$GLOBALS['dbConn'];\n\n" .
				          "        // perform insert in the database\n" .
				          "        \$dbConn->insert(\"$tableName\", \$_obj);\n";
		$createNewStub .= "    }";
		
		array_push($this->__methodListings, $createNewStub);
	}
	
	/**
	 * GENERATE CRUD: Retrive item
	 * Generates a method that retrives value from the database
	 * it creates a new object that will be use to retrive the item in the 
	 * database
	 * 
	 * @param string fieldID
	 * @param string tableName
	 * */
	function generateRetrive($tableName, $fieldID) {
		// table
		$table = $this->__dbConn->getTable();
		
		// parameters
		$parameters = array($fieldID);
		$createNewStub = $this->generateDocumentation("Retrived an existing record: $tableName", $parameters, array("new ".ucwords($tableName)));
		// create new method stub
		$createNewStub .= $this->generateMethods("get_$tableName", $parameters);
		$createNewStub .= "\n        // retrive the data\n";
		$createNewStub .= "        \$dbConn = \$GLOBALS['dbConn'];\n\n" .
				          "        // retrieved value in the database\n" .
				          "        \$_resultSet = \$dbConn->doQuery(\"SELECT * FROM $tableName WHERE $fieldID = '$$fieldID'\");\n\n" .
				          "        \$__$tableName"."Obj = new $tableName();\n" .
				          "        // return the retrived from the database\n\n" .
				          "        // create a new object\n" .
				          "        \$__obj = new ".ucwords($tableName)."();\n";
		foreach($table[$tableName] as $_field) {
			$createNewStub .= "        \$__obj->set_$_field(\$_resultSet[0]['$_field']);\n";
		}				       
		$createNewStub .= "\n\n        return \$__obj;\n";
		$createNewStub .= "    }";
		
		array_push($this->__methodListings, $createNewStub);
		
	}
	
	/**
	 * GENERATE CRUD: Update record
	 * generate a method stub that updates the data into the database
	 * 
	 * @param string tableName
	 * @param string fieldID
	 * */
	function generateUpdate($tableName, $fieldID) {
		// parameters
		
		$parameters = array($fieldID, "itemsToBeUpdated = array()");
		$createNewStub = $this->generateDocumentation("Update an existing record: $tableName", $parameters, array("void"));
		// create new method stub
		$createNewStub .= $this->generateMethods("update_$tableName", $parameters);
		$createNewStub .= "\n         // get database connection\n" .
				          "         \$dbConn = \$GLOBALS['dbConn'];\n\n" .
				          "         // performs update in the database\n" .
				          "         foreach(\$itemsToBeUpdated as \$_fName => \$_fVal) { \n " .
				          "              \$dbConn->addValuePair(\$_fName, \$_fVal);\n" .
				          "         }\n\n" .
				          "         // perform update operation\n" .
				          "         \$dbConn->update(\"$tableName\", \"$fieldID = '$$fieldID'\");\n" .
				          "    }";
		array_push($this->__methodListings, $createNewStub);
	}
	
	/**
	 * GENERATE CRUD: Delete Record
	 * Generate a Deletes record method in a database given the table parameters
	 * and field name
	 * 
	 * */
	function generateDelete($tableName, $fieldID) {
		// parameters
		$parameters = array($fieldID);
		$createNewStub = $this->generateDocumentation("Delete an existing record: $tableName", $parameters, array("void"));
		// create a new method stub
		$createNewStub .= $this->generateMethods("delete_$tableName", $parameters);
		$createNewStub .= "\n         // get database connection\n" .
				          "         \$dbConn = \$GLOBALS['dbConn'];\n\n" .
				          "         // performs deletion of data\n" .
				          "         \$dbConn->delete(\"$tableName\", \"$fieldID = '$$fieldID'\");\n" .
				          "    }";
		array_push($this->__methodListings, $createNewStub);		          
	}
	
	function generateList($tableName) {
		
		// parameters
		$parameters = array("conditionalStatement = ''");
		$createNewStub = $this->generateDocumentation("Retrived list of objects base on a given parameters: $tableName", $parameters, array("collection of objects: ". ucwords($tableName)));
		// create a new method stub
		$createNewStub .= $this->generateMethods("list_$tableName", $parameters);
		$createNewStub .= "\n         \$dbConn = \$GLOBALS['dbConn'];" .
						  "\n         // check if there is a given parameter list\n";
		$createNewStub .= "         if(!empty(\$conditionalStatement)) { \n" .
				          "              \$sqlStatement = \"SELECT * FROM $tableName WHERE \$conditionalStatement\"; \n" .
				          "         } else { \n" .
				          "              \$sqlStatement = \"SELECT * FROM $tableName\";\n" .
				          "         }\n\n" .
				          "         // retrieve the values base on the query result\n" .
				          "         \$__resObj = \$dbConn->doQuery(\$sqlStatement);\n\n" .
				          "         \$__collectionOfObjects = array();\n" .
				          "         foreach(\$__resObj as \$__rs) { \n" .
				          "            \$__newObj = new ".ucwords($tableName)."();\n\n";
				          
		// retrived the table inforamtion
		$table = $this->__dbConn->getTable();
		 
		// get all the fields
		foreach($table[$tableName] as $__f) {
			$createNewStub .= "            \$__newObj->set_$__f(\$__rs['$__f']);\n";
		}				     
		$createNewStub .= "\n            // add object to collection \n" .
				          "            array_push(\$__collectionOfObjects, \$__newObj);\n" .
				          "         }\n\n" .
				          "         // return collection of objects\n" .
				          "         return \$__collectionOfObjects;\n".
				          "    }" ;
				          
		// add to method listing stack
		array_push($this->__methodListings, $createNewStub);
	} 
	
	/**
	 * Generate documentation stub for all the genrated functions
	 * */
	function generateDocumentation($docs, $param = array(), $result = array()) {
		
		// declare documentation string variable
		// resolve error for PHP5 for 
		$docsStub = "";

		// Resolve the error reported by: Pierre ABOUCAYA
		// regarding the old code. $docsStub =  "    /***\n".
		// ive change it to this new line.  
		$docsStub =  "    /***\n";
		$docsStub .= "     * $docs\n";
		$docsStub .= "     *\n" .
				     "     *\n" .
				     "     * Metodo autogenerado Jerson Gomez <jerson.gomez0517@gmail.com>  \n" .
				     "     *\n";
		
		foreach($param as $__p) {
		    $docsStub .= "     * @param $__p\n";
		}
		foreach($result as $__r) {
			$docsStub .= "     * @result $__r\n";
		}
		
		$docsStub .= "     **/\n";
		
		return $docsStub;
	}
	
	
	function generate($tableName, $fieldID) {
		
		// get the database tables
		$tableInfo = $this->__dbConn->getTable();
		
		// get the table fields
		$fields = $tableInfo[$tableName];
		
		$__gC = "";
		$__gC .= "class ".ucwords($tableName)." extends Database { \n\n";
		
		// perform fields and accessor generation
		foreach($fields as $__f) {
			$this->generateFields($__f);
			
			$this->generateAccessor($__f);
		}
		
		// put the generated fields and methods to a stirng buffer
		foreach($this->__fieldListings as $__fields) {
			$__gC .= $__fields."\n\n";
		}
		
		$__gC .= "//--------------- GET METHODS ----------------------------- //\n";
		// put the generated getmethods to a stirng buffer
 		foreach($this->__getMethods as $__getMethods) {
 			$__gC .= $__getMethods."\n\n";
 		}
 		
 		$__gC .= "//--------------- SET METHODS ----------------------------- //\n";
 		foreach($this->__setMethods as $__setMethods) {
 			$__gC .= $__setMethods."\n\n";
 		}
 			
		// generate the CRUD
		$this->generateCreateNew($tableName);
		$this->generateRetrive($tableName, $fieldID);
		$this->generateUpdate($tableName, $fieldID);
		$this->generateDelete($tableName, $fieldID);
		
		// item listings
		$this->generateList($tableName);
		
		$__gC .= "//--------------- CRUD METHODS ----------------------------- //\n";
		foreach($this->__methodListings as $__methodList) {
			$__gC .= $__methodList."\n\n";
		}
		
		$__gC .= "}";
		
		// final output of the file
		$__gC = "<?php ".$__gC." ?>";
		
		// write the file into the designated location
		$this->_writeFile($__gC, $this->__generatedClassPath.strtolower($tableName).".class.php");
		
		// reset everything
		$this->__fieldListings = array();
		$this->__getMethods = array();
		$this->__setMethods = array();
		$this->__methodListings = array();
	}
		
	/**
	 * Write The content for the class
	 * into a file into a defined directory
	 * provided in the constructor
	 * */
	function _writeFile($fClass, $fName) {
	   // if the directory does not exists then
	   // create it
	   if(!is_dir($this->__generatedClassPath)) {
	   	  // create the directory
	      mkdir($this->__generatedClassPath);
	   }	
		
	   error_log(__FILE__.':'.__LINE__." $fName");
	   if (!$handle = fopen($fName, 'w')) {
	         echo "Cannot open file ($fName)";
	         exit;
	   }
	   
	   // Write $somecontent to our opened file.
	   if (fwrite($handle, $fClass) === FALSE) {
	       echo "Cannot write to file ($fName)";
	       exit;
	   }
	   fclose($handle);
	  
	 
	}
	
	
}
?>
