<?php
error_reporting(E_ALL);
include "mysql_functions.php";
include('xml.php');

			  $sql = "SELECT * FROM pre_itemmaster WHERE 1";
			  $data = select($sql); 

			  $data = array_change_key_case($data, CASE_UPPER); 
			  //echo '<pre>'; print_r($data); die;
			  $ITEMS = array();
              $result['ITEMS']=	$data;		  
            
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
	        $output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;

?>