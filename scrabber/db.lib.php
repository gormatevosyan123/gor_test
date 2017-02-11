<?php
   
   $tableMaps = array();

   /**
   * Performs an SQL SELECT statement
   * The caller must provide $data['table'] = table name
   *
   * @param array $info -- the table information for the SELECT statement
   * @return asscociative array $rows -- returns the rows or null
   */
   function select($info = null)
   {

      if (isset($info['table']))
          $table = $info['table'];
      else
          return null;

      if (isset($info['fields']))
         $fields = implode(',', $info['fields']);
      else
         $fields = '*';

      if (isset($info['where']))

         $where = $info['where'];

      else
         $where = '1';

      $stmt = "SELECT $fields FROM $table WHERE $where";

      if (isset($info['debug']) && $info['debug'])
          echo_br($stmt);

      $result = mysql_query($stmt);

      $err    = mysql_error();

      if (isset($info['debug']) && $info['debug'])
          echo_br($err);

      if (!empty($err) || mysql_num_rows($result) < 1)
          return null;

      $data = array();

      while($row = mysql_fetch_assoc($result))
      {
          $data[] = $row;
      }

      return $data;
   }

   /**
   * Inserts data into an SQL table
   * The caller must provide $data['table'] = table name, $data['data'] = array
   * where the data array must field=value pairs
   *
   * @param array $data -- the data, table information for the INSERT statement
   * @return array  $ret -- returns the new row ID or null
   */
   function insert($data = null)
   {
     $ret = array();
     $fieldMap = getTableFieldMap($data['table']);
     $valueList = array();
     $fieldList = array();
     $userData = $data['data'];

     foreach ($fieldMap as $field => $settings)
     {
        list($type,
             $len,
             $required,
             $autoinc,
             $pk,
             $uniq,
             $enum) = explode(':', $settings);

        //$userField = strtolower($field);
        $userField = trim($field);

        if (isset($userData[$userField]))
            $value = trim($userData[$userField]);
        else
            continue;

        $fieldList[] = $field;

        // Quote if the field type requires it
        $valueList[] = (preg_match("/string/i", $type) ||
                        preg_match("/blob/i", $type) ||
                        preg_match("/date/i", $type) ||
                        preg_match("/time/i", $type)
                        ) ? q($value) : $value;
     }

     $fieldStr = implode(',', $fieldList);
     $valueStr = implode(',', $valueList);

     $stmt     = 'INSERT IGNORE INTO ' . $data['table'] . " ($fieldStr) VALUES($valueStr)";

     $result   = mysql_query($stmt);

     $err      = mysql_error();

     if (isset($data['debug']) && $data['debug'])
     {
          echo_br($stmt);
          echo_br("Error: " . $err);
     }

     if (! empty($err))
     {
        if (preg_match("/Duplicate/i", $err))
        {
           $errors[] = $data['dup_error'];
           $ret['newid'] = null;
           $ret['error'] = $errors;
           $ret['affected_rows'] = 0;
        }
     }
     else
     {
        $ret['newid'] = mysql_insert_id();
        $ret['affected_rows'] = mysql_affected_rows();
     }

     return $ret;
   }


   /**
   * Performs an SQL DELETE statement
   * The caller must provide $data['table'] = table name
   *
   * @param array $info -- the table information for the DELETE statement
   * @return bool -- returns true if successful else false
   */
   function delete($info = null)
   {
      if (isset($info['table']))
          $table = $info['table'];
      else
          return null;

      if (isset($info['where']))

         $where = $info['where'];

      // We won't continue unless explicit
      // where clause is given
      else
      {
         return null;
      }
      $stmt = "DELETE FROM $table WHERE $where";

      $result = mysql_query($stmt);
      $err    = mysql_error();

      if (isset($info['debug']) && $info['debug'])
      {
          echo_br("delete($stmt)");
          echo_br("Error: $err");
          echo_br("Affected Rows: " . mysql_affected_rows());
      }
      if (!empty($err) || mysql_affected_rows() < 1)
          return false;

      return true;
   }

   /**
   * Performs an SQL UPDATE statement
   * The caller must provide $data['table'] = table name
   *
   * @param array $info -- the table information for the UPDATE statement
   * @return bool -- returns true if successful else false
   */
   function update($info)
   {

      $table = (isset($info['table'])) ? $info['table'] : null;
      $where = (isset($info['where'])) ? $info['where'] : 1;
      $data  = (isset($info['data']))  ? $info['data']  : null;

      // If table name or data not provided return false
      if (! $table || ! $data)
         return false;

      $updateStr = array();

      // Get the table field meta data
      $fieldMap = getTableFieldMap($info['table']);

       // Quote fields as needed
       foreach ($fieldMap as $field => $settings)
       {
          // Break down each field's meta info into attributes
          list($type,
               $len,
               $required,
               $autoinc,
               $pk,
               $uniq,
               $enum) = explode(':', $settings);

          //$userField = strtolower($field);
          $userField = trim($field);


          if (isset($data[$userField]))
              $value = trim($data[$userField]);
          else
              continue;

          // Special case: value = NULL is changed to value = ''
          if (preg_match("/^NULL$/i", $value))
             $value = '';

          // Quote strings/date/blob type data
          $value= (preg_match("/string/i", $type) ||
                   preg_match("/date/i", $type) ||
                   preg_match("/time/i", $type) ||
                   preg_match("/blob/i", $type)) ? q($value) : $value;
          $updateStr[] = "$field = $value";
       }

      $keyVal = implode(', ', $updateStr);
      $update = "UPDATE $table  SET $keyVal WHERE $where";
      $result = mysql_query($update);
      $err    = mysql_error();
      $affectedRows = mysql_affected_rows();

      // If debugging is turned on show helpful info
      if (isset($info['debug']) && $info['debug'])
      {
         echo_br($update);
         echo_br($err);
         echo_br("Affected rows $affectedRows" );

      }
      return (empty($err)) ? true : false;
   }


   /**
   * Returns all data related to a given table from $_REQUEST super global
   *
   * @param string $table -- the table name for which data is retrieved
   * @return array $resultData  -- the key=value pairs
   */
   function getUserDataSet($table = null)
   {
       // Get the field map for the current table
       $fieldMap = getTableFieldMap($table);

       $resultData = array();

       // Loop through the field map and find out
       // which of the field(s) have data from $_REQUEST
       foreach ($fieldMap as $field => $info)
       {
          list($type,
               $len,
               $required,
               $autoinc,
               $pk,
               $unique,
               $enum) = explode(":",$info);

          // Ignore auto inc field since it is never received from user
          if ($autoinc )
              continue;

          $value = $_REQUEST[$field];

          // If value is available we need to store it
          // in result set
          //if (! empty($value))
          if (isset($value))
          {
              // We trim the string to remove leading
              // and trailing spaces
              if (preg_match("/string/i", $type) ||
                  preg_match("/blob/i", $type)
                 )
              {
                 $value = trim($value);
              }
              else if ((preg_match("/int/i", $type) ||
                        preg_match("/float/i", $type) ||
                        preg_match("/decimail/i", $type) ||
                        preg_match("/double/i", $type)
                        ) && ! is_numeric($value)
                      )
              {
                 // User given value is a NOT number
                 // when we are expecting one!
                 // so we are going to ignore it
                 continue;
              }

              $resultData[$field] = $value;
          }
       }

       return $resultData;
   }


   /**
   * Returns SQL table meta data for a given table
   * Return format:
   * $hash[field] = "type:length:required:autoinc:pk:unique:enum";
   *
   * @param string $table -- the table name
   * @return array  $hash  --
   */
   function getTableFieldMap($table = null)
   {
      global $tableMaps;
      
      if(isset($tableMaps[$table])) {
          return $tableMaps[$table];  
      }
   	
      $result =  mysql_query("SELECT * FROM $table LIMIT 0, 1");

      $errors = mysql_error();

      // If table does not exist, return null
      if (!empty($errors))
          return null;

      // Get field names for the table
      $fields = mysql_num_fields($result);

      // Setup an array to store return info
      $hash = array();

      // For each field, find out what type, length, requirements,
      // PK, unqiue, enum, attributes etc.
      for ($i=0; $i < $fields; $i++)
      {
         $type     = mysql_field_type($result, $i);
         $name     = mysql_field_name($result, $i);
         $len      = mysql_field_len($result, $i);
         $flags    = mysql_field_flags($result, $i);
         $required = (preg_match("/not_null/i", $flags)) ? 1 : 0;
         $autoinc  = (preg_match("/auto_increment/i", $flags)) ? 1 : 0;
         $pk       = (preg_match("/primary/i", $flags)) ? 1 : 0;
         $unique   = (preg_match("/unique/i", $flags)) ? 1 : 0;
         $enum     = (preg_match("/enum/i", $flags)) ? 1 : 0;

         $hash[$name] = "$type:$len:$required:$autoinc:$pk:$unique:$enum";
      }

      // Free the result set
      mysql_free_result($result);

      // Return
      return $hash;
   }
   
   /**
   * Returns a list of enumrated values for a given MySQL table field
   *
   * @param  string $tableName      -- name of the table
   * @return array  $enumValueList  -- list of possible enumrated values for the field
   */
   function getEnumFieldValues($tableName = null, $field = null)
   {
       // Make a DDL query
       $query = "SHOW COLUMNS FROM $tableName LIKE " . q($field);

       $result = mysql_query($query);
       $data   = mysql_fetch_array($result);

       if(eregi("('.*')", $data['Type'], $match))
       {
          $enumStr       = ereg_replace("'", '', $match[1]);
          $enumValueList = explode(',', $enumStr);
       }

       return $enumValueList;
   }
   
   
   // Return the next auto increment id of table $tableName
   function nextId($tableName = null)
   {
      $nextIncrement = 0;
      $qShowStatus = "SHOW TABLE STATUS LIKE '$tablename'";
      $qShowStatusResult = mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "
                        " . qShowStatus );


      while ($row = mysql_fetch_assoc($qShowStatusResult)) 
      {
         $nextIncrement = $row['Auto_increment'];
      }
      mysql_free_result($qShowStatusResult);

      return $nextIncrement;      
   }
   
   //
   function tableField($tableName = null)
   {
      //mysql_list_fields() function is deprecated, Try to use straight mysql_query() 
      $res = mysql_list_fields(DB_NAME, $tableName);
      $columns = mysql_num_fields($res);
      $fields = array();
      for ($i = 0; $i < $columns; $i++) {
         $fields[] = mysql_field_name($res, $i);
      }
      return $fields; 
   }
   
   function getTableData($tableName = null)
   {
      $info = array();
      $info['table'] = $tableName;
      $info['debug'] = false;
      
      $ret = select($info);
      
      return $ret;      
   }

   /**
   * Returns a quoted, mysql ready (i.e properly character-escaped) string
   *
   * @param string $str -- the string to quote and escape for mysql
   * @return string -- the escaped, mysql ready string
   */
   function q($str = null)
   {
      return "'" . mysql_escape_string($str) . "'";
   }   
   
   /**
   * This debugging function is used to print a line with HTML BR tag
   * DO NOT USE THIS IN PRODUCTION CODE! All echo_br() calls
   * in production code must be commented out or removed!
   *
   * @param string $msg -- message to be printed
   * @return null
   */
   function echo_br($msg = null)
   {
      echo  $msg . '</br>';
   }



   /**
   * Inserts data into an SQL table
   * The caller must provide $data['table'] = table name, $data['data'] = array
   * where the data array must field=value pairs
   *
   * @param array $data -- the data, table information for the INSERT statement
   * @return array  $ret -- returns the new row ID or null
   */
   function insertNull($data = null)
   {
     $ret = array();
     $fieldMap = getTableFieldMap($data['table']);
     $valueList = array();
     $fieldList = array();
     $userData = $data['data'];

     foreach ($fieldMap as $field => $settings)
     {
        list($type,
             $len,
             $required,
             $autoinc,
             $pk,
             $uniq,
             $enum) = explode(':', $settings);

        //$userField = strtolower($field);
        $userField = trim($field);

        if (isset($userData[$userField]))
            $value = trim($userData[$userField]);
        else
            continue;

        $fieldList[] = $field;

        // Quote if the field type requires it
        $valueList[] = (preg_match("/string/i", $type) ||
                        preg_match("/blob/i", $type) ||
                        preg_match("/date/i", $type) ||
                        preg_match("/time/i", $type)
                        ) ? q($value) : $value;
     }

     $fieldStr = implode(',', $fieldList);
     $valueStr = implode(',', $valueList);

     $stmt     = 'INSERT IGNORE INTO ' . $data['table'] . " ($fieldStr) VALUES($valueStr)";

     $result   = mysql_query($stmt);

     $err      = mysql_error();

     if (isset($data['debug']) && $data['debug'])
     {
          echo_br($stmt);
          echo_br("Error: " . $err);
     }

     if (! empty($err))
     {
        if (preg_match("/Duplicate/i", $err))
        {
           $errors[] = $data['dup_error'];
           $ret['newid'] = null;
           $ret['error'] = $errors;
           $ret['affected_rows'] = 0;
        }
     }
     else
     {
        $ret['newid'] = mysql_insert_id();
        $ret['affected_rows'] = mysql_affected_rows();
     }

     return $ret;
   }


   /**
   * Returns all data related to a given table from $_REQUEST super global
   *
   * @param string $table -- the table name for which data is retrieved
   * @return array $resultData  -- the key=value pairs
   */
   function getUserDataSetSafe($table = null)
   {
       // Get the field map for the current table
       $fieldMap = getTableFieldMap($table);

       $resultData = array();

       // Loop through the field map and find out
       // which of the field(s) have data from $_REQUEST
       foreach ($fieldMap as $field => $info)
       {
          list($type,
               $len,
               $required,
               $autoinc,
               $pk,
               $unique,
               $enum) = explode(":",$info);

          // Ignore auto inc field since it is never received from user
          if ($autoinc )
              continue;

          $value = $_REQUEST[$field];

          // If value is available we need to store it
          // in result set
          if (! empty($value))
          {
             if (isset($value))
             {
                 // We trim the string to remove leading
                 // and trailing spaces
                 if (preg_match("/string/i", $type) ||
                     preg_match("/blob/i", $type)
                    )
                 {
                    $value = trim($value);
                 }
                 else if ((preg_match("/int/i", $type) ||
                           preg_match("/float/i", $type) ||
                           preg_match("/decimail/i", $type) ||
                           preg_match("/double/i", $type)
                           ) && ! is_numeric($value)
                         )
                 {
                    // User given value is a NOT number
                    // when we are expecting one!
                    // so we are going to ignore it
                    continue;
                 }
   
                 $resultData[$field] = $value;
             }
          }
       }

       return $resultData;
   }

  /**
  * @purpose Fetches specific field of specific table for defined condition 
  * @param $table = Table Name, $field = Field Name, $where = condition
  * @return $retVal field value
  */  
  function getFieldValue($table, $field, $where)
  {
     $query = "SELECT `$field` FROM $table WHERE $where";
     
     $rs    = mysql_query($query);
     
     $retVal = "";
     
     if(mysql_num_rows($rs))
     {
        $rowVal = mysql_fetch_assoc($rs);
        $retVal = $rowVal[$field];	
     }
     
     return $retVal;
  
  }//EO Fn
?>
