<?php
## common functions

function __fC($val)
{
   $val = str_replace(array('$', ','), '', $val);
   
   return trim(filterText($val));	
}

function __fTN($text)
{
   $num = preg_replace('/[^0-9\.]*/', '', $text);
   
   return trim($num);	
}

function __NF($n, $d = 2)
{
    return number_format($n, $d, '.', '');	
}

function trim_value(&$v)
{
   $v = trim($v);
   $v = stripslashes($v);	
}

function pv_strip_tags(&$v)
{
   $v = strip_tags(filterText($v));
}

function dBug($arr)
{
   echo '<pre>';
   print_r($arr);
   echo '</pre>';	
}

function reduceSpaces($str)
{
   $str = preg_replace('/\s\s+/', ' ', $str);
   
   return $str;	
}

function _write2file($q, $fn = 'sql2.txt')
{
   $fp = fopen("tmp_files/$fn", 'a+');
   fwrite($fp, $q . "\n");
   fclose($fp);
}

function filterText($text, $rem_list = array())
{
   $text = reduceSpaces($text);
   $pattern = array("\r", "\n", "&nbsp;");
   
   if(sizeof($rem_list))
   {
      $pattern = array_merge($pattern, $rem_list);	
   }
  
   $replacement = array('');
   
   $text = str_ireplace($pattern, $replacement, $text);
   
   return trim($text);	
}

function getRow($query)
{            
   //dBug($query);
   
   $rs = mysql_query($query);
   
   $result = array();
        
   if(mysql_num_rows($rs))
   {      
      $row = mysql_fetch_assoc($rs);
      array_walk_recursive($row, 'filter_db_row_deep');	         
      $result = $row;
      @mysql_free_result($rs);
   }	      
         
   return $result;
	
}//EO Fn

function getRows($query, $limit = 'all')
{      
   $rs = mysql_query($query);
   
   $results = array();
   
   if($limit == 'all')
   {
      $limit = mysql_num_rows($rs);	
   }
  
   if(mysql_num_rows($rs))
   {
      $cnt = 1;
      
      while($limit >= $cnt && ($row = mysql_fetch_assoc($rs)))
      {
         array_walk_recursive($row, 'filter_db_row_deep');	         
         $results[] = $row;                           
         $cnt++;	
      }
      
      @mysql_free_result($rs);   	
   }	      
   
   return $results;
	
}//EO Fn

function getIdxRows($query, $idx = '', $limit = 'all')
{
   $rs = mysql_query($query);

   $results = array();

   if($limit == 'all')
   {
      $limit = mysql_num_rows($rs);
   }

   if(mysql_num_rows($rs))
   {
      $cnt = 1;

      while($limit >= $cnt && ($row = mysql_fetch_assoc($rs)))
      {
         array_walk_recursive($row, 'filter_db_row_deep');
         $results[$row[$idx]] = $row;
         $cnt++;
      }

      @mysql_free_result($rs);
   }

   return $results;
}//EO Fn


function getIdxValxRows($query, $idx = '', $valx = '', $limit = 'all')
{
   $rs = mysql_query($query);

   $results = array();

   if($limit == 'all')
   {
      $limit = mysql_num_rows($rs);
   }

   if(mysql_num_rows($rs))
   {
      $cnt = 1;

      while($limit >= $cnt && ($row = mysql_fetch_assoc($rs)))
      {
         array_walk_recursive($row, 'filter_db_row_deep');
         if ($idx) {
             $results[$row[$idx]] = $row[$valx];
         } else {
             $results[] = $row[$valx];
         }
        
         $cnt++;
      }

      @mysql_free_result($rs);
   }

   return $results;

}//EO Fn

function filter_db_row_deep(&$value, $index)
{
   $value = stripslashes($value);
   $value = trim($value);
}

function filter_value(&$v)
{
   $v = trim($v);	
   $v = addslashes($v);   
}

function trim_value2(&$v)
{
   $v = trim($v, "\r\n\t, ");
   
   //return $v;  
}

function getbetweenTwo($textBefore,$textAfter,$allText,$offset=0)
{
     $pattern='#'.preg_quote($textBefore, '#').'(.*)'.preg_quote($textAfter, '#').'#isU';
                     
     preg_match_all($pattern, $allText,$matches);        
                     
     return @$matches[1][$offset];
}//EO Method

function saveToDatabase($data, $op = 'add', $table)
{      
   if(sizeof($data) < 3) return false;
   
   $info = array();
   $info['table'] = $table;
   $info['data']  = $data;
   $info['debug'] = false;
      
   if($op == 'add')
   {
      $ret = insert($info);	
      
      return $ret['newid'];
   }
   else
   {      
      extract($data);
      $where = $data['where'];
      $info['where'] = $where;
      update($info);		
   } 
   
   return isset($ret['newid']) ? $ret['newid'] : true;
}

## Special CSV Method

function getCsvReport($select, $filename = 'csv_report.csv', $returnContents = 0)
{
   $export = mysql_query ( $select ) or die ( "Sql error : " . mysql_error( ) );
   $header = '';
   $fields = mysql_num_fields ( $export );
   $data = '';   

   for ( $i = 0; $i < $fields; $i++ )
   {
       $field_name = mysql_field_name( $export , $i );
       $header .= ucwords(str_replace('_', ' ', $field_name)) . ", ";
   }

   while( $row = mysql_fetch_assoc( $export ) )
   {              
       $line = '';
       
       foreach( $row as $ind=>$value )
       {                      
           $value = stripslashes($value);
                                
           if( ( !isset( $value ) ) || ( $value == "" ) )
           {               
               $value = ",";
           }
           else
           {
               $value = str_replace( '"' , '""' , $value );
               
               $value = '"' . $value . '"' . ",";               
           }
           
           $line .= $value;
       }
       $data .= trim( $line ) . "\n";
   }
   $data = str_replace( "\r" , "" , $data );

   if ( $data == "" )
   {
       $data = "\n(0) Records Found!\n";
   }

   if ($returnContents) {
       return "$header\n$data";
   }
   	
   ## Download prompt
   
   header("Content-type: application/octet-stream");
   header("Content-Disposition: attachment; filename={$filename}");
   header("Pragma: no-cache");
   header("Expires: 0");
   print "$header\n$data";
   

}//EO Method

?>