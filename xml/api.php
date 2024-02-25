<?php
error_reporting(E_ALL);
include "mysql_functions.php";
include('xml.php');

 /*// old method
$xml = simplexml_load_string($_POST['data']);
$json = json_encode($xml);
$array = json_decode($json, TRUE);*/

function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
    $arrData = array();
   
    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }
   
    if (is_array($arrObjData)) { 
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
			
            $arrData[$index] = (is_array($value) && count($value)==0)?'':$value;
        }
    }
    return $arrData;
}
	$dataPOST = trim(file_get_contents('php://input'));
	$xml = new SimpleXMLElement($dataPOST);
	$array = objectsIntoArray($xml);
	
	//for checking data
	/* $xml = new SimpleXMLElement($_POST['data']);
	$array = objectsIntoArray($xml); */
	
	$actions = $array['HEADER']['TALLYREQUEST'];
	$sql = "INSERT into log values('','$actions',now())";
	$res=mysql_query($sql);
if (isset($array['HEADER']['TALLYREQUEST'])) {
    $actions = $array['HEADER']['TALLYREQUEST'];
    switch ($actions) {

        case "REGISTER":
             $BILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
             $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
             $OTP = $array['BODY']['DATA']['SEAPOTP']; 

            // for check password

            $pass = selectone("SELECT * FROM pre_company WHERE pid = '$PRINCIPALID' and otp = '$OTP'");
            $distributor_varification = selectone("SELECT * FROM pre_distributor WHERE id ='$BILLINGCODE'");
            if ($pass && $distributor_varification) {
               $sql = "SELECT c.pid as PRINCIPALID, c.comp_name as PRINCIPALNAME, d.id as SEAPBILLINGCODE, d.name as SEAPDISTNAME, d.phone as SEAPDISTPHONE,c.otp as SEAPOTP, c.password as SEAPPASS, d.address as SEAPDISTADD1,d.address1 as SEAPDISTADD2,
                        d.address2 as SEAPDISTADD3,d.address3 as SEAPDISTADD4,
                d.tinno as SEAPDISTTINNO,d.panno as SEAPDISTPAN,d.creation_date as EFFECTIVEDATE
                   FROM pre_distributor d LEFT JOIN pre_company c on d.company = c.id WHERE d.id = '$BILLINGCODE'";
                $data = selectone($sql); 
				//print_r($data);die;
                //$data['SEAPBILLINGCODE'] = $BILLINGCODE;
                //$data['SEAPPASS'] = $OTP;
                //echo '<pre>'; print_r($data);
				$result['DATA']= $data;
				//header('Content-Disposition: attachment; filename="disti.xml"');
                header("Content-type: text/xml");
                $xml = Array2XML::createXML('ENVELOPE', $result);
                $output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
                echo $output;
            } else {
                echo "Invalid XML";
            }
            break;

        case "Geo Details":
            $val = $array['pre_company_geo_details'];
            insert($tablename = 'pre_company_geo_details', $val);
            break;

        case "IMPORTED VOUCHER":
            $LGBILLINGCODE = $array['DATA']['VALIDATION']['LGBILLINGCODE'];
            $LGOTP = $array['DATA']['VALIDATION']['LGPASS'];
            $pass = selectone("SELECT * FROM pre_company WHERE otp = '$LGOTP'");
            if ($pass) {
                $sql = "SELECT * FROM pre_distributor WHERE id = '$LGBILLINGCODE'";

                $data = selectone($sql);
                if ($data) {
                    $q = "select masterid,vouchertype from pre_voucher";
                    $result['voucher'] = select($q);
                    $vdata = $result['voucher'];
                    header("Content-type: text/xml");
                    $result = array();
                    $VOUCHER = array();
                    $result['HEADER'] = array('LGRESPONSE' => 'IMPORTED VOUCHERS STATUS');
                    $result['DATA'] =
                            array('TALLYMESSAGE' =>
                                array('VOUCHER' => $vdata
                                )
                    );
                    $xml = Array2XML::createXML('ENVELOPE', $result);
                    $output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
                    echo $output;
                }
            } else {
                echo "Invalid Request!";
            }
            break;
        case "IMPORT VOUCHERS":
            $LGBILLINGCODE = $array['DATA']['VALIDATION']['LGBILLINGCODE'];
            $LGOTP = $array['DATA']['VALIDATION']['LGPASS'];
            $val = $array['BODY']['DATA']; //echo '<pre>'; print_r($val);
            insert($tablename = 'pre_voucher', $val);
            break;
        case "DISTRIBUTOR IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            //echo '<pre>'; print_r($val); die;
            insert($tablename = 'pre_distributor', $val);
            break;
        case "TRANSACTION IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            //echo '<pre>'; print_r($val); die;
            insert($tablename = 'pre_transaction', $val);
            break;
        case "PRODUCT IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            //echo '<pre>'; print_r($val); die;
            insert($tablename = 'pre_products', $val);
            break;
        case "CONFIRM":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $ROLLOUTDATE = $array['BODY']['DATA']['ROLLOUTDATE'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select password from pre_company where pid = '$PRINCIPALID'";
			$rows=selectone($q);
			$PASSWORD = $rows['password'];
			date_default_timezone_set('Asia/Kolkata');
			$d=strtotime($ROLLOUTDATE);
			$ROLLOUTDATE = date("Y-m-d", $d);
            update_distributor_status($SEAPBILLINGCODE,$ROLLOUTDATE,$PASSWORD);
				$distributor = "SELECT pid as PRINCIPALID, id as SEAPBILLINGCODE, password as PERMPASS FROM pre_distributor where id ='$SEAPBILLINGCODE'";
				$data = selectone($distributor);
				if($data){
					$result['DATA']= $data;
					//header('Content-Disposition: attachment; filename="permpass.xml"');
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				}
				else{
					echo "Invalid Request!";
				}
            break;
		case "REQMASTER GeoDetails":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$q="select pid as PID, rid as RID, id as ID, name, nature from pre_company_geo_details";
				$rows=select($q);
				$result['HEADER'] = array('TALLYRESPONSE' => 'GEODETAILS');
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 1":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[0]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[0]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 2":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[1]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[1]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 3":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[2]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[2]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 4":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[3]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[3]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 5":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[4]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[4]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 6":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[5]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[5]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 7":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[6]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[6]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 8":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[7]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[7]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 9":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[8]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[8]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER ITEM ATTRIBUTE 10":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='item' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[9]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Item ATTRIBUTE 1','DISPLAYNAME' =>$row_data[9]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 1":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[0]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[0]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 2":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[1]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[1]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 3":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[2]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[2]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 4":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[3]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[3]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 5":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[4]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[4]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 6":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[5]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[5]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 7":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[6]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[6]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 8":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[7]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[7]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 9":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[8]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[8]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER Dealer ATTRIBUTE 10":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$row="select attribute_id, name from pre_attribute where parent_name='distributor' and rid=0 order by attribute_id asc";
				$row_data=select($row);
				//print_r($row_data);die;
				$attr_id = $row_data[9]['attribute_id'];
				$q="select pid as PID,name as NAME,parent as PARENT from pre_attribute where rid='$attr_id'";
				$rows=select($q);
				$result['HEADER'] = array('ATTRIBUTENAME' => 'Dealer ATTRIBUTE 1','DISPLAYNAME' =>$row_data[9]['name']);
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER PRODUCT GROUP":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				
				$q="select pid as PID, rid as RID, id as ID, name as NAME from pre_product_group";
				$rows=select($q);
				$result['HEADER'] = array('TALLYRESPONSE' => 'Product Group');
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER PRODUCT CATEGORY":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				
				$q="select pid as PID, rid as RID, id as ID, name as NAME from pre_product_category";
				$rows=select($q);
				$result['HEADER'] = array('TALLYRESPONSE' => 'Product Category');
				$result['DATA']= $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER PRODUCTS":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$q="select p.pid as PID,p.rid as RID,p.product_name as NAME, g.name as PRODUCT_GROUP, c.name as PRODUCT_CATEGORY, u.symbol as UOM, p.alt_uom as ALTUOM, p.thirduom as THIRDUOM, p.numerator as NUMERATOR, p.denominator as DENOMINATOR, p.conversion as CONVERSION, p.cst_tax as TAXRATE, p.batchwise as BATCHWISE, p.eff_date_cost as COSTAPPDATE, p.costperunit as COSTPRICE, p.eff_date_price as PRICEAPPDATE, p.priceperunit as SALEPRICE, p.effdatemrp as MRPAPPDATE, p.mrp as MRPPRICE, p.attribute1 as ATTRIBUTE1, p.attribute2 as ATTRIBUTE2, p.attribute3 as ATTRIBUTE3, p.attribute4 as ATTRIBUTE4, p.attribute5 as ATTRIBUTE5, p.attribute6 as ATTRIBUTE6, p.attribute7 as ATTRIBUTE7, p.attribute8 as ATTRIBUTE8, p.attribute9 as ATTRIBUTE9, p.attribute10 as ATTRIBUTE10, p.sfield1 as SFIELD1, p.sfield2 as SFIELD2, p.sfield3 as SFIELD3, p.sfield4 as SFIELD4, p.sfield5 as SFIELD5, p.sfield6 as SFIELD6, p.sfield7 as SFIELD7, p.sfield8 as SFIELD8, p.sfield9 as SFIELD9, p.sfield10 as SFIELD10, p.nfield1 as NFIELD1, p.nfield2 as NFIELD2, p.nfield3 as NFIELD3, p.nfield4 as NFIELD4, p.nfield5 as NFIELD5, p.dfield1 as DFIELD1, p.dfield2 as DFIELD2, p.dfield3 as DFIELD3, p.dfield4 as DFIELD4, p.dfield5 as DFIELD5, p.creation_date as CREATION_DATE, p.altered_on as ALTERED_ON  FROM pre_products p LEFT JOIN pre_product_category c on p.product_category = c.id LEFT JOIN pre_product_group g on p.product_group = g.id LEFT JOIN pre_uom u on p.uom = u.id";
				//print_r($q);die;
				$rows=select($q);
				foreach($rows as $row){
					$data = array();
					$data = $row;
					unset($data['COSTAPPDATE']);
					unset($data['COSTPRICE']);
					unset($data['PRICEAPPDATE']);
					unset($data['SALEPRICE']);
					unset($data['MRPAPPDATE']);
					unset($data['MRPPRICE']);
					$data['COSTLIST'] = array('COSTAPPDATE'=>$row['COSTAPPDATE'], 'COSTPRICE'=>$row['COSTPRICE']);
					$data['PRICELIST'] = array('PRICEAPPDATE'=>$row['PRICEAPPDATE'], 'SALEPRICE'=>$row['SALEPRICE']);
					$data['MRPLIST'] = array('MRPAPPDATE'=>$row['MRPAPPDATE'], 'MRPPRICE'=>$row['MRPPRICE']);
					$datas[]= $data;
				}
				//echo '<pre>';print_r($datas);die;
				$result['HEADER'] = array('NAME' => 'PRODUCTS');
				$result['DATA']= $datas;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			}
			else
			{
				echo "Invalid Request!";
			}
		break;
		case "DISTRIBUTOR MASTER":
            $val = $array['DATA'];
			$data = $val;
			unset($data['DIS_ID']);
			unset($data['CREATIONDATE']);
			unset($data['ALTEREDON']);
			unset($data['ROLLOUTDATE']);
			unset($data['LASTSYNCDATE']);
			unset($data['DATAFROMDATE']);
			unset($data['LASTTRANSACTIONDATE']);
			unset($data['ROLLBACKDATE']);
			unset($data['ROLLBACKEXECUTEDON']);
			$data['CREATION_DATE'] = $val['CREATIONDATE'];
			$data['ALTERED_ON'] = $val['ALTEREDON'];
			$data['ROLL_OUT_DATE'] = $val['ROLLOUTDATE'];
			$data['LAST_SYNC_DATE'] = $val['LASTSYNCDATE'];
			$data['DATA_FROM_DATE'] = $val['DATAFROMDATE'];
			$data['LAST_TRANSACTION_DATE'] = $val['LASTTRANSACTIONDATE'];
			$data['ROLL_BACK_DATE'] = $val['ROLLBACKDATE'];
			$data['ROLL_BACK_EXECUTED_ON'] = $val['ROLLBACKEXECUTEDON'];
			insert($tablename = 'pre_distributor', $data);
			echo "DISTRIBUTOR IMPORTED SUCCESSFULLY";
			
		break;
		case "permpass":
            $SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $data = select_permpass($SEAPBILLINGCODE);
			if ($data) {
				$result['DATA'] = $data;
                    header("Content-type: text/xml");
                    $xml = Array2XML::createXML('ENVELOPE', $result);
                    $output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
                    echo $output;
            } else {
                echo "Invalid Request!";
            }
            break;
        case "Category":
		case "Segment":
			$attType = $array['HEADER']['TALLYREQUEST'];
            $data = select_attributes($attType);
		    $result['HEADER'] = array('DISPLAYNAME' => $attType);
			if ($data) {
				$result['DATA'] = $data;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
            } else {
                echo "Invalid Request!";
            }
            break;
		case "REQMASTER UOM":
			$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$qs = "select pid as PID, nature as TYPE, symbol as SYMBOL, name as FORMALNAME, decimals as DECIMALS, first_unit as FIRSTUNIT, conversion as CONVERSION, second_unit as SECONDUNIT from pre_uom";
				//print_r($qs);die;
				$rows = select($qs);
				$result['HEADER'] = array('TALLYRESPONSE' => 'UOM MASTER');
				$result['DATA'] = $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			} else {
				echo "Invalid Request!";
			}
		break;
		case "REQMASTER SalesHierarchy":
			$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            $PASSWORD = $array['BODY']['DATA']['PASSWORD'];
            $PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
			$q="select * from pre_distributor where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
			$rows=selectone($q);
			if($rows)
			{
				$qs = "select pid as PID, rid as RID, id as ID, name as NAME, nature as NATURE from pre_sales_hierarchy";
				//print_r($qs);die;
				$rows = select($qs);
				$result['HEADER'] = array('TALLYRESPONSE' => 'sales hierarchy');
				$result['DATA'] = $rows;
				header("Content-type: text/xml");
				$xml = Array2XML::createXML('ENVELOPE', $result);
				$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
				echo $output;
			} else {
				echo "Invalid Request!";
			}
		break;
        case "Purchase Request":
            $DISTIBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
            //$LGOTP = $array['DATA']['VALIDATION']['LGPASS'];
			        $vdata = array('VOUCHERNUMBER'=>'ARSINGURAJJ13/8712','VOUCHERDATE'=>'20130828');
                    $vdata1 = array('STOCKITEMNAME'=>'12342','BILLEDQTY'=>'15');
                    $vdata2 = array('LEDGERNAME'=>'Input Vat @ 12.5%','TAXPERCENTAGE'=>'14');

                    $result['HEADER'] = array('TALLYREQUEST' => 'IMPORT VOUCHERS');
                    $result['VALIDATION'] = array('DISTIBILLINGCODE' =>$DISTIBILLINGCODE , 'DISTIPASS'=>'NULL');
                    $result['BODY']['DATA']['REQUESTDATA'] = array('TALLYMESSAGE' => $vdata,'INVENTORYENTRY.LIST' => $vdata1,'LEDGERENTRIES.LIST' => $vdata2);
                    
                    header("Content-type: text/xml");
                    $xml = Array2XML::createXML('ENVELOPE', $result);
                    $output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
                    echo $output;
			break;
			//----
			case "district":
				$attType = $array['HEADER']['TALLYREQUEST'];
				$data = select_attributes($attType);
				$result['HEADER'] = array('DISPLAYNAME' => $attType);
				if ($data) {
					$result['DATA'] = $data;
						header("Content-type: text/xml");
						$xml = Array2XML::createXML('ENVELOPE', $result);
						$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
						echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
				///-----
        default:
            echo "Invalid XML Request";
            break;
    }
}




