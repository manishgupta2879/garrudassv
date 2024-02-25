<?php
error_reporting(E_ALL);
include "mysql_functions.php";
include('xml.php');
$action = $_REQUEST['act'];
	switch ($action) {
		case "districts":
			$sql = "SELECT * FROM pre_district WHERE 1";
			$data = select($sql, CASE_UPPER);

			$result['HEADER']=array('DISPLAYNAME'=>'DISTRICTS');
			$result['DATA']= $data;
			//print_r($result);die;
			//$result = array_change_key_case($result, CASE_UPPER);
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
			$output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;
		break;
		case "products":
			$sql = "SELECT * FROM pre_products WHERE 1";
			$data = select($sql, CASE_UPPER); 
 
			$result['HEADER']=array('DISPLAYNAME'=>'PRODUCTS');
			$result['DATA']= $data;		  
		
			$result = array_change_key_case($result, CASE_UPPER);
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
			$output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;
		break;
		case "distributors":
			$sql = "SELECT * FROM pre_distributor WHERE 1";
			$data = select($sql, CASE_UPPER); 
 
			$result['HEADER']=array('DISPLAYNAME'=>'DISTRIBUTORS');
			$result['DATA']= $data;			  
		
			$result = array_change_key_case($result, CASE_UPPER);
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
			$output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;
		break;
		case "companies":
			$sql = "SELECT * FROM pre_company WHERE 1";
			$data = select($sql, CASE_UPPER); 

			$result['HEADER']=array('DISPLAYNAME'=>'COMPANIES');
			$result['DATA']= $data;		  
			
			$result = array_change_key_case($result,CASE_UPPER); 
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
	        $output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;
		break;
		default:
			echo "Invalid XML Request";
	}
?>