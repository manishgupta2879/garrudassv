<?php
error_reporting(E_ALL);
include "mysql_functions.php";
include('xml.php');

$sql = "SELECT pid as PID,rid as RID,id AS ID,product_name AS NAME,product_group as PRODUCT_GROUP,
product_category as PRODUCT_CATEGORY,uom as UOM,alt_uom as ALTUOM,thirduom_conversion as THIRDUOM,vat_rate as VAT_RATE,cst_tax as CST_TAX,
attribute1 as ATTRIBUTE1,attribute2 as ATTRIBUTE2,attribute3 as ATTRIBUTE3,attribute4 as ATTRIBUTE4,attribute5 as ATTRIBUTE5,
attribute6 as ATTRIBUTE6,attribute7 as ATTRIBUTE7,attribute8 as ATTRIBUTE8,attribute9 as ATTRIBUTE9,attribute10 as ATTRIBUTE10,
sfield1 as SFIELD1,sfield2 as SFIELD2,sfield3 as SFIELD3,sfield4 as SFIELD4,sfield5 as SFIELD5,sfield6 as SFIELD6,
sfield7 as SFIELD7,sfield8 as SFIELD8,sfield9 as SFIELD9,sfield10 as SFIELD10,nfield1 as NFIELD1,nfield2 as NFIELD2,
nfield3 as NFIELD3,nfield4 as NFIELD4,nfield5 as NFIELD5,dfield1 as DFIELD1,dfield2 as DFIELD2,dfield3 as DFIELD3,
dfield4 as DFIELD4,dfield5 as DFIELD5,effdatemrp as EFFDATEMRP, mrp as MRP, eff_date_cost as EFF_DATE_COST, costperunit as COSTPERUNIT, eff_date_price as 
EFF_DATE_PRICE, priceperunit as PRICEPERUNIT, creation_date as CREATION_DATE,altered_on as ALTERED_ON FROM pre_products";
			$data = select($sql);
			//echo '<pre>'; print_r($data);
			 
			  $PRODUCTS = array();
              $result['HEADER']=array('LGRESPONSE'=>'PRODUCTS');
			  $result['DATA']= $data; 


            $result = array_change_key_case($result, CASE_UPPER); 
			header("Content-type: text/xml");
			$xml =Array2XML::createXML('ENVELOPE', $result );
	        $output=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml->saveXML());
			echo $output;

?>
