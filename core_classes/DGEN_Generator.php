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

	var $namespace; 
	
	
	
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
			$generatedClassPath = "tmp/";		
		}
		
		// Sets the location of the generated Class Path
		$this->__generatedClassPath = $generatedClassPath;

		$this->namespace = 'App\Models';
		
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
		$fieldNameTmp = $fieldName;
		$fieldName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($fieldName))))));
		// generate a GET method
		$accessorMethod = $this->generateDocumentation("Get value for field: $fieldName", array(), array("$fieldName"));
		$accessorMethod .= $this->generateMethods("get$fieldName");
		$accessorMethod .= "        // returns the value of $fieldName\n";
		$accessorMethod .= "        return \$this->$fieldNameTmp;\n";
		$accessorMethod .= "    }\n";
		
		// add method to get Method stack
		array_push($this->__getMethods, $accessorMethod);
		
		// generate a SET method
		$parameters = array($fieldName);
		
		$accessorMethod = $this->generateDocumentation("Set value for field: $fieldName", $parameters, array("void"));
		$accessorMethod .= $this->generateMethods("set$fieldName", $parameters);
		$accessorMethod .= "        // sets the value of $fieldName\n";
		$accessorMethod .= "        \$this->$fieldNameTmp = $$fieldName;\n";
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
		$__field .= "    private $$fieldName;";
		
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
		$tableNameTmp = $tableName;
		$tableName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))));
		$tableList = $this->__dbConn->getTable();
		
		// parameters for creating new item
		$parameters = array("itemsToBeInsert = array()");
		$createNewStub = $this->generateDocumentation("Create a new Record: $tableName", $parameters, array("void"));
		
		// generate a new method stub
		$createNewStub .= $this->generateMethods("create$tableName", $parameters);
		$createNewStub .= "         \$values = array();\n" .
						  "         // performs update in the database\n" .
						  "         \$sqlStatementAux = '';\n" . 
						  "         \$sqlStatement = \"INSERT INTO $tableNameTmp (\";\n" .
						  "         foreach(\$itemsToBeInsert as \$_fName => \$_fVal) { \n " .
						  "				\$com = (count(\$values) > 0) ? ',' : '';\n" .
						  "				\$sqlStatement .= \"\$com \$_fName\";\n" .
						  "				\$sqlStatementAux .= \"\$com ? \";\n" .
						  "				array_push(\$values, \$_fVal);\n" .
						  "         }\n\n" .
						  "         \$sqlStatement .= \") VALUES (\$sqlStatementAux)\";\n" .
						  "         \$__resObj = \$this->db->prepare(\$sqlStatement);\n\n" .
						  "         if (!\$__resObj->execute(\$values)) {\n" . 
						  "				return array('error' => \$__resObj->errorInfo());\n" .
						  "         }\n\n".
						  "         return array('id' => \$__resObj->lastInsertId());\n" .
				          "    }";
		
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
		$createNewStub .= $this->generateMethods("Get" . ucfirst(strtolower($tableName)), $parameters);
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
		$tableNameTmp = $tableName;
		$tableName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))));
		// parameters
		$parameters = array($fieldID, "itemsToBeUpdated = array()", "conditionalStatement = array()");
		$createNewStub = $this->generateDocumentation("Update an existing record: $tableName", $parameters, array("void"));
		// create new method stub
		$createNewStub .= $this->generateMethods("update" . $tableName, $parameters);
		$createNewStub .= "         \$values = array();\n" .
						  "         // performs update in the database\n" .
						  "         \$sqlStatement = \"UPDATE $tableNameTmp SET \";\n" .
						  "         foreach(\$itemsToBeUpdated as \$_fName => \$_fVal) { \n " .
						  "            \$com = (count(\$values) > 0) ? ',' : '';\n" .
						  "            \$sqlStatement .= \"\$com \$_fName = ? \";\n" .
						  "            array_push(\$values, \$_fVal);\n" .
						  "         }\n\n" .
						  "         \$sqlStatement .= \"WHERE $fieldID = '$$fieldID'\";\n" .
						  "         if(!empty(\$conditionalStatement) && count(\$conditionalStatement) > 0) { \n" . 
					  	  "             // performs update in the database\n" .
						  "             foreach(\$conditionalStatement as \$_fName => \$_fVal) { \n" .
						  "                 \$sqlStatement .= \" AND \$_fName = ?\";\n" .
						  "                 array_push(\$values, \$_fVal);\n" .
						  "         	}\n\n" .
						  "         }\n" .
						  "         \$__resObj = \$this->db->prepare(\$sqlStatement);\n\n" .
						  "			if (!\$__resObj->execute(\$values)) {\n" . 
						  "				return array('error' => \$__resObj->errorInfo());\n" .
						  "		    }\n\n".
						  "			return true;\n" .
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
		$tableNameTmp = $tableName;
		$tableName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))));
		// parameters
		$parameters = array($fieldID, "conditionalStatement = array()");
		$createNewStub = $this->generateDocumentation("Delete an existing record: $tableName", $parameters, array("void"));
		// create a new method stub
		$createNewStub .= $this->generateMethods("delete". $tableName, $parameters);
		$createNewStub .= "         // performs deletion of data\n" .
						  "         \$sqlStatement = \"DELETE FROM $tableNameTmp WHERE $fieldID = '$$fieldID'\";\n" .
						  "         \$values = array();\n" .
						  "         if(!empty(\$conditionalStatement) && count(\$conditionalStatement) > 0) { \n" . 
					  	  "             // performs delete in the database\n" .
						  "             foreach(\$conditionalStatement as \$_fName => \$_fVal) { \n " .
						  "             	\$sqlStatement .= \" AND \$_fName = ?\";\n" .
						  "					array_push(\$values, \$_fVal);" .
						  "         	}\n\n" .
						  "         }\n" .
						  "         \$__resObj = \$this->db->prepare(\$sqlStatement);\n\n" .
						  "			\$__resObj->execute(\$values);\n" .
				          "    }";
		array_push($this->__methodListings, $createNewStub);		          
	}
	
	function generateList($tableName) {
		$tableNameTmp = $tableName;
		$tableName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))));
		// parameters
		$parameters = array("findFields = array()", "conditionalStatement = array()","limit = 0"," search = false");
		$createNewStub = $this->generateDocumentation("Retrived list of objects base on a given parameters: $tableName", $parameters, array("collection of objects: ". ucwords($tableName)));
		// create a new method stub
		$createNewStub .= $this->generateMethods("list". $tableName, $parameters);
		$createNewStub .= "\n         // check if there is a given parameter list\n";
		$createNewStub .= "         \$sqlStatement = \"SELECT \";\n" .
						  "         if(!empty(\$findFields) && count(\$findFields) > 0) { \n" .
						  "             // performs select in the database\n" .
						  "             \$sqlStatement .= implode(',', \$findFields);\n" .
						  "         } else { \n" .
						  "             \$sqlStatement .= \" * \"; \n" .
						  "         }\n\n" .
						  "         \$sqlStatement .= \" FROM $tableNameTmp \";\n" .
						  "         \$values = array();\n" .
						  "         if(!empty(\$conditionalStatement) && count(\$conditionalStatement) > 0) { \n" . 
						  "				\$sqlStatement .= \" WHERE \";\n	\$_add = 0;							".	
						  "             // performs select in the database\n" .
						  "             foreach(\$conditionalStatement as \$_fName => \$_fVal) { \n " .
						  "					\$_conditionalSearch = (\$_add != 0) ? 'OR' : ''; \n".
						  "					\$_conditional = (\$_add != 0) ? 'AND' : ''; \n".
						  "					\$sqlStatement .= (\$search) ? " \$_conditionalSearch \$_fName LIKE '%\$_fVal%' \" : \" \$_conditional \$_fName = ? \"; \n".
						  "					\$_add++;	\n".	
						  "					array_push(\$values, \$_fVal);" .
				          "         	}\n\n" .
						  "         }\n" .
						  "         if(\$limit > 0) { \n" .
						  "				\$sqlStatement .= \" LIMIT \$limit \";\n" .
						  "         }\n" .
				          "         // retrieve the values base on the query result\n" .
						  "         \$__resObj = \$this->db->prepare(\$sqlStatement);\n\n" .
						  "			\$__resObj->execute(\$values);\n" .
						  "			\$amount = \$__resObj->rowCount();\n" .
						  "         \$__collectionOfObjects = array();\n" .
						  "         if(\$amount > 0) { \n" .
						  "         	while(\$__rs = \$__resObj->fetch(\PDO::FETCH_ASSOC)) { \n" .
						  "            		\$__newObj = new ".ucwords($tableName)."Model(false);\n\n";
				          
		// retrived the table inforamtion
		$table = $this->__dbConn->getTable();
		 
		// get all the fields
		foreach($table[$tableNameTmp] as $__f) {
			$__fParam = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($__f))))));
			$createNewStub .= "					\$__newObj->set$__fParam((isset(\$__rs['$__f']) ? \$__rs['$__f'] : ''));\n";
		}				     
		$createNewStub .= "\n            	// add object to collection \n" .
				          "            		array_push(\$__collectionOfObjects, \$__newObj);\n" .
						  "         	}\n\n" .
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
		$docsStub =  "    /**\n";
		$docsStub .= "     *\n";
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
		$verificar = 'verify';
		$objThis = 'this->db';
		$db = 'db';
		$__gC = "";
		$__gC .= "\n\n namespace " . $this->namespace . "; 
					\n use App\Classes\Database;
					\n\nclass ".str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))))
					."Model extends Database { \n\n";
		$__gC .= "private $$db;

				public function __construct($$verificar = true) {
					if ($$verificar) {
						$$objThis = Database::getInstance();
					}
				}\n\n";
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
		//$this->generateRetrive($tableName, $fieldID);
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
		$tableName = str_replace(' ','',(ucwords(str_replace("_", " ", ucfirst(strtolower($tableName))))));
		$this->_writeFile($__gC, $this->__generatedClassPath.$tableName."Model.php");
		
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
