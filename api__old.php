<?php
ini_set('max_execution_time', 0);

include "mysql_functions.php";
include('xml.php');
date_default_timezone_set('Asia/Kolkata');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

			$arrData[$index] = (is_array($value) && count($value) == 0) ? '' : $value;
		}
	}
	return $arrData;
}


try {

	// Batch Queries
	$query_tables = [];
	$query_tables_columns = [];
	$query_tables_values = [];

	// End Batch Queries

	# for sync data from tally
	header('Content-type: text/xml');
	$dataPOST = trim(file_get_contents('php://input'));

	$dataPOST = str_replace('&apos;', '', $dataPOST);
	$dataPOST = str_replace('&amp;', '', $dataPOST);
	$dataPOST = str_replace('&#4;', '', $dataPOST);
	$dataPOST = str_replace('&#13;', '', $dataPOST);
	$dataPOST = str_replace('&#10;', '', $dataPOST);
	$dataPOST = str_replace('&quot;', '', $dataPOST);
	$dataPOST = str_replace('\\', '', $dataPOST);

	$xml = new SimpleXMLElement($dataPOST, LIBXML_PARSEHUGE);
	$array = objectsIntoArray($xml);

	//$encodeData = json_encode($array,true);
	//$sql = "INSERT INTO `tally_request`(`id`, `json`) VALUES ('','$encodeData')";
	//mysqli_query($link, $sql);
	$primaryIds = [];
	$primaryBatchIds = [];
	$primaryBatchNames = [];
	$actions = $array['HEADER']['TALLYREQUEST'];
	$sql = "INSERT into log values('','$actions',now())";
	$res = mysqli_query($link, $sql);
	if (isset($array['HEADER']['TALLYREQUEST'])) {
		$actions = $array['HEADER']['TALLYREQUEST'];
		Logger::log("INFO : Tally Request ON " . date('Y-m-d H:i:s') . " | " . $actions);
		switch ($actions) {

			case "REGISTER":
				$BILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$OTP = $array['BODY']['DATA']['SEAPOTP'];

				// for check password
				$pass = selectone("SELECT * FROM pre_companies WHERE pid = '$PRINCIPALID' and otp = '$OTP'");
				$distributor_varification = selectone("SELECT * FROM pre_distributors WHERE id ='$BILLINGCODE'");
				if ($pass && $distributor_varification) {

					$curdate = date("Y-m-d");
					$selectdistsync = selectone("SELECT * FROM pre_distributor_syncs WHERE distributor_id = '$BILLINGCODE' and sync_date = '$curdate'");

					if ($selectdistsync) {
					} else {
						$sql = "insert into pre_distributor_syncs(distributor_id,sync_date) values ('$BILLINGCODE','$curdate')";
						mysqli_query($link, $sql);
					}
					$sql = "SELECT c.pid as PRINCIPALID, c.comp_name as PRINCIPALNAME, d.id as SEAPBILLINGCODE,d.dist_sort_code as SEAPDISTSCODE, d.name as SEAPDISTNAME, d.phone as SEAPDISTPHONE,c.otp as SEAPPASS, d.address1 as SEAPDISTADD1,d.address2 as SEAPDISTADD2,
                        d.address3 as SEAPDISTADD3,d.address4 as SEAPDISTADD4,d.City as CITY,d.District as DISTRICT,d.State as STATE,d.Country as COUNTRY,d.Zone as ZONE,d.Region as REGION,d.pincode as PINCODE,d.contact_name as CONTACTNAME,d.designation as CONTACTDESIG,d.mobile as CONTACTMOBILE,d.email as CONTACTEMAIL, d.dob as CONTACTDOB,d.doa as CONTACTDOA,d.salespersonname as ALTCONTACTNAME,d.alt_designation as ALTCONTACTDESIG,d.alt_mobile as ALTCONTACTMOBILE,d.alt_email as ALTCONTACTEMAIL,d.alt_dob as ALTCONTACTDOB,d.alt_doa as ALTCONTACTDOA,pl.name as DISTLEVEL,lb.lob_name as DISTTYPE,pls.name as DISTSALEHIER,pu.name as DISTHANDLEDBY,
                d.tinno as SEAPDISTTINNO,d.panno as SEAPDISTPAN,d.cstno as SEAPDISTCSTNO,d.servicetaxno as SEAPDISTSTAXNO,d.cinno as SEAPDISTCINNO,d.eff_date as EFFECTIVEDATE,d.status as STATUS,c.bat_for_ser as BATCHWISEAPP,c.up_rat as UPLOADRATES,c.up_o_s as UPLOADOUTSTANDING,c.Cons_aged as OUTSTANDINGTYPE,c.up_stock as UPLOADSTOCK,c.dail_month as STOCKTYPE,d.creation_date as CREATIONDATE,d.altered_on as ALTEREDON,d.DSR as DSR,d.beat as BEAT,d.roll_out_date as ROLLOUTDATE,d.last_sync_date as LASTSYNCDATE,d.data_from_date as DATAFROMDATE,d.last_transaction_date as LASTTRANSACTIONDATE,d.roll_back_date as ROLLBACKDATE,d.roll_back_executed_on as ROLLBACKEXECUTEDON,d.tally_serial_no as TALLYSERIALNO,d.tally_release as TALLYRELEASE,d.tcp_version as TCPVERSION,d.attribute1 as ATTRIBUTE1,d.attribute2 as ATTRIBUTE2,d.attribute3 as ATTRIBUTE3,d.attribute4 as ATTRIBUTE4,d.attribute5 as ATTRIBUTE5,d.attribute6 as ATTRIBUTE6,d.attribute7 as ATTRIBUTE7,d.attribute8 as ATTRIBUTE8,d.attribute9 as ATTRIBUTE9,d.attribute10 as ATTRIBUTE10,d.sfield1 as SFIELD1,d.sfield2 as SFIELD2,d.sfield3 as SFIELD3,d.sfield4 as SFIELD4,d.sfield5 as SFIELD5,d.sfield6 as SFIELD6,d.sfield7 as SFIELD7,d.sfield8 as SFIELD8,d.sfield9 as SFIELD9,d.sfield10 as SFIELD10,d.sfield10 as SFIELD10,d.nfield1 as NFIELD1,d.nfield2 as NFIELD2,d.nfield3 as NFIELD3,d.nfield4 as NFIELD4,d.nfield5 as NFIELD5,d.dfield1 as DFIELD1,d.dfield2 as DFIELD2,d.dfield3 as DFIELD3,d.dfield4 as DFIELD4,d.dfield5 as DFIELD5
                   FROM pre_distributors d LEFT JOIN pre_companies c on d.pid = c.com_id LEFT JOIN pre_levels pl on d.type = pl.level_id LEFT JOIN pre_lobs lb on d.distributor_type = lb.id LEFT JOIN pre_levels pls on d.saleshierarchy = pls.level_id LEFT JOIN pre_users pu on d.handle_by = pu.id WHERE d.id = '$BILLINGCODE' AND d.pid ='$PRINCIPALID'";
					$data = selectone($sql);

					if ($data['EFFECTIVEDATE'] != '0000-00-00') {
						$data['EFFECTIVEDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['EFFECTIVEDATE'])));
					} else {
						$data['EFFECTIVEDATE'] = '';
					} //
					if ($data['CONTACTDOB'] != '0000-00-00') {
						$data['CONTACTDOB'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CONTACTDOB'])));
					} else {
						$data['CONTACTDOB'] = '';
					} //
					if ($data['CONTACTDOA'] != '0000-00-00') {
						$data['CONTACTDOA'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CONTACTDOA'])));
					} else {
						$data['CONTACTDOA'] = '';
					} //
					if ($data['ALTCONTACTDOB'] != '0000-00-00') {
						$data['ALTCONTACTDOB'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTCONTACTDOB'])));
					} else {
						$data['ALTCONTACTDOB'] = '';
					} //
					if ($data['ALTCONTACTDOA'] != '0000-00-00') {
						$data['ALTCONTACTDOA'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTCONTACTDOA'])));
					} else {
						$data['ALTCONTACTDOA'] = '';
					} //
					if ($data['CREATIONDATE'] != '0000-00-00') {
						$data['CREATIONDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CREATIONDATE'])));
					} else {
						$data['CREATIONDATE'] = '';
					} //
					if ($data['ALTEREDON'] != '0000-00-00') {
						$data['ALTEREDON'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTEREDON'])));
					} else {
						$data['ALTEREDON'] = '';
					} //
					if ($data['ROLLOUTDATE'] && $data['ROLLOUTDATE'] != '0000-00-00 00:00:00') {
						$data['ROLLOUTDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['ROLLOUTDATE'])));
					} else {
						$data['ROLLOUTDATE'] = '';
					}
					if ($data['LASTSYNCDATE'] && $data['LASTSYNCDATE'] != '0000-00-00 00:00:00') {
						$data['LASTSYNCDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['LASTSYNCDATE'])));
					} else {
						$data['LASTSYNCDATE'] = '';
					} //
					if ($data['DATAFROMDATE'] != '0000-00-00') {
						$data['DATAFROMDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DATAFROMDATE'])));
					} else {
						$data['DATAFROMDATE'] = '';
					} //
					if ($data['LASTTRANSACTIONDATE'] != '0000-00-00') {
						$data['LASTTRANSACTIONDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['LASTTRANSACTIONDATE'])));
					} else {
						$data['LASTTRANSACTIONDATE'] = '';
					} //
					if ($data['ROLLBACKEXECUTEDON'] != '0000-00-00') {
						$data['ROLLBACKEXECUTEDON'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ROLLBACKEXECUTEDON'])));
					} else {
						$data['ROLLBACKEXECUTEDON'] = '';
					} //
					if ($data['ROLLBACKDATE'] && $data['ROLLBACKDATE'] != '0000-00-00') {
						$data['ROLLBACKDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['ROLLBACKDATE'])));
					} else {
						$data['ROLLBACKDATE'] = '';
					} //
					if ($data['DFIELD1'] != '0000-00-00') {
						$data['DFIELD1'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD1'])));
					} else {
						$data['DFIELD1'] = '';
					} //
					if ($data['DFIELD2'] != '0000-00-00') {
						$data['DFIELD2'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD2'])));
					} else {
						$data['DFIELD2'] = '';
					} //
					if ($data['DFIELD3'] != '0000-00-00') {
						$data['DFIELD3'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD3'])));
					} else {
						$data['DFIELD3'] = '';
					} //
					if ($data['DFIELD4'] != '0000-00-00') {
						$data['DFIELD4'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD4'])));
					} else {
						$data['DFIELD4'] = '';
					} //
					if ($data['DFIELD5'] != '0000-00-00') {
						$data['DFIELD5'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD5'])));
					} else {
						$data['DFIELD5'] = '';
					} //
					$sql = "SELECT id,name FROM pre_companygeodetails";
					$geos = select($sql);
					foreach ($geos as $geo) {
						$geoss[$geo['id']] = $geo['name'];
					}
					if ($data['CITY']) {
						$data['CITY'] = $geoss[$data['CITY']];
					} else {
						$data['CITY'] = '';
					}
					if ($data['DISTRICT']) {
						$data['DISTRICT'] = $geoss[$data['DISTRICT']];
					} else {
						$data['DISTRICT'] = '';
					}
					if ($data['STATE']) {
						$data['STATE'] = $geoss[$data['STATE']];
					} else {
						$data['STATE'] = '';
					}
					if ($data['COUNTRY']) {
						$data['COUNTRY'] = $geoss[$data['COUNTRY']];
					} else {
						$data['COUNTRY'] = '';
					}
					if ($data['ZONE']) {
						$data['ZONE'] = $geoss[$data['ZONE']];
					} else {
						$data['ZONE'] = '';
					}
					if ($data['REGION']) {
						$data['REGION'] = $geoss[$data['REGION']];
					} else {
						$data['REGION'] = '';
					}



					if ($data['BATCHWISEAPP'] == 1 || $data['BATCHWISEAPP'] == 'YES') {
						$data['BATCHWISEAPP'] = 'YES';
					} else {
						$data['BATCHWISEAPP'] = 'NO';
					}
					if ($data['UPLOADRATES'] == 1 || $data['UPLOADRATES'] == 'YES') {
						$data['UPLOADRATES'] = 'YES';
					} else {
						$data['UPLOADRATES'] = 'NO';
					}
					if ($data['UPLOADOUTSTANDING'] == 1 || $data['UPLOADOUTSTANDING'] == 'YES') {
						$data['UPLOADOUTSTANDING'] = 'YES';
					} else {
						$data['UPLOADOUTSTANDING'] = 'NO';
					}
					if ($data['UPLOADSTOCK'] == 1 || $data['UPLOADSTOCK'] == 'YES') {
						$data['UPLOADSTOCK'] = 'YES';
					} else {
						$data['UPLOADSTOCK'] = 'NO';
					}

					$result['DATA'] = $data;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid XML";
				}
				break;

			case "REQMASTER DISTRIBUTOR":
				$BILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$OTP = $array['BODY']['DATA']['PASSWORD'];

				// for check password

				$pass = selectone("SELECT * FROM pre_companies WHERE pid = '$PRINCIPALID'");

				$distributor_varification = selectone("SELECT * FROM pre_distributors WHERE id ='$BILLINGCODE' AND password='$OTP'");

				if ($pass && $distributor_varification) {


					$curdate = date("Y-m-d");
					$selectdistsync = selectone("SELECT * FROM pre_distributor_syncs WHERE distributor_id = '$BILLINGCODE' and sync_date = '$curdate'");

					if ($selectdistsync) {
					} else {
						$sql = "insert into pre_distributor_syncs(distributor_id,sync_date) values ('$BILLINGCODE','$curdate')";
						mysqli_query($link, $sql);
					}


					$sql = "SELECT c.pid as PRINCIPALID, c.comp_name as PRINCIPALNAME, d.id as SEAPBILLINGCODE,d.dist_sort_code as SEAPDISTSCODE, d.name as SEAPDISTNAME, d.phone as SEAPDISTPHONE,d.password as SEAPPASS, d.address1 as SEAPDISTADD1,d.address2 as SEAPDISTADD2,
                        d.address3 as SEAPDISTADD3,d.address4 as SEAPDISTADD4,d.City as CITY,d.District as DISTRICT,d.State as STATE,d.Country as COUNTRY,d.Zone as ZONE,d.Region as REGION,d.pincode as PINCODE,d.contact_name as CONTACTNAME,d.designation as CONTACTDESIG,d.mobile as CONTACTMOBILE,d.email as CONTACTEMAIL, d.dob as CONTACTDOB,d.doa as CONTACTDOA,d.salespersonname as ALTCONTACTNAME,d.alt_designation as ALTCONTACTDESIG,d.alt_mobile as ALTCONTACTMOBILE,d.alt_email as ALTCONTACTEMAIL,d.alt_dob as ALTCONTACTDOB,d.alt_doa as ALTCONTACTDOA,pl.name as DISTLEVEL,lb.lob_name as DISTTYPE,pls.name as DISTSALEHIER,pu.name as DISTHANDLEDBY,
                d.tinno as SEAPDISTTINNO,d.panno as SEAPDISTPAN,d.cstno as SEAPDISTCSTNO,d.servicetaxno as SEAPDISTSTAXNO,d.cinno as SEAPDISTCINNO,d.eff_date as EFFECTIVEDATE,d.status as STATUS,c.bat_for_ser as BATCHWISEAPP,c.up_rat as UPLOADRATES,c.up_o_s as UPLOADOUTSTANDING,c.Cons_aged as OUTSTANDINGTYPE,c.up_stock as UPLOADSTOCK,c.dail_month as STOCKTYPE,d.creation_date as CREATIONDATE,d.altered_on as ALTEREDON,d.DSR as DSR,d.beat as BEAT,d.roll_out_date as ROLLOUTDATE,d.last_sync_date as LASTSYNCDATE,d.data_from_date as DATAFROMDATE,d.last_transaction_date as LASTTRANSACTIONDATE,d.roll_back_date as ROLLBACKDATE,d.roll_back_executed_on as ROLLBACKEXECUTEDON,d.tally_serial_no as TALLYSERIALNO,d.tally_release as TALLYRELEASE,d.tcp_version as TCPVERSION,d.attribute1 as ATTRIBUTE1,d.attribute2 as ATTRIBUTE2,d.attribute3 as ATTRIBUTE3,d.attribute4 as ATTRIBUTE4,d.attribute5 as ATTRIBUTE5,d.attribute6 as ATTRIBUTE6,d.attribute7 as ATTRIBUTE7,d.attribute8 as ATTRIBUTE8,d.attribute9 as ATTRIBUTE9,d.attribute10 as ATTRIBUTE10,d.sfield1 as SFIELD1,d.sfield2 as SFIELD2,d.sfield3 as SFIELD3,d.sfield4 as SFIELD4,d.sfield5 as SFIELD5,d.sfield6 as SFIELD6,d.sfield7 as SFIELD7,d.sfield8 as SFIELD8,d.sfield9 as SFIELD9,d.sfield10 as SFIELD10,d.sfield10 as SFIELD10,d.nfield1 as NFIELD1,d.nfield2 as NFIELD2,d.nfield3 as NFIELD3,d.nfield4 as NFIELD4,d.nfield5 as NFIELD5,d.dfield1 as DFIELD1,d.dfield2 as DFIELD2,d.dfield3 as DFIELD3,d.dfield4 as DFIELD4,d.dfield5 as DFIELD5,d.last_trans_master_id as LASTTRANSMASTERID,d.last_trans_alter_id as LASTTRANSALTERID,d.last_stock_date as LASTSTOCKDATE,d.last_os_date as LASTOSDATE,d.initiate_rollback as INITIATEROLLBACK,
				d.initiate_rollback_by as INITIATEROLLBACKBY,
				d.rollback_type as ROLLBACKTYPE,
				d.rollback_from_date as ROLLBACKFROMDATE,
				d.tally_version as TALLYVERSION,d.tss_expiry as TSSEXPIRY,
				d.re_rollout_date as REROLLOUTDATE,
				d.rollback_initiated_on as ROLLBACKINITIATEDON
                   FROM pre_distributors d LEFT JOIN pre_companies c on d.pid = c.com_id LEFT JOIN pre_levels pl on d.type = pl.level_id LEFT JOIN pre_lobs lb on d.distributor_type = lb.id LEFT JOIN pre_levels pls on d.saleshierarchy = pls.level_id LEFT JOIN pre_users pu on d.handle_by = pu.id WHERE d.id = '$BILLINGCODE' AND d.pid ='$PRINCIPALID'";
					$data = selectone($sql);


					if ($data['EFFECTIVEDATE'] != '0000-00-00') {
						$data['EFFECTIVEDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['EFFECTIVEDATE'])));
					} else {
						$data['EFFECTIVEDATE'] = '';
					} //
					if ($data['CONTACTDOB'] != '0000-00-00') {
						$data['CONTACTDOB'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CONTACTDOB'])));
					} else {
						$data['CONTACTDOB'] = '';
					} //
					if ($data['CONTACTDOA'] != '0000-00-00') {
						$data['CONTACTDOA'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CONTACTDOA'])));
					} else {
						$data['CONTACTDOA'] = '';
					} //
					if ($data['ALTCONTACTDOB'] != '0000-00-00') {
						$data['ALTCONTACTDOB'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTCONTACTDOB'])));
					} else {
						$data['ALTCONTACTDOB'] = '';
					} //
					if ($data['ALTCONTACTDOA'] != '0000-00-00') {
						$data['ALTCONTACTDOA'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTCONTACTDOA'])));
					} else {
						$data['ALTCONTACTDOA'] = '';
					} //
					if ($data['CREATIONDATE'] != '0000-00-00') {
						$data['CREATIONDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CREATIONDATE'])));
					} else {
						$data['CREATIONDATE'] = '';
					} //
					if ($data['ALTEREDON'] != '0000-00-00') {
						$data['ALTEREDON'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTEREDON'])));
					} else {
						$data['ALTEREDON'] = '';
					} //
					if ($data['ROLLOUTDATE'] && $data['ROLLOUTDATE'] != '0000-00-00 00:00:00') {
						$data['ROLLOUTDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['ROLLOUTDATE'])));
					} else {
						$data['ROLLOUTDATE'] = '';
					} //
					if ($data['LASTSYNCDATE'] && $data['LASTSYNCDATE'] != '0000-00-00 00:00:00') {
						$data['LASTSYNCDATE'] = str_replace('-', '/', date("d-m-Y H:i:s", strtotime($data['LASTSYNCDATE'])));
					} else {
						$data['LASTSYNCDATE'] = '';
					} //
					if ($data['DATAFROMDATE'] != '0000-00-00') {
						$data['DATAFROMDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DATAFROMDATE'])));
					} else {
						$data['DATAFROMDATE'] = '';
					} //
					if ($data['LASTTRANSACTIONDATE'] != '0000-00-00') {
						$data['LASTTRANSACTIONDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['LASTTRANSACTIONDATE'])));
					} else {
						$data['LASTTRANSACTIONDATE'] = '';
					} //
					if ($data['ROLLBACKEXECUTEDON'] != '0000-00-00') {
						$data['ROLLBACKEXECUTEDON'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ROLLBACKEXECUTEDON'])));
					} else {
						$data['ROLLBACKEXECUTEDON'] = '';
					} //
					if ($data['DFIELD1'] != '0000-00-00') {
						$data['DFIELD1'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD1'])));
					} else {
						$data['DFIELD1'] = '';
					} //
					if ($data['DFIELD2'] != '0000-00-00') {
						$data['DFIELD2'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD2'])));
					} else {
						$data['DFIELD2'] = '';
					} //
					if ($data['DFIELD3'] != '0000-00-00') {
						$data['DFIELD3'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD3'])));
					} else {
						$data['DFIELD3'] = '';
					} //
					if ($data['DFIELD4'] != '0000-00-00') {
						$data['DFIELD4'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD4'])));
					} else {
						$data['DFIELD4'] = '';
					} //
					if ($data['DFIELD5'] != '0000-00-00') {
						$data['DFIELD5'] = str_replace('-', '/', date("d-m-Y", strtotime($data['DFIELD5'])));
					} else {
						$data['DFIELD5'] = '';
					} //
					if ($data['LASTSTOCKDATE'] != '0000-00-00 00:00:00') {
						$data['LASTSTOCKDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['LASTSTOCKDATE'])));
					} else {
						$data['LASTSTOCKDATE'] = '';
					} //

					if ($data['LASTOSDATE'] != '0000-00-00') {
						$data['LASTOSDATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['LASTOSDATE'])));
					} else {
						$data['LASTOSDATE'] = '';
					} //
					$data['ROLLBACKFROMDATE'] = $data['ROLLBACKFROMDATE'] && $data['ROLLBACKFROMDATE'] != '0000-00-00 00:00:00' && strtotime($data['ROLLBACKFROMDATE']) ? date('d-M-Y', strtotime($data['ROLLBACKFROMDATE'])) : '';


					$sql = "SELECT * FROM `pre_companies` WHERE pid = com_id limit 1";
					$primaryCompany = selectone($sql);
					if ($primaryCompany) {
						$data['SYNCMASTERONREGISTRATION'] = $primaryCompany['sync_master_on_registration'];
						$data['SYNC_GEO'] = $primaryCompany['sync_geo'];
						$data['SYNC_HIERARCHY'] = $primaryCompany['sync_hierarchy'];
						$data['SYNC_ATTRIBUTE'] = $primaryCompany['sync_attribute'];
						$data['SYNC_LOB'] = $primaryCompany['sync_lob'];
						$data['SYNC_PRODUCT'] = $primaryCompany['sync_product'];
						$data['SYNC_PRICELIST'] = $primaryCompany['sync_price_list'];
						$data['SYNC_MESSAGE_BOARD'] = $primaryCompany['sync_message_board'];
					}
					foreach ($geos as $geo) {
						$geoss[$geo['id']] = $geo['name'];
					}
					if ($data['CITY']) {
						$data['CITY'] = $geoss[$data['CITY']];
					} else {
						$data['CITY'] = '';
					}
					if ($data['DISTRICT']) {
						$data['DISTRICT'] = $geoss[$data['DISTRICT']];
					} else {
						$data['DISTRICT'] = '';
					}
					if ($data['STATE']) {
						$data['STATE'] = $geoss[$data['STATE']];
					} else {
						$data['STATE'] = '';
					}
					if ($data['COUNTRY']) {
						$data['COUNTRY'] = $geoss[$data['COUNTRY']];
					} else {
						$data['COUNTRY'] = '';
					}
					if ($data['ZONE']) {
						$data['ZONE'] = $geoss[$data['ZONE']];
					} else {
						$data['ZONE'] = '';
					}
					if ($data['REGION']) {
						$data['REGION'] = $geoss[$data['REGION']];
					} else {
						$data['REGION'] = '';
					}

					if ($data['BATCHWISEAPP'] == 1 || $data['BATCHWISEAPP'] == 'YES') {
						$data['BATCHWISEAPP'] = 'YES';
					} else {
						$data['BATCHWISEAPP'] = 'NO';
					}
					if ($data['UPLOADRATES'] == 1 || $data['UPLOADRATES'] == 'YES') {
						$data['UPLOADRATES'] = 'YES';
					} else {
						$data['UPLOADRATES'] = 'NO';
					}
					if ($data['UPLOADOUTSTANDING'] == 1 || $data['UPLOADOUTSTANDING'] == 'YES') {
						$data['UPLOADOUTSTANDING'] = 'YES';
					} else {
						$data['UPLOADOUTSTANDING'] = 'NO';
					}
					if ($data['UPLOADSTOCK'] == 1 || $data['UPLOADSTOCK'] == 'YES') {
						$data['UPLOADSTOCK'] = 'YES';
					} else {
						$data['UPLOADSTOCK'] = 'NO';
					}

					$result['DATA'] = $data;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid XML";
				}
				break;
				/* case "Geo Details":
            $val = $array['pre_companygeodetails'];
            insert($tablename = 'pre_companygeodetails', $val);
            break; */

			case "REQVOUCHER PURCHASE":
				$BILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$OTP = $array['BODY']['DATA']['PASSWORD'];
				// for check password
				$q1 = "SELECT * FROM pre_distributors WHERE id ='$BILLINGCODE' and password='$OTP' and pid='$PRINCIPALID'";
				$distributor_varification = selectone($q1);
				$BILLINGCODE1 = $distributor_varification['sfield1'];
				$BILLINGCODE2 = $distributor_varification['sfield2'];
				$BILLINGCODE3 = $distributor_varification['sfield3'];
				$BILLINGCODE4 = $distributor_varification['sfield4'];
				$BILLINGCODE5 = $distributor_varification['sfield5'];

				if ($distributor_varification) {
					//RAHUL CHANGES
					$q = "select vchno,vchdate,partyname,amount,vouchertype from pre_vouchers where (dist_id='$BILLINGCODE'				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' ) AND posted='' GROUP BY vchno";
					//print_r($q);die;
					$rows = select($q);
					foreach ($rows as $row) {
						$master_id = $row['vchno'];

						$matching_amount = $row['amount'];

						//$match_query = "select masterid, alterid from pre_transactions where disti_code='$BILLINGCODE' AND vchno='$master_id' AND (posted=0 OR posted='') AND batch_qty =''";

						$match_query = "SELECT SUM(salesamount) as sum1 FROM pre_vouchers WHERE  (dist_id='$BILLINGCODE'				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' ) AND vchno = '$master_id' AND posted = '' AND batch_qty = ''";



						$match_result = select($match_query);

						$match_amount = $match_result[0]['sum1'];
						if (bccomp($matching_amount, $match_amount, 2)) {

							$q = "update pre_vouchers set posted='Totaling Error' where  (dist_id='$BILLINGCODE'
				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' ) AND vchno='$master_id'";
							mysqli_query($link, $q);
							//continue;
						}

						$q = "select pv.voucher_id ,pv.item_name,pp.product_name as STOCKITEMNAME, pp.pid as PID, pp.rid as RID, pp.id as ID, pp.description as DESCRIPTION, pp.part_number as PARTNO, pp.product_group as PRODUCTGROUP, pp.product_category as PRODUCTCATEGORY, pp.uom as UOM,uom.name as UOMFORMALNAME, pp.alt_uom as ALTUOM, pp.thirduom as THIRDUOM, pp.numerator as NUMERATOR, pp.denominator as DENOMINATOR, pp.conversion as CONVERSION, pp.vat_rate as TAXRATE, pp.cst_tax as CSTTAX, pp.batchwise as BATCHWISE, pp.attribute1 as ATTRIBUTE1, pp.attribute2 as ATTRIBUTE2, pp.attribute3 as ATTRIBUTE3, pp.attribute4 as ATTRIBUTE4, pp.attribute5 as ATTRIBUTE5, pp.attribute6 as ATTRIBUTE6, pp.attribute7 as ATTRIBUTE7, pp.attribute8 as ATTRIBUTE8, pp.attribute9 as ATTRIBUTE9, pp.attribute10 as ATTRIBUTE10, pp.sfield1 as SFIELD1, pp.sfield2 as SFIELD2, pp.sfield3 as SFIELD3, pp.sfield4 as SFIELD4, pp.sfield5 as SFIELD5, pp.sfield6 as SFIELD6, pp.sfield7 as SFIELD7, pp.sfield8 as SFIELD8, pp.sfield9 as SFIELD9, pp.sfield10 as SFIELD10, pp.nfield1 as NFIELD1, pp.nfield2 as NFIELD2, pp.nfield3 as NFIELD3, pp.nfield4 as NFIELD4, pp.nfield5 as NFIELD5, pp.dfield1 as DFIELD1, pp.dfield2 as DFIELD2, pp.dfield3 as DFIELD3, pp.dfield4 as DFIELD4, pp.dfield5 as DFIELD5, pp.creation_date as CREATION_DATE, pp.altered_on as ALTERED_ON, pv.item_qty as BILLEDQTY, pv.alt_quantity as ALTQTY, pv.rate as RATE,pv.salesledgername as PURCHASELEDGERNAME, pv.salestaxprecentage as TAXPERCENTAGE, pv.salesamount as AMOUNT, pv.batch as BATCHSLRNO, pv.batch_qty as BATCHQTY, pv.batch_alt_qty as BATCHALTQTY, pv.alternatestkqty as ALTERNATEBATCHQTY, pv.alternatestkaltqty as ALTERNATEBATCHALTQTY, pv.tailstkqty as TAILBATCHQTY, pv.tailstkaltqty as TAILBATCHALTQTY, pv.batch_mfg_date as BATCHMFGDATE, pv.batch_exp_date as BATCHEXPIRYDATE, pv.user_description as USERDESC 
					from pre_vouchers as pv
					LEFT JOIN pre_product_management as pp ON pv.item_name = pp.id OR pv.item_name = pp.sfield6 OR pv.item_name = pp.sfield7 OR pv.item_name = pp.sfield8 OR pv.item_name = pp.sfield9
					LEFT JOIN pre_uoms uom on pp.uom = uom.id
					where pv.item_name !='' AND pv.vchno='$master_id' 
					AND  (pv.dist_id='$BILLINGCODE' or pv.dist_id='$BILLINGCODE1' or pv.dist_id='$BILLINGCODE2' 
					or pv.dist_id='$BILLINGCODE3' or pv.dist_id='$BILLINGCODE4' or pv.dist_id='$BILLINGCODE5' ) 
					AND pv.item_qty !='' AND (pv.posted ='' OR pv.posted ='0') ORDER BY pv.voucher_id ASC";
						$inventory = select($q);

						$q = "select salesledgername as LEDGERNAME,salestaxprecentage as TAXPERCENTAGE ,salesamount as AMOUNT from pre_vouchers where (item_name IS NULL OR item_name='') AND vchno='$master_id' AND  (dist_id='$BILLINGCODE'
				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' )";
						$ledger = select($q);

						$row['VOUCHERNUMBER'] = $row['vchno'];
						unset($row['vchno']);
						$originalDate = $row['vchdate'];
						$newDate = date("d-m-Y", strtotime(str_replace('/', '-', $originalDate)));
						if ($row['vchdate']) {
							$row['VOUCHERDATE'] = str_replace('-', '/', $newDate);
						} else {
							$row['VOUCHERDATE'] = '';
						}
						//$row['VOUCHERDATE'] = $row['vchdate'];
						unset($row['vchdate']);
						$com_id = $row['partyname'];
						$com_name = selectone("SELECT comp_name FROM pre_companies WHERE com_id = '$com_id'");
						$row['PARTYNAME'] = $com_name['comp_name'] . '-' . $row['partyname'];
						unset($row['partyname']);
						$row['AMOUNT'] = $row['amount'];
						unset($row['amount']);
						/* $row['MASTERID'] = $row['masterid'];product_category
                    unset($row['masterid']);
                    $row['ALTERID'] = $row['alterid'];
                    unset($row['alterid']); */
						$row['VOUCHERTYPE'] = $row['vouchertype'];
						unset($row['vouchertype']);
						foreach ($inventory as $invent) {
							$invent['CREATION_DATE'] = date('d/m/Y', strtotime($invent['CREATION_DATE']));
							$invent['ALTERED_ON'] = date('d/m/Y', strtotime($invent['ALTERED_ON']));

							// get related field values start - 25-3-22
							$group = selectone("SELECT name FROM pre_product_groups WHERE id='" . $invent['PRODUCTGROUP'] . "'");
							$invent['PRODUCTGROUP'] = $group['name'];

							$category = selectone("SELECT name FROM pre_product_categories WHERE id='" . $invent['PRODUCTCATEGORY'] . "'");
							$invent['PRODUCTCATEGORY'] = $category['name'];

							$uom = selectone("SELECT symbol FROM pre_uoms WHERE id='" . $invent['UOM'] . "'");
							$invent['UOM'] = $uom['symbol'];

							$altuom = selectone("SELECT symbol FROM pre_uoms WHERE id='" . $invent['ALTUOM'] . "' AND nature='Simple'");
							$invent['ALTUOM'] = $altuom['symbol'];

							$denominator = selectone("SELECT symbol FROM pre_uoms WHERE id='" . $invent['DENOMINATOR'] . "' AND nature='Simple'");
							$invent['ALTUOM'] = $denominator['symbol'];

							$numerator = selectone("SELECT symbol FROM pre_uoms WHERE id='" . $invent['NUMERATOR'] . "' AND nature='Simple'");
							$invent['ALTUOM'] = $numerator['symbol'];

							// get related field values end


							//--
							$i_name = $invent['item_name'];
							$v_id = $invent['voucher_id'];
							$v_qty = $invent['BILLEDQTY'];
							$v_qty_lt = floor($v_qty);
							$q = "select batch_rate as RATE,batch_value as AMOUNT, batch as BATCHSLRNO, batch_qty as BATCHQTY, batch_alt_qty as BATCHALTQTY,alternatestkqty as ALTERNATEBATCHQTY, alternatestkaltqty as ALTERNATEBATCHALTQTY, tailstkqty as TAILBATCHQTY,tailstkaltqty as TAILBATCHALTQTY, batch_mfg_date as BATCHMFGDATE, batch_exp_date as BATCHEXPIRYDATE, user_description as USERDESC from pre_vouchers where item_name ='$i_name' AND batch !='' AND vchno='$master_id' AND  (dist_id='$BILLINGCODE'
				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' ) AND (posted ='' OR posted ='0') AND voucher_id >$v_id ORDER BY voucher_id ASC LIMIT $v_qty_lt";
							$inventorybatch = select($q);

							if ($inventorybatch) {
								foreach ($inventorybatch as $invbatch) {
									//--
									if ($invbatch) {
										if ($invbatch['BATCHMFGDATE']) {
											$bmfgdate = str_replace('-', '/', date("d-m-Y", strtotime(str_replace('/', '-', $invbatch['BATCHMFGDATE']))));
										} else {
											$bmfgdate = '';
										}
										if ($invbatch['BATCHEXPIRYDATE']) {
											$bexpdate = str_replace('-', '/', date("d-m-Y", strtotime(str_replace('/', '-', $invbatch['BATCHEXPIRYDATE']))));
										} else {
											$bexpdate = '';
										}

										$invent['USERDESCRIPTION'][] = array('USERDESC' => $invbatch['USERDESC']);
										$invent['BATCHALLOCATIONS'][] = array('BATCHSLRNO' => $invbatch['BATCHSLRNO'], 'BATCHMFGDATE' => $bmfgdate, 'BATCHEXPIRYDATE' => $bexpdate, 'BATCHQTY' => $invbatch['BATCHQTY'], 'BATCHALTQTY' => $invbatch['BATCHALTQTY'], 'ALTERNATEBATCHQTY' => $invbatch['ALTERNATEBATCHQTY'], 'ALTERNATEBATCHALTQTY' => $invbatch['ALTERNATEBATCHALTQTY'], 'TAILBATCHQTY' => $invbatch['TAILBATCHQTY'], 'TAILBATCHALTQTY' => $invbatch['TAILBATCHALTQTY'], 'BATCHRATE' => $invbatch['RATE'], 'BATCHVALUE' => $invbatch['AMOUNT']);
									} else {
										$invent['USERDESCRIPTION'][] = array('USERDESC' => '');
										$invent['BATCHALLOCATIONS'][] = array('BATCHSLRNO' => '', 'BATCHMFGDATE' => '', 'BATCHEXPIRYDATE' => '', 'BATCHQTY' => '', 'BATCHALTQTY' => '', 'ALTERNATEBATCHQTY' => '', 'ALTERNATEBATCHALTQTY' => '', 'TAILBATCHQTY' => '', 'TAILBATCHALTQTY' => '', 'BATCHRATE' => '', 'BATCHVALUE' => '');
									}
								}
							} else {
								$invent['USERDESCRIPTION'][] = array('USERDESC' => '');
								$invent['BATCHALLOCATIONS'][] = array('BATCHSLRNO' => '', 'BATCHMFGDATE' => '', 'BATCHEXPIRYDATE' => '', 'BATCHQTY' => '', 'BATCHALTQTY' => '', 'ALTERNATEBATCHQTY' => '', 'ALTERNATEBATCHALTQTY' => '', 'TAILBATCHQTY' => '', 'TAILBATCHALTQTY' => '', 'BATCHRATE' => '', 'BATCHVALUE' => '');
							}
							unset($invent['USERDESC']);
							unset($invent['BATCHSLRNO']);
							unset($invent['BATCHMFGDATE']);
							unset($invent['BATCHEXPIRYDATE']);
							unset($invent['BATCHQTY']);
							unset($invent['BATCHALTQTY']);
							unset($invent['ALTERNATEBATCHQTY']);
							unset($invent['ALTERNATEBATCHALTQTY']);
							unset($invent['TAILBATCHQTY']);
							unset($invent['TAILBATCHALTQTY']);
							unset($invent['item_name']);
							unset($invent['voucher_id']);
							/* unset($invent['BATCHRATE']);
                        unset($invent['BATCHVALUE']); */
							$inventory1[] = $invent;
							unset($invent);
						}

						//unset($invent);
						unset($row['INVENTORYENTRIES']);
						unset($row['LEDGERENTRIES']);
						$row['INVENTORYENTRIES'] = $inventory1;

						unset($inventory1);


						$row['LEDGERENTRIES'] = $ledger;

						$final_data[] = $row;
					}



					//-------
					/* $sql = "UPDATE pre_distributors set last_sync_date = NOW() WHERE id = '$BILLINGCODE'"; 
                mysqli_query($link, $sql); */
					//-------
					$result['HEADER'] = array('TALLYRESPONSE' => 'PURCHASE VOUCHER');
					$result['BODY']['DATA']['VALIDATION'] = array('PRINCIPALID' => $PRINCIPALID, 'SEAPBILLINGCODE' => $BILLINGCODE, 'PASSWORD' => $OTP);
					$result['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'] = $final_data;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "ACKNOWLEDGE PURCHASE":
				$BILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$PRINCIPALID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$OTP = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				// for check password
				$distributor_varification = selectone("SELECT * FROM pre_distributors WHERE id ='$BILLINGCODE' and password='$OTP' and pid='$PRINCIPALID'");


				$BILLINGCODE1 = $distributor_varification['sfield1'];
				$BILLINGCODE2 = $distributor_varification['sfield2'];
				$BILLINGCODE3 = $distributor_varification['sfield3'];
				$BILLINGCODE4 = $distributor_varification['sfield4'];
				$BILLINGCODE5 = $distributor_varification['sfield5'];


				if ($distributor_varification) {
					if (!isset($array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'][0])) {
						$valuss[] = $array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'];
					} else {
						$valuss = $array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'];
					}
					foreach ($valuss as $valus) {
						$master_id = $valus['SERVERMASTERID'];
						$date = $valus['TALLYIMPORTDT'];
						$q = "update pre_vouchers set posted='$date', is_posted=1 where (dist_id='$BILLINGCODE'
				
				or dist_id='$BILLINGCODE1' or dist_id='$BILLINGCODE2' or dist_id='$BILLINGCODE3' or dist_id='$BILLINGCODE4' or dist_id='$BILLINGCODE5' ) AND vchno='$master_id' AND posted=''";
						mysqli_query($link, $q);
					}
				} else {
					echo "Invalid Request!";
				}
				break;
			case "ALL VOUCHERS":
				$PID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$LGBILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$LGOTP = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				$sql = "SELECT * FROM pre_distributors WHERE id ='$LGBILLINGCODE' and password='$LGOTP' and pid='$PID'";
				$distributor_varification = selectone($sql);

				if ($distributor_varification) {
					$valus = $array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'];
					if (!isset($array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'][0])) {
						$vals[] = $array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'];
					} else {
						$vals = $array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE'];
					}

					if ($array['BODY']['DATA']['REQUESTDATA']['TALLYMESSAGE']) {
						// Loop Begin 
						$transaction_histories_query = '';
						$pre_transactions_query = '';
						$pre_transactions_query2 = '';
						$pre_transactions_query3 = '';
						$pre_transaction_errors = '';
						$pre_transaction_errors2 = '';
						$pre_transaction_errors3 = '';
						$pre_distributor_syncs_sync_date = "";
						$pre_distributor_syncs_sale_date = "";

						foreach ($vals as $val) {
							$val['PARTYNAME'] = mysqli_real_escape_string($link, htmlspecialchars_decode($val['PARTYNAME']));
							$invent = $val['INVENTORYENTRIES'];
							if (!isset($val['INVENTORYENTRIES'][0])) {
								$inventy[] = $val['INVENTORYENTRIES'];
							} else {
								$inventy = $val['INVENTORYENTRIES'];
							}

							$val['PID'] =  $PID;
							$val['DISTI_CODE'] = $LGBILLINGCODE;
							if ($val['CREATIONDATE']) {
								$val['CREATIONDATE'] = date("Y-m-d", strtotime($val['CREATIONDATE']));
							} else {
								$val['CREATIONDATE'] = date("Y-m-d", strtotime($val['DOCDATE']));
							}
							if ($val['ALTEREDON']) {
								$val['ALTEREDON'] = date("Y-m-d", strtotime($val['ALTEREDON']));
							} else {
								$val['ALTEREDON'] = "0000-00-00";
							}
							if ($val['DOCDATE']) {
								$val['DOCDATE'] = date("Y-m-d", strtotime($val['DOCDATE']));
							} else {
								$val['DOCDATE'] = "0000-00-00";
							}
							if ($val['DFIELD1']) {
								$val['DFIELD1'] = date("Y-m-d", strtotime($val['DFIELD1']));
							} else {
								$val['DFIELD1'] = "0000-00-00";
							}
							if ($val['DFIELD2']) {
								$val['DFIELD2'] = date("Y-m-d", strtotime($val['DFIELD2']));
							} else {
								$val['DFIELD2'] = "0000-00-00";
							}
							if ($val['DFIELD3']) {
								$val['DFIELD3'] = date("Y-m-d", strtotime($val['DFIELD3']));
							} else {
								$val['DFIELD3'] = "0000-00-00";
							}
							if ($val['DFIELD4']) {
								$val['DFIELD4'] = date("Y-m-d", strtotime($val['DFIELD4']));
							} else {
								$val['DFIELD4'] = "0000-00-00";
							}
							if ($val['DFIELD5']) {
								$val['DFIELD5'] = date("Y-m-d", strtotime($val['DFIELD5']));
							} else {
								$val['DFIELD5'] = "0000-00-00";
							}
							$val['sync_date'] = date('Y-m-d');
							unset($val['INVENTORYENTRIES']);
							$type = str_replace(' ', '_', strtolower($val['TRNTYPE']));
							if ($type == 'purchase' || $type == 'purchase reversed' || $type == 'purchase removed') {
								$year = date('Y', strtotime($val['DFIELD1']));
								$month = date('m', strtotime($val['DFIELD1']));
							} else {
								$year = date('Y', strtotime($val['DOCDATE']));
								$month = date('m', strtotime($val['DOCDATE']));
							}
							$year = date('Y', strtotime($val['DOCDATE']));
							$month = date('m', strtotime($val['DOCDATE']));

							//==============================History Transaction Code Start======================================

							if (!in_array($val['TRNTYPE'], array('Sales Removed', 'Credit Note Removed', 'Purchase Removed', 'Debit Note Removed', 'Stock Journal Removed'))) {
								$distCode = $LGBILLINGCODE;
								$masterId = $val['MASTERID'];
								$trnType = $val['TRNTYPE'];

								$q = "SELECT * FROM pre_transactions WHERE pid = '$PID'  AND disti_code = '$distCode' AND masterid = '$masterId' AND trntype LIKE '%$trnType%'";

								$rows = select($q);
								foreach ($rows as $row) {
									$rowID = $row['id'];
									if ($row['trntype'] == 'purchase' || $row['trntype'] == 'purchase reversed' || $row['trntype'] == 'purchase removed') {
										$rowYear = date('Y', strtotime($row['dfield1']));
										$rowMonth = date('m', strtotime($row['dfield1']));
									} else {
										$rowYear = date('Y', strtotime($row['docdate']));
										$rowMonth = date('m', strtotime($row['docdate']));
									}
									$rowYear = date('Y', strtotime($row['docdate']));
									$rowMonth = date('m', strtotime($row['docdate']));
									$rowTrnType = clearTransType($row['trntype']);

									$rowStockNo = $row['stockno'];
									$deletedQty = $row['stkqty'];
									if ($deletedQty) {

										$q = "select * from pre_stockreports where pid = '$PID' AND  distid = '$LGBILLINGCODE' AND itemcode='$rowStockNo' AND YEAR(`fromdate`)='$rowYear' AND MONTH(`fromdate`)='$rowMonth' and itemgodownname='' and itembatchname='' ORDER BY id ASC LIMIT 1";
										$stockReports = selectone($q);
										if ($rowTrnType == 'sales_gst') {
											$rowTrnType = 'sales';
										} elseif ($rowTrnType == 'Purchase (CIS)') {
											$rowTrnType = 'purchase';
										}
										if ($stockReports) {
											$stkId = $stockReports['id'];
											if ($deletedQty) {
												$sql = "UPDATE pre_stockreports set $rowTrnType = ($rowTrnType - ($deletedQty)) WHERE id = $stkId";

												mysqli_query($link, $sql);
												if ($stkId && $stkId != '') {
													updateClosingStock($link, $stkId);
												}
											}
										}
									}
									$keys = array_keys($row);
									unset($keys[0]);
									unset($row['id']);
									$columns = implode(", ", $keys);
									$values = "'" . implode("', '", $row) . "'";
									$transaction_histories_query .= " ($values), ";
									//$sqlInsert = "INSERT INTO pre_transaction_histories($columns) VALUES ($values)";		BATCH QUERY							
									//$res = mysqli_query($link, $sqlInsert);
									//
									$sqlInsert = "UPDATE pre_transactions SET stkqty='0',stkaltqty='0',`bw_posted`='0',stkrate='0',stkvalue='0',amount='0' ,is_deleted = '1' where id = '$rowID' AND godownname = ''";
									$res = mysqli_query($link, $sqlInsert);

									$sqlInsert = "UPDATE pre_transactions SET batchslrno='0',batchmfgdate='0000-00-00', batchexpirydate='0000-00-00',batchqty='0',batchaltqty='0',alternatebatchqty='0',alternatebatchaltqty='0',
									tailbatchqty='0',tailbatchaltqty='0',batchrate='0', is_deleted = '1',batchvalue='0',`bw_posted`='0',amount='0' where id = '$rowID' AND godownname != '' ";
									$res = mysqli_query($link, $sqlInsert);
								}
							}

							//END History
							//==============================END History

							foreach ($inventy as $data) {
								$itemname = $data['STOCKNO'];
								$q = "select * from pre_product_management where '$itemname' IN (`id`, `sfield6`, `sfield7`, `sfield8`, `sfield9`)";
								$products = selectone($q);
								if ($products) {
									$code = $products['id'];
									# check entry in stock table start
									$q = "select * from pre_stockreports where distid = '$LGBILLINGCODE' AND itemcode='$code' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' and itemgodownname='' and itembatchname='' ORDER BY id ASC LIMIT 1";
									$stock = selectone($q);

									if ($stock) {
										$entqty = $data['STKQTY'];
										$type = clearTransType($val['TRNTYPE']);
										//$type = str_replace(' ','_',strtolower($val['TRNTYPE']));
										$stkId = $stock['id'];
										if ($entqty) {
											$sql = "UPDATE pre_stockreports set $type = ($type + ($entqty)) WHERE id = $stkId";
											mysqli_query($link, $sql);
										}
										updateClosingStock($link, $stkId);
									} else {
										$type = clearTransType($val['TRNTYPE']);
										//$type = str_replace(' ','_',strtolower($val['TRNTYPE']));
										$stockEntry = array(
											'pid'       => $PID,
											'rid'       => $distributor_varification['rid'],
											'distid'    => $LGBILLINGCODE,
											'fromdate'  => date('Y-m-01', strtotime($val['DOCDATE'])),
											'todate'    => date('Y-m-t', strtotime($val['DOCDATE'])),
											'itemcode'  => $code,
											'itemname'  => $products['product_name'],
											'openqty'   => 0,
											$type     => $data['STKQTY']
										);
										$stockId =  insert($tablename = 'pre_stockreports', $stockEntry);
										if ($stockId && $stockId != '') {
											updateClosingStock($link, $stockId);
										}
									}

									# END

									$val['ENTSLRNO'] = $data['ENTSLRNO'];
									$val['STOCKNO'] = $code;
									$val['STOCKNAME'] = $products['product_name'];
									$val['STKQTY'] = $data['STKQTY'];
									$val['STKALTQTY'] = $data['STKALTQTY'];
									$val['STKRATE'] = $data['STKRATE'];
									$val['STKVALUE'] = $data['STKVALUE'];
									$val['DISCOUNTRATE'] = $data['DISCOUNTRATE'];
									$val['DISCOUNTAMOUNT'] = $data['DISCOUNTAMOUNT'];
									$val['FOC'] = $data['FOC'];
									//$val['USERDESC'] = $data['USERDESCRIPTION']['USERDESC'];

									$val_keys = array_keys($val);
									$columns = strtolower(implode(", ", $val_keys));
									$values = "'" . implode("', '", $val) . "'";
									//print_r($columns);die;
									//insert($tablename = 'pre_transactions', $val); BATCH QUERY									
									$pre_transactions_query .= " ($values), ";
									$curdate = date("Y-m-d");

									$d_code = $val['DISTI_CODE'];

									$sale_date = $val['DOCDATE'];

									$ttype = $val['TRNTYPE'];

									$selectdistsync = selectone("SELECT id FROM pre_distributor_syncs WHERE distributor_id = '$d_code' and sync_date = '$curdate'");
									if (!$selectdistsync) {
										//$sql = "INSERT INTO pre_distributor_syncs(distributor_id,sync_date) values ('$d_code','$curdate')"; BATCH QUERY
										//mysqli_query($link, $sql);
										$pre_distributor_syncs_sync_date .= "('$d_code','$curdate'), ";
									}

									if (strtolower($ttype) == 'sales') {

										$selectdistsync1 = selectone("SELECT id FROM pre_distributor_syncs WHERE distributor_id = '$d_code' and sync_date = '$sale_date'");
										if ($selectdistsync1) {
											$sql = "UPDATE pre_distributor_syncs set sale_date = '$sale_date' WHERE distributor_id='$d_code' AND date(sync_date)= '$sale_date'";
											mysqli_query($link, $sql);
										} else {

											$pre_distributor_syncs_sale_date .= "('$d_code','$sale_date'), ";
											//$sql = "INSERT INTO pre_distributor_syncs(distributor_id,sale_date) values ('$d_code','$sale_date')"; BATCH QUERY
											//mysqli_query($link, $sql);
										}
									}


									//// code on 17april23 end



									//$batch_count = count($data['BATCHALLOCATIONS']);
									//if($batch_count >=14){
									if (!isset($data['BATCHALLOCATIONS'][0])) {
										$batch[] = $data['BATCHALLOCATIONS'];
									} else {
										$batch = $data['BATCHALLOCATIONS'];
									}

									foreach ($batch as $bath) {
										unset($val['STKQTY']);
										unset($val['STKALTQTY']);
										unset($val['STKRATE']);
										unset($val['STKVALUE']);
										$val['ORDERNO'] = $bath['ORDERNO'];
										$val['TRACKINGNO'] = $bath['TRACKINGNO'];
										$val['GODOWNNAME'] = $bath['GODOWNNAME'];
										$val['BATCHSLRNO'] = $bath['BATCHSLRNO'];
										$val['BATCHMFGDATE'] = ($bath['BATCHMFGDATE']) ? $bath['BATCHMFGDATE'] : "0000-00-00";
										$val['BATCHEXPIRYDATE'] = ($bath['BATCHEXPIRYDATE']) ? $bath['BATCHEXPIRYDATE'] : "0000-00-00";
										$val['BATCHQTY'] = $bath['BATCHQTY'];
										$val['BATCHALTQTY'] = $bath['BATCHALTQTY'];
										$val['BATCHRATE'] = $bath['BATCHRATE'];
										$val['BATCHVALUE'] = $bath['BATCHVALUE'];
										$val['ALTERNATEBATCHQTY'] = $bath['ALTERNATEBATCHQTY'];
										$val['ALTERNATEBATCHALTQTY'] = $bath['ALTERNATEBATCHALTQTY'];
										$val['TAILBATCHQTY'] = $bath['TAILBATCHQTY'];
										$val['TAILBATCHALTQTY'] = $bath['TAILBATCHALTQTY'];

										$val_keys = array_keys($val);
										$columns = strtolower(implode(", ", $val_keys));
										$values = "'" . implode("', '", $val) . "'";
										$pre_transactions_query2 .= " ($values), ";
										// insert($tablename = 'pre_transactions', $val); BATCH QUERY
									}

									//$val['USERDESC'] = $data['USERDESCRIPTION']['USERDESC'];

									//$user_count = count($data['USERDESCRIPTION']);

									//if($user_count ==1){
									if (!isset($data['USERDESCRIPTION'][0])) {
										$userdesc[] = $data['USERDESCRIPTION'];
									} else {
										$userdesc = $data['USERDESCRIPTION'];
									}

									foreach ($userdesc as $desc) {
										$val['USERDESC'] = $desc['USERDESC'];
										unset($val['ORDERNO']);
										unset($val['TRACKINGNO']);
										unset($val['GODOWNNAME']);
										unset($val['BATCHSLRNO']);
										unset($val['BATCHMFGDATE']);
										unset($val['BATCHEXPIRYDATE']);
										unset($val['BATCHQTY']);
										unset($val['BATCHALTQTY']);
										unset($val['BATCHRATE']);
										unset($val['BATCHVALUE']);
										unset($val['STKQTY']);
										unset($val['STKALTQTY']);
										unset($val['STKRATE']);
										unset($val['STKVALUE']);
										unset($val['DISCOUNTRATE']);
										unset($val['DISCOUNTAMOUNT']);
										unset($val['ALTERNATEBATCHQTY']);
										unset($val['ALTERNATEBATCHALTQTY']);
										unset($val['TAILBATCHQTY']);
										unset($val['TAILBATCHALTQTY']);
										if ($val['USERDESC']) {
											$val_keys = array_keys($val);
											$columns = strtolower(implode(", ", $val_keys));
											$values = "'" . implode("', '", $val) . "'";
											$pre_transactions_query3 .= " ($values), ";
											// insert($tablename = 'pre_transactions', $val); BATCH QUERY

										}
										unset($userdesc);
										unset($val['USERDESC']);
									}
									unset($batch);
									unset($data);
								} else {
									// Start errors of transactions and stock reports
									$code = $data['STOCKNO'];
									// $q = "select * from pre_stockreport_errors where distid = '$LGBILLINGCODE' AND itemcode='$code' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' and itemgodownname='' and itembatchname='' ORDER BY id ASC LIMIT 1";
									// $stock = selectone($q);
									// if (!$stock) {
									// 	$type = str_replace(' ', '_', strtolower($val['TRNTYPE']));
									// 	$stockEntry = array(
									// 		'pid'       => $PID,
									// 		'rid'       => $distributor_varification['rid'],
									// 		'distid'    => $LGBILLINGCODE,
									// 		'fromdate'  => date('Y-m-01', strtotime($val['DOCDATE'])),
									// 		'todate'    => date('Y-m-t', strtotime($val['DOCDATE'])),
									// 		'itemcode'  => $code,
									// 		'itemname'  => $products['product_name'],
									// 		'openqty'   => 0,
									// 		$type     => $data['STKQTY']
									// 	);
									// 	$stockId =  insert($tablename = 'pre_stockreport_errors', $stockEntry);
									// }
									$PID = $val['PID'];
									$distCode = $val['DISTI_CODE'];
									$trnType = $val['TRNTYPE'];
									$masterId = $val['MASTERID'];
									$q = "SELECT id FROM pre_transaction_errors WHERE pid = '$PID'  AND disti_code = '$distCode' AND masterid = '$masterId' AND trntype = '$trnType' AND STOCKNO = '$code' LIMIT 1";
									$hasTransaction = selectone($q);
									if (!$hasTransaction) {
										$val['ENTSLRNO'] = $data['ENTSLRNO'];
										$val['STOCKNO'] = $code;
										$val['STOCKNAME'] = $data['STOCKNAME'];
										$val['STKQTY'] = $data['STKQTY'];
										$val['STKALTQTY'] = $data['STKALTQTY'];
										$val['STKRATE'] = $data['STKRATE'];
										$val['STKVALUE'] = $data['STKVALUE'];
										$val['DISCOUNTRATE'] = $data['DISCOUNTRATE'];
										$val['DISCOUNTAMOUNT'] = $data['DISCOUNTAMOUNT'];
										$val['FOC'] = $data['FOC'];

										$val_keys = array_keys($val);
										$columns = strtolower(implode(", ", $val_keys));
										$values = "'" . implode("', '", $val) . "'";
										$pre_transaction_errors .= " ($values), ";
										//insert($tablename = 'pre_transaction_errors', $val); BATCH QUERY

										if (!isset($data['BATCHALLOCATIONS'][0])) {
											$batch[] = $data['BATCHALLOCATIONS'];
										} else {
											$batch = $data['BATCHALLOCATIONS'];
										}

										foreach ($batch as $bath) {
											unset($val['STKQTY']);
											unset($val['STKALTQTY']);
											unset($val['STKRATE']);
											unset($val['STKVALUE']);
											$val['ORDERNO'] = $bath['ORDERNO'];
											$val['TRACKINGNO'] = $bath['TRACKINGNO'];
											$val['GODOWNNAME'] = $bath['GODOWNNAME'];
											$val['BATCHSLRNO'] = $bath['BATCHSLRNO'];
											$val['BATCHMFGDATE'] = ($bath['BATCHMFGDATE']) ? $bath['BATCHMFGDATE'] : "0000-00-00";
											$val['BATCHEXPIRYDATE'] = ($bath['BATCHEXPIRYDATE']) ? $bath['BATCHEXPIRYDATE'] : "0000-00-00";
											$val['BATCHQTY'] = $bath['BATCHQTY'];
											$val['BATCHALTQTY'] = $bath['BATCHALTQTY'];
											$val['BATCHRATE'] = $bath['BATCHRATE'];
											$val['BATCHVALUE'] = $bath['BATCHVALUE'];
											$val['ALTERNATEBATCHQTY'] = $bath['ALTERNATEBATCHQTY'];
											$val['ALTERNATEBATCHALTQTY'] = $bath['ALTERNATEBATCHALTQTY'];
											$val['TAILBATCHQTY'] = $bath['TAILBATCHQTY'];
											$val['TAILBATCHALTQTY'] = $bath['TAILBATCHALTQTY'];

											$val_keys = array_keys($val);
											$columns = strtolower(implode(", ", $val_keys));
											$values = "'" . implode("', '", $val) . "'";
											$pre_transaction_errors2 .= " ($values), ";
											// insert($tablename = 'pre_transaction_errors', $val); BATCH QUERY
										}
										if (!isset($data['USERDESCRIPTION'][0])) {
											$userdesc[] = $data['USERDESCRIPTION'];
										} else {
											$userdesc = $data['USERDESCRIPTION'];
										}
										foreach ($userdesc as $desc) {
											$val['USERDESC'] = $desc['USERDESC'];
											unset($val['ORDERNO']);
											unset($val['TRACKINGNO']);
											unset($val['GODOWNNAME']);
											unset($val['BATCHSLRNO']);
											unset($val['BATCHMFGDATE']);
											unset($val['BATCHEXPIRYDATE']);
											unset($val['BATCHQTY']);
											unset($val['BATCHALTQTY']);
											unset($val['BATCHRATE']);
											unset($val['BATCHVALUE']);
											unset($val['STKQTY']);
											unset($val['STKALTQTY']);
											unset($val['STKRATE']);
											unset($val['STKVALUE']);
											unset($val['DISCOUNTRATE']);
											unset($val['DISCOUNTAMOUNT']);
											unset($val['ALTERNATEBATCHQTY']);
											unset($val['ALTERNATEBATCHALTQTY']);
											unset($val['TAILBATCHQTY']);
											unset($val['TAILBATCHALTQTY']);
											if ($val['USERDESC']) {

												$val_keys = array_keys($val);
												$columns = strtolower(implode(", ", $val_keys));
												$values = "'" . implode("', '", $val) . "'";
												$pre_transaction_errors3 .= " ($values), ";

												// insert($tablename = 'pre_transaction_errors', $val); BATCH QUERY
											}
											unset($userdesc);
											unset($val['USERDESC']);
										}
										unset($batch);
										unset($data);
									}





									// End errors of transactions and stock reports
								}
							}
							unset($inventy);
							$ackn['TALLYMASTERID'] = $val['MASTERID'];
							if ($val['MASTERID']) {
								$ackn['SYNCED'] = 'YES';
							} else {
								$ackn['SYNCED'] = 'NO';
							}
							$ackn['SERVERIMPORTDT'] = str_replace('-', '/', date("d-m-Y"));
							$ack['TALLYMESSAGE'][] = $ackn;
							//-for update in distributor table.
							$m_id = $val['MASTERID'];
							$al_id = $val['ALTERID'];
							$sql = "UPDATE pre_distributors set last_trans_master_id = $m_id WHERE id = '$LGBILLINGCODE' AND last_trans_master_id < $m_id";
							mysqli_query($link, $sql);
							$sql = "UPDATE pre_distributors set last_trans_alter_id = $al_id WHERE id = '$LGBILLINGCODE' AND last_trans_alter_id < $al_id";
							mysqli_query($link, $sql);
							//-end of for update in distributor table.
							$invoice_creation_date = $val['CREATIONDATE'];
							$invoice_alteration_date = $val['ALTEREDON'];
							$invoice_trasaction_date = $val['DOCDATE'];
						}
						$transaction_histories_query = rtrim($transaction_histories_query, ', ');
						$pre_transactions_query = rtrim($pre_transactions_query, ', ');
						$pre_transactions_query2 = rtrim($pre_transactions_query2, ', ');
						$pre_transactions_query3 = rtrim($pre_transactions_query3, ', ');
						$pre_distributor_syncs_sync_date = rtrim($pre_distributor_syncs_sync_date, ', ');
						$pre_distributor_syncs_sale_date = rtrim($pre_distributor_syncs_sale_date, ', ');
						$pre_transaction_errors = rtrim($pre_transaction_errors, ', ');
						$pre_transaction_errors2 = rtrim($pre_transaction_errors2, ', ');
						$pre_transaction_errors3 = rtrim($pre_transaction_errors3, ', ');
						if ($transaction_histories_query) {
							//Logger::log("transaction_histories_query");
							$res = mysqli_query($link, "INSERT INTO pre_transaction_histories(pid, disti_code, trntype, docno, docdate, doctime, entslrno, locationid, stockno, stockname, orderno, trackingno, godownname, batchslrno, batchmfgdate, batchexpirydate, batchqty, batchaltqty, alternatebatchqty, alternatebatchaltqty, tailbatchqty, tailbatchaltqty, batchrate, batchvalue, userdesc, salesman, partycode, partyname, stkqty, stkaltqty, alternatestkqty, alternatestkaltqty, tailstkqty, tailstkaltqty, stkrate, stkvalue, discountrate, discountamount, amtbeforetax, taxrate, taxamt, amount, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, foc, masterid, alterid, creationdate, alteredon, sync_date, bw_posted, created_at, stkqty_new, is_deleted) values " . $transaction_histories_query);
						}
						if ($pre_transactions_query) {
							//Logger::log("pre_transactions_query");
							$res = mysqli_query($link, "INSERT INTO pre_transactions(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, stkqty, stkaltqty, stkrate, stkvalue, discountrate, discountamount, foc)  values " . $pre_transactions_query);
						}

						if ($pre_transactions_query2) {
							//Logger::log("pre_transactions_query2");
							$res = mysqli_query($link, "INSERT INTO pre_transactions(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, discountrate, discountamount, foc, orderno, trackingno, godownname, batchslrno, batchmfgdate, batchexpirydate, batchqty, batchaltqty, batchrate, batchvalue, alternatebatchqty, alternatebatchaltqty, tailbatchqty, tailbatchaltqty) values " . $pre_transactions_query2);
						}
						if ($pre_transactions_query3) {
							//Logger::log("pre_transactions_query3");
							$res = mysqli_query($link, "INSERT INTO pre_transactions(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, foc, userdesc) values " . $pre_transactions_query3);
						}
						if ($pre_distributor_syncs_sync_date) {
							//Logger::log("pre_distributor_syncs_sync_date");
							$res = mysqli_query($link, "INSERT INTO pre_distributor_syncs(distributor_id,sync_date) values " . $pre_distributor_syncs_sync_date);
						}
						if ($pre_distributor_syncs_sale_date) {
							//Logger::log("pre_distributor_syncs_sale_date");
							$res = mysqli_query($link, "INSERT INTO pre_distributor_syncs(distributor_id,sale_date) values " . $pre_distributor_syncs_sale_date);
						}
						if ($pre_transaction_errors) {
							//Logger::log("pre_transaction_errors");
							$res = mysqli_query($link, "INSERT INTO pre_transaction_errors(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, stkqty, stkaltqty, stkrate, stkvalue, discountrate, discountamount, foc) values " . $pre_transaction_errors);
						}
						if ($pre_transaction_errors2) {
							Logger::log("pre_transaction_errors2");
							$res = mysqli_query($link, "INSERT INTO pre_transaction_errors(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, discountrate, discountamount, foc, orderno, trackingno, godownname, batchslrno, batchmfgdate, batchexpirydate, batchqty, batchaltqty, batchrate, batchvalue, alternatebatchqty, alternatebatchaltqty, tailbatchqty, tailbatchaltqty) values " . $pre_transaction_errors2);
						}
						if ($pre_transaction_errors3) {
							Logger::log("pre_transaction_errors3");
							$res = mysqli_query($link, "INSERT INTO pre_transaction_errors(docno, docdate, doctime, trntype, partycode, partyname, amtbeforetax, taxrate, taxamt, amount, locationid, salesman, billtype, sfield1, sfield2, sfield3, sfield4, sfield5, sfield6, sfield7, sfield8, sfield9, sfield10, nfield1, nfield2, nfield3, nfield4, nfield5, dfield1, dfield2, dfield3, dfield4, dfield5, masterid, alterid, creationdate, alteredon, pid, disti_code, sync_date, entslrno, stockno, stockname, foc, userdesc) values " . $pre_transaction_errors3);
						}


						$sql = "UPDATE pre_distributors set invoice_creation_date = '$invoice_creation_date', invoice_trasaction_date='$invoice_trasaction_date', invoice_alteration_date='$invoice_alteration_date' WHERE id = '$LGBILLINGCODE' AND last_trans_alter_id <= $al_id";
						mysqli_query($link, $sql);
					}

					// Logger::log("INFO : Tally Request END ON " . date('Y-m-d H:i:s') . " | " . $actions);
					// Logger::log("=============================================================");

					//--working
					$result['HEADER'] = array('TALLYRESPONSE' => 'ACKNOWLEDGE TRANSACTION');
					$result['BODY']['DATA']['VALIDATION'] = array('PRINCIPALID' => $PID, 'SEAPBILLINGCODE' => $LGBILLINGCODE, 'PASSWORD' => $LGOTP);
					$result['BODY']['DATA']['REQUESTDATA'] = @$ack;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
					//-- working
					//echo "Success!";

				} else {
					echo "Invalid Password!";
				}
				break;
				/* case "DISTRIBUTOR IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            insert($tablename = 'pre_distributors', $val);
            break;
        case "TRANSACTION IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            insert($tablename = 'pre_transactions', $val);
            break;
        case "PRODUCT IMPORT":
            $val = $array['DATA'];
            $val = array_change_key_case($val, CASE_LOWER);
            insert($tablename = 'pre_product_management', $val);
            break; */
			case "CONFIRM":

				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$ROLLOUTDATE = $array['BODY']['DATA']['ROLLOUTDATE'];

				$TALLYSERIALNO = $array['BODY']['DATA']['TALLYSERIALNO'];
				$TALLYRELEASE = $array['BODY']['DATA']['TALLYRELEASE'];
				$TCPVERSION = $array['BODY']['DATA']['TCPVERSION'];
				$TSSEXPIRY = $array['BODY']['DATA']['TSSEXPIRY'];

				$TALLYVERSION = $array['BODY']['DATA']['TALLYVERSION'];

				//END
				$q = "select otp from pre_companies where pid = '$PRINCIPALID'";
				$rows = selectone($q);
				$PASSWORD = substr("ABCDEFGHIJKLMNOPQRSTUVWXYZ", mt_rand(0, 23), 4) . substr("!@#$%&*", mt_rand(0, 4), 1) . substr(time(), -4);
				date_default_timezone_set('Asia/Kolkata');
				$d = strtotime($ROLLOUTDATE);
				$ROLLOUTDATE = date("Y-m-d H:i:s", $d);
				$pass = "SELECT pid as PRINCIPALID, password as PERMPASS FROM pre_distributors where id ='$SEAPBILLINGCODE'";
				$passw = selectone($pass);
				//if($passw['PERMPASS'] ==''){
				update_distributor_status($SEAPBILLINGCODE, $ROLLOUTDATE, $PASSWORD, $TALLYSERIALNO, $TALLYRELEASE, $TCPVERSION, $TSSEXPIRY, $TALLYVERSION);
				//}
				$distributor = "SELECT pid as PRINCIPALID, id as SEAPBILLINGCODE, password as PERMPASS FROM pre_distributors where id ='$SEAPBILLINGCODE'";
				$data = selectone($distributor);
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
			case "REQMASTER GeoDetails":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$q = "select pgo.pid as PID, rpgo.name as RID, pgo.id as ID, pgo.name, pgo.nature from pre_companygeodetails pgo LEFT JOIN pre_companygeodetails rpgo on pgo.rid = rpgo.id";
					$rows = select($q);

					$result['HEADER'] = array('TALLYRESPONSE' => 'GEODETAILS');
					$result['DATA'] = $rows;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER HIERARCHY MANAGEMENT":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$row = "select pl.pid as PID, apl.name as RID, pl.level_id as ID, pl.name as NAME, pl.type as TYPE, pl.level as LEVEL  from pre_levels pl LEFT JOIN pre_levels apl on pl.rel_id = apl.level_id order by pl.level_id asc";
					$row_data = select($row);
					$result['HEADER'] = array('TALLYRESPONSE' => 'HIERARCHY MANAGEMENT');
					$result['DATA'] = $row_data;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER ATTRIBUTE MASTER":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$row = "select pa.pid as PID, pl.name as RID, pa.attribute_id as ID, pa.name as NAME, pa.parent_name as PARENTNAME, pa.parent as PARENT, rpl.name as RELATTRIBUTE, rav.name as RELATTRIBUTEVALUE from pre_attributes pa LEFT JOIN pre_levels pl on pa.rid = pl.level_id LEFT JOIN pre_levels rpl on pa.related_parent_attribute = rpl.level_id LEFT JOIN pre_attributes rav on pa.related_attribute_value_id = rav.attribute_id order by pa.attribute_id asc";
					$row_data = select($row);
					$result['HEADER'] = array('TALLYRESPONSE' => 'ATTRIBUTE MASTER');
					$result['DATA'] = $row_data;
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER PRODUCT GROUP":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				$category_id = $rows['lob'];
				$category_id = "'" . $category_id . "'";
				// $last_sync_date = date("Y-m-d", strtotime($rows['last_sync_date']));
				$last_sync_date = $rows['last_sync_date'];
				$category_id = str_replace(',', "','", $category_id);
				if ($rows) {
					$primaryCompany = selectone("SELECT * FROM pre_companies WHERE pid = com_id LIMIT 1");
					$sync_product = $primaryCompany && $primaryCompany['sync_product'] == 'YES' ? true : false;

					if ($category_id && $category_id != "''") {
						$q = "select pg.pid as PID, pag.name as RID, pg.id as ID, pg.name as NAME from pre_product_groups pg LEFT JOIN pre_product_groups pag on pg.rid = pag.id WHERE pg.id IN($category_id) AND (pg.created_at >= '$last_sync_date' OR pg.updated_at >= '$last_sync_date')";
					} else {
						$q = "select pg.pid as PID, pag.name as RID, pg.id as ID, pg.name as NAME from pre_product_groups pg LEFT JOIN pre_product_groups pag on pg.rid = pag.id WHERE (pg.created_at >= '$last_sync_date' OR pg.updated_at >= '$last_sync_date')";
					}

					$rows = select($q);
					$result['HEADER'] = array('TALLYRESPONSE' => 'Product Group');
					if ($rows && $sync_product) {
						$result['DATA'] = $rows;
					} else {
						$row = array('PID' => '', 'RID' => '', 'ID' => '', 'NAME' => '');
						$result['DATA'] = $row;
					}
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER PRODUCT CATEGORY":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);

				// $last_sync_date = date("Y-m-d", strtotime($rows['last_sync_date']));
				$last_sync_date = $rows['last_sync_date'];
				if ($rows) {

					$q = "select pc.pid as PID, pac.name as RID, pc.id as ID, pc.name as NAME from pre_product_categories pc LEFT JOIN pre_product_categories pac on pc.rid = pac.id WHERE DATE(pc.created_at) >= '$last_sync_date' OR DATE(pc.updated_at) >= '$last_sync_date'";
					$rows = select($q);
					$result['HEADER'] = array('TALLYRESPONSE' => 'Product Category');
					if ($rows) {
						$result['DATA'] = $rows;
					} else {
						$row = array('PID' => '', 'RID' => '', 'ID' => '', 'NAME' => '');
						$result['DATA'] = $row;
					}
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER PRODUCTS":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				$category_id = $rows['lob'];
				$category_id = "'" . $category_id . "'";
				//$category_id = str_replace(',',"' OR p.product_group='",$category_id);
				$category_id = str_replace(',', "','", $category_id);
				$state = $rows['State'];
				// $last_sync_date = date("Y-m-d H:i:s", strtotime($rows['last_sync_date']));
				$last_sync_date = $rows['last_sync_date'];
				//$last_sync_date = '2023-10-31 13:55:39';
				$st = "select name from pre_companygeodetails where id = '$state'";
				$state = selectone($st);
				$state_name = $state['name'];
				if ($rows) {
					$primaryCompany = selectone("SELECT * FROM pre_companies WHERE pid = com_id LIMIT 1");
					$sync_product = $primaryCompany && $primaryCompany['sync_product'] == 'YES' ? true : false;
					//$sync_product = true;
					if ($category_id && $category_id != "''") {
						$q = "select p.pid as PID,p.rid as RID,p.id as ID,p.product_name as NAME,p.description as DESCRIPTION,p.part_number as PARTNO, g.name as PRODUCTGROUP, c.name as PRODUCTCATEGORY, u.symbol as UOM,u.name as UOMFORMALNAME, au.symbol as ALTUOM, tau.symbol as THIRDUOM, p.numerator as NUMERATOR, p.denominator as DENOMINATOR, p.conversion as CONVERSION, p.cst_tax as TAXRATE,p.cst_tax as CSTTAX, p.batchwise as BATCHWISE, p.attribute1 as ATTRIBUTE1, p.attribute2 as ATTRIBUTE2, p.attribute3 as ATTRIBUTE3, p.attribute4 as ATTRIBUTE4, p.attribute5 as ATTRIBUTE5, p.attribute6 as ATTRIBUTE6, p.attribute7 as ATTRIBUTE7, p.attribute8 as ATTRIBUTE8, p.attribute9 as ATTRIBUTE9, p.attribute10 as ATTRIBUTE10, p.sfield1 as SFIELD1, p.sfield2 as SFIELD2, p.sfield3 as SFIELD3, p.sfield4 as SFIELD4, p.sfield5 as SFIELD5, p.sfield6 as SFIELD6, p.sfield7 as SFIELD7, p.sfield8 as SFIELD8, p.sfield9 as SFIELD9, p.sfield10 as SFIELD10, p.nfield1 as NFIELD1, p.nfield2 as NFIELD2, p.nfield3 as NFIELD3, p.nfield4 as NFIELD4, p.nfield5 as NFIELD5, p.dfield1 as DFIELD1, p.dfield2 as DFIELD2, p.dfield3 as DFIELD3, p.dfield4 as DFIELD4, p.dfield5 as DFIELD5, p.creation_date as CREATION_DATE, p.altered_on as ALTERED_ON  FROM pre_product_management p LEFT JOIN pre_product_categories c on p.product_category = c.id LEFT JOIN pre_product_groups g on p.product_group = g.id LEFT JOIN pre_uoms u on p.uom = u.id LEFT JOIN pre_uoms au on p.alt_uom = au.id LEFT JOIN pre_uoms tau on p.thirduom = tau.id where p.product_group IN($category_id) AND (p.creation_date >= '$last_sync_date' OR p.altered_on >= '$last_sync_date')";
					} else {
						$q = "select p.pid as PID,p.rid as RID,p.id as ID,p.product_name as NAME,p.description as DESCRIPTION,p.part_number as PARTNO, g.name as PRODUCTGROUP, c.name as PRODUCTCATEGORY, u.symbol as UOM,u.name as UOMFORMALNAME, au.symbol as ALTUOM, tau.symbol as THIRDUOM, p.numerator as NUMERATOR, p.denominator as DENOMINATOR, p.conversion as CONVERSION, p.cst_tax as TAXRATE,p.cst_tax as CSTTAX, p.batchwise as BATCHWISE, p.attribute1 as ATTRIBUTE1, p.attribute2 as ATTRIBUTE2, p.attribute3 as ATTRIBUTE3, p.attribute4 as ATTRIBUTE4, p.attribute5 as ATTRIBUTE5, p.attribute6 as ATTRIBUTE6, p.attribute7 as ATTRIBUTE7, p.attribute8 as ATTRIBUTE8, p.attribute9 as ATTRIBUTE9, p.attribute10 as ATTRIBUTE10, p.sfield1 as SFIELD1, p.sfield2 as SFIELD2, p.sfield3 as SFIELD3, p.sfield4 as SFIELD4, p.sfield5 as SFIELD5, p.sfield6 as SFIELD6, p.sfield7 as SFIELD7, p.sfield8 as SFIELD8, p.sfield9 as SFIELD9, p.sfield10 as SFIELD10, p.nfield1 as NFIELD1, p.nfield2 as NFIELD2, p.nfield3 as NFIELD3, p.nfield4 as NFIELD4, p.nfield5 as NFIELD5, p.dfield1 as DFIELD1, p.dfield2 as DFIELD2, p.dfield3 as DFIELD3, p.dfield4 as DFIELD4, p.dfield5 as DFIELD5, p.creation_date as CREATION_DATE, p.altered_on as ALTERED_ON  FROM pre_product_management p LEFT JOIN pre_product_categories c on p.product_category = c.id LEFT JOIN pre_product_groups g on p.product_group = g.id LEFT JOIN pre_uoms u on p.uom = u.id LEFT JOIN pre_uoms au on p.alt_uom = au.id LEFT JOIN pre_uoms tau on p.thirduom = tau.id where (p.creation_date >= '$last_sync_date' OR p.altered_on >= '$last_sync_date')";
					}


					$rows = select($q);
					$datas = array();
					foreach ($rows as $row) {
						$data = array();
						$data = $row;
						$data['CREATION_DATE'] = str_replace('-', '/', date("d-m-Y", strtotime($data['CREATION_DATE'])));
						$data['ALTERED_ON'] = str_replace('-', '/', date("d-m-Y", strtotime($data['ALTERED_ON'])));
						$datas[] = $data;
					}

					if ($datas && $sync_product) {
						$result['HEADER'] = array('NAME' => 'PRODUCTS');
						$result['DATA'] = $datas;
						header("Content-type: text/xml");
						$xml = Array2XML::createXML('ENVELOPE', $result);
						$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
						echo $output;
					} else {
						$datass = array('PID' => '', 'RID' => '', 'ID' => '', 'NAME' => '', 'DESCRIPTION' => '', 'PARTNO' => '', 'PRODUCTGROUP' => '', 'PRODUCTCATEGORY' => '', 'UOM' => '', 'UOMFORMALNAME' => '', 'ALTUOM' => '', 'THIRDUOM' => '', 'NUMERATOR' => '', 'DENOMINATOR' => '', 'CONVERSION' => '', 'TAXRATE' => '', 'CSTTAX' => '', 'BATCHWISE' => '', 'ATTRIBUTE1' => '', 'ATTRIBUTE2' => '', 'ATTRIBUTE3' => '', 'ATTRIBUTE4' => '', 'ATTRIBUTE5' => '', 'ATTRIBUTE6' => '', 'ATTRIBUTE7' => '', 'ATTRIBUTE8' => '', 'ATTRIBUTE9' => '', 'ATTRIBUTE10' => '', 'SFIELD1' => '', 'SFIELD2' => '', 'SFIELD3' => '', 'SFIELD4' => '', 'SFIELD5' => '', 'SFIELD6' => '', 'SFIELD7' => '', 'SFIELD8' => '', 'SFIELD9' => '', 'SFIELD10' => '', 'NFIELD1' => '', 'NFIELD2' => '', 'NFIELD3' => '', 'NFIELD4' => '', 'NFIELD5' => '', 'DFIELD1' => '', 'DFIELD2' => '', 'DFIELD3' => '', 'DFIELD4' => '', 'DFIELD5' => '', 'CREATION_DATE' => '', 'ALTERED_ON' => '');
						$result['HEADER'] = array('NAME' => 'PRODUCTS');
						$result['DATA'] = $datass;
						header("Content-type: text/xml");
						$xml = Array2XML::createXML('ENVELOPE', $result);
						$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
						echo $output;
					}
				} else {
					echo "Invalid Request!";
				}
				break;
			case "REQMASTER PRICELIST":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				$category_id = $rows['lob'];
				$category_id = "'" . $category_id . "'";
				//$category_id = str_replace(',',"' OR p.product_group='",$category_id);
				$category_id = str_replace(',', "','", $category_id);


				// $last_sync_date = date("Y-m-d", strtotime($rows['last_sync_date']));
				$last_sync_date = $rows['last_sync_date'];
				$state = $rows['State'];
				$st = "select name from pre_companygeodetails where id = '$state'";
				$state = selectone($st);
				$state_name = $state['name'];

				$distributor_type = $rows['distributor_type'];
				$st = "select lob_name from pre_lobs where id = '$distributor_type'";
				$type = selectone($st);
				$distributor_type_name = $type['lob_name'];

				$st = "select price_type from pre_companies where pid = rid AND rid = com_id";
				$com = selectone($st);
				if ($com['price_type'] == "State") {
					$where = "and price_type='State' and (state = '$state_name' OR state ='All State')";
				} elseif ($com['price_type'] == "Type") {
					$where = "and price_type='Type' and (state = '$distributor_type_name' OR state ='All Type')";
				}

				if ($rows) {

					$q = "select pid as PID,rid as RID,id as ID,product_name as NAME FROM pre_product_management WHERE product_group IN($category_id)";

					$rows = select($q);

					foreach ($rows as $row) {
						$data = array();
						$data = $row;
						$id = $data['ID'];
						$list = "select tax_rate as TAXRATE, addlvatrate as ADDLVATRATE, vatsurchargerate as VATSURCHARGERATE, vatcessrate as VATCESSRATE, cstformrate as CSTFORMRATE, cstwoformrate as CSTWOFORMRATE, cost_effect_date as COSTAPPDATE,cost as COSTPRICE,price_effect_date as PRICEAPPDATE,price as SALEPRICE,mrp_effect_date as MRPAPPDATE,mrp as MRPPRICE from pre_price_lists where product_id = '$id' $where and (creation_date >='$last_sync_date' OR alter_date >='$last_sync_date')";
						$price_list = select($list);

						if ($price_list) {
							foreach ($price_list as $list) {
								$data['TAXRATE'] = $list['TAXRATE'];
								$data['ADDLVATRATE'] = $list['ADDLVATRATE'];
								$data['VATSURCHARGERATE'] = $list['VATSURCHARGERATE'];
								$data['VATCESSRATE'] = $list['VATCESSRATE'];
								$data['CSTFORMRATE'] = $list['CSTFORMRATE'];
								$data['CSTWOFORMRATE'] = $list['CSTWOFORMRATE'];
								$data['COSTLIST'][] = array('COSTAPPDATE' => str_replace('-', '/', date("d-m-Y", strtotime($list['COSTAPPDATE']))), 'COSTPRICE' => $list['COSTPRICE']);
								$data['PRICELIST'][] = array('PRICEAPPDATE' => str_replace('-', '/', date("d-m-Y", strtotime($list['PRICEAPPDATE']))), 'SALEPRICE' => $list['SALEPRICE']);
								$data['MRPLIST'][] = array('MRPAPPDATE' => str_replace('-', '/', date("d-m-Y", strtotime($list['MRPAPPDATE']))), 'MRPPRICE' => $list['MRPPRICE']);
							}
							$datas[] = $data;
						}/* else{
						$data['TAXRATE'] = '';
						$data['ADDLVATRATE'] = '';
						$data['VATSURCHARGERATE'] = '';
						$data['VATCESSRATE'] = '';
						$data['CSTFORMRATE'] = '';
						$data['CSTWOFORMRATE'] = '';
						
						$data['COSTLIST'] = array('COSTAPPDATE'=>'', 'COSTPRICE'=>'');
						$data['PRICELIST'] = array('PRICEAPPDATE'=>'', 'SALEPRICE'=>'');
						$data['MRPLIST'] = array('MRPAPPDATE'=>'', 'MRPPRICE'=>'');
					}
					$datas[]= $data; */
					}


					$result['HEADER'] = array('NAME' => 'PRICELIST');
					if (empty($datas)) {
						$blankarray = array('PID' => '', 'RID' => '', 'ID' => '', 'NAME' => '');
						$blankarray['TAXRATE'] = '';
						$blankarray['ADDLVATRATE'] = '';
						$blankarray['VATSURCHARGERATE'] = '';
						$blankarray['VATCESSRATE'] = '';
						$blankarray['CSTFORMRATE'] = '';
						$blankarray['CSTWOFORMRATE'] = '';
						$blankarray['COSTLIST'] = array('COSTAPPDATE' => '', 'COSTPRICE' => '');
						$blankarray['PRICELIST'] = array('PRICEAPPDATE' => '', 'SALEPRICE' => '');
						$blankarray['MRPLIST'] = array('MRPAPPDATE' => '', 'MRPPRICE' => '');
						$result['DATA'] = $blankarray;
					} else {
						$result['DATA'] = $datas;
					}
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "DISTRIBUTOR MASTER":

				$SEAPBILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$pass = selectone("SELECT * FROM pre_companies WHERE pid = '$PRINCIPALID'");
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);

				if ($rows) {
					$vals = $array['BODY']['DATA']['REQUESTDATA'];
					//$val_count = count($vals);
					/* if($val_count >=68){
					$valss[] = $vals; 
				}
				else{
					$valss = $vals;
				} */

					if (!isset($array['BODY']['DATA']['REQUESTDATA'][0])) {
						$valss[] = $array['BODY']['DATA']['REQUESTDATA'];
					} else {
						$valss = $array['BODY']['DATA']['REQUESTDATA'];
					}

					$sql = "SELECT id,name FROM pre_companygeodetails";
					$geos = select($sql);
					foreach ($geos as $geo) {
						$geoss[$geo['name']] = $geo['id'];
					}
					$sql = "SELECT level_id,name FROM pre_levels";
					$levels = select($sql);
					foreach ($levels as $level) {
						$levelss[$level['name']] = $level['level_id'];
					}
					$sql = "SELECT id,lob_name FROM pre_lobs";
					$lobs = select($sql);
					foreach ($lobs as $lob) {
						$lobss[$lob['lob_name']] = $lob['id'];
					}
					$sql = "SELECT id,name FROM pre_users";
					$users = select($sql);
					foreach ($users as $user) {
						$userss[$user['name']] = $user['id'];
					}


					foreach ($valss as $val) {
						$val['NAME'] = mysqli_real_escape_string($link, htmlspecialchars_decode($val['NAME']));
						$data = $val;
						$pid = $data['PID'];
						$rid = $data['RID'];
						$SHORTCODE = $data['SHORTCODE'];
						unset($data['DISID']);
						unset($data['CREATIONDATE']);
						unset($data['ALTEREDON']);
						unset($data['ROLLOUTDATE']);
						unset($data['LASTSYNCDATE']);
						unset($data['DATAFROMDATE']);
						unset($data['LASTTRANSACTIONDATE']);
						unset($data['ROLLBACKDATE']);
						unset($data['ROLLBACKEXECUTEDON']);
						unset($data['TALLYSERIALNO']);
						unset($data['TALLYRELEASE']);
						unset($data['TCPVERSION']);
						unset($data['SHORTCODE']);
						unset($data['SALEHIER']);
						unset($data['HANDLEDBY']);
						unset($data['TALLYVERSION']);
						unset($data['TSSEXPIRY']);
						if ($data['CITY']) {
							$data['CITY'] = $geoss[$data['CITY']];
						} else {
							$data['CITY'] = '';
						}
						if ($data['DISTRICT']) {
							$data['DISTRICT'] = $geoss[$data['DISTRICT']];
						} else {
							$data['DISTRICT'] = '';
						}
						if ($data['STATE']) {
							$data['STATE'] = $geoss[$data['STATE']];
						} else {
							$data['STATE'] = '';
						}
						if ($data['COUNTRY']) {
							$data['COUNTRY'] = $geoss[$data['COUNTRY']];
						} else {
							$data['COUNTRY'] = '';
						}
						if ($data['ZONE']) {
							$data['ZONE'] = $geoss[$data['ZONE']];
						} else {
							$data['ZONE'] = '';
						}
						if ($data['REGION']) {
							$data['REGION'] = $geoss[$data['REGION']];
						} else {
							$data['REGION'] = '';
						}
						$data['TYPE'] = $levelss[$data['TYPE']];
						$data['DISTRIBUTOR_TYPE'] = $lobss[$data['LOB']];
						$data['SALESHIERARCHY'] = $levelss[$val['SALEHIER']];
						$data['handle_by'] = $userss[$val['HANDLEDBY']];
						$data['COMPANY'] = $pass['id'];

						$data['CREATION_DATE'] = ($val['CREATIONDATE']) ? date("Y-m-d", strtotime($val['CREATIONDATE'])) : '';
						$data['ALTERED_ON'] = ($val['ALTEREDON']) ? $val['ALTEREDON'] : "0000-00-00";
						$data['DIST_SORT_CODE'] = $val['SHORTCODE'];
						$data['ROLL_OUT_DATE'] = ($val['ROLLOUTDATE'] && $val['ROLLOUTDATE'] != '0000-00-00 00:00:00') ? $val['ROLLOUTDATE'] : "0000-00-00";
						$data['LAST_SYNC_DATE'] = ($val['LASTSYNCDATE'] && $val['LASTSYNCDATE'] != '0000-00-00 00:00:00') ? $val['LASTSYNCDATE'] : "0000-00-00";
						$data['DATA_FROM_DATE'] = ($val['DATAFROMDATE']) ? $val['DATAFROMDATE'] : "0000-00-00";
						$data['LAST_TRANSACTION_DATE'] = ($val['LASTTRANSACTIONDATE']) ? $val['LASTTRANSACTIONDATE'] : "0000-00-00";
						$data['ROLL_BACK_DATE'] = ($val['ROLLBACKDATE']) ? $val['ROLLBACKDATE'] : "0000-00-00";
						$data['ROLL_BACK_EXECUTED_ON'] = ($val['ROLLBACKEXECUTEDON']) ? $val['ROLLBACKEXECUTEDON'] : "0000-00-00";
						$data['TALLY_SERIAL_NO'] = $val['TALLYSERIALNO'];
						$data['TALLY_RELEASE'] = $val['TALLYRELEASE'];
						$data['TCP_VERSION'] = $val['TCPVERSION'];
						$data['TALLY_VERSION'] = $val['TALLYVERSION'];
						$data['TSS_EXPIRY'] = $val['TSSEXPIRY'];
						//--Preventing duplicate data entry
						$q = "select * from pre_distributors where id='" . $data['ID'] . "' AND dist_sort_code='" . $SHORTCODE . "'";
						$rows = selectone($q);

						$string = '';
						if ($rows) {
							$id = $data['ID'];
							unset($data['ID']);
							unset($data['PID']);
							unset($data['RID']);
							$data['BW_POSTED'] = NULL;
							foreach ($data as $key => $value) {
								$string .= $key . "='" . $value . "',";
							}
							$string = substr($string, 0, -1);

							$sql = "UPDATE pre_distributors set " . $string . " WHERE id='" . $id . "' AND dist_sort_code='" . $SHORTCODE . "' AND pid='" . $pid . "' AND rid='" . $rid . "'";

							mysqli_query($link, $sql);
						} else {

							insert($tablename = 'pre_distributors', $data);
						}
					}
					echo "DISTRIBUTOR IMPORTED SUCCESSFULLY";
				} else {
					echo "Invalid Request!";
				}

				break;
			case "STOCK REPORT":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$vals = $array['BODY']['DATA']['REQUESTDATA'];
					//$valu_count =  count($vals);
					if (!isset($array['BODY']['DATA']['REQUESTDATA'][0])) {
						$valss[] = $array['BODY']['DATA']['REQUESTDATA'];
					} else {
						$valss = $array['BODY']['DATA']['REQUESTDATA'];
					}
					foreach ($valss as $val) {
						$itemname = $val['STOCKNO'];
						unset($val['STOCKNO']);
						$q = "select * from pre_product_management where '$itemname' IN (`id`, `sfield6`, `sfield7`, `sfield8`, `sfield9`)";
						$products = selectone($q);
						if ($products) {
							$itemcode = $products['id'];
							$data = $val;
							$form_date = $data['FROMDATE'];
							$data['FROMDATE'] = date('Y-m-d', strtotime($data['FROMDATE']));
							$data['TODATE'] = date('Y-m-d', strtotime($data['TODATE']));

							$year = date('Y', strtotime($data['FROMDATE']));
							$month = date('m', strtotime($data['FROMDATE']));

							$q = "select * from pre_stockreports where distid = '$SEAPBILLINGCODE' AND itemcode='$itemcode' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' ORDER BY id ASC LIMIT 1";
							$stock = selectone($q);
							unset($data['BATCHDETAILS']);
							if ($stock) {
								$stkid = $stock['id'];
								$openqty = $data['OPENQTY'];
								$alternateopenqty = $data['ALTERNATEOPENQTY'];
								$tailopenqty = $data['TAILOPENQTY'];

								if (in_array($itemcode, $primaryIds)) {
									$openqty  = (float)$openqty + (float) $stock['openqty'];
									$alternateopenqty  = (float)$alternateopenqty + (float) $stock['alternateopenqty'];
									$tailopenqty  = (float)$tailopenqty + (float) $stock['tailopenqty'];
								}
								$primaryIds[] = $itemcode;


								$sql = "UPDATE pre_stockreports set openqty = $openqty, alternateopenqty = $alternateopenqty, tailopenqty = $tailopenqty, bw_posted=NULL WHERE id = $stkid";
								mysqli_query($link, $sql);
								if ($stkId && $stkId != '') {
									updateClosingStock($link, $stkId);
								}

								//$sql = "DELETE FROM pre_stockreports where  distid = '$SEAPBILLINGCODE' AND itemcode='$itemcode' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' AND id !=$stkid";
								//mysqli_query($link, $sql);
							} else {
								$data['itemcode'] = $products['id'];

								if (isset($data['itemname'])) {
									unset($data['itemname']);
								}
								if (isset($data['ITEMNAME'])) {
									unset($data['ITEMNAME']);
								}
								$data['itemname'] = $products['product_name'];
								$primaryIds[] = $products['id'];

								$stkId = insert($tablename = 'pre_stockreports', $data);
								if ($stkId && $stkId != '') {
									updateClosingStock($link, $stkId);
								}
							}
							// $sql = "DELETE FROM pre_stockreports WHERE distid='$SEAPBILLINGCODE' AND fromdate='".$data['FROMDATE']."' AND itemcode='".$products['id']."'";
							// mysqli_query($link, $sql);

							if (!isset($val['BATCHDETAILS'][0])) {
								$batch[] = $val['BATCHDETAILS'];
							} else {
								$batch = $val['BATCHDETAILS'];
							}
							foreach ($batch as $btch) {
								$data['itemcode'] = $products['id'];
								$itemcode = $products['id'];
								$itemgodownname = $btch['ITEMGODOWNNAME'];
								$itembatchname = $btch['ITEMBATCHNAME'];
								$batchopenqty  = $btch['BATCHOPENQTY'];
								$alternatebatchopenqty = $btch['ALTERNATEBATCHOPENQTY'];
								$tailbatchopenqty = $btch['TAILBATCHOPENQTY'];

								$q = "select * from pre_stockreports where distid = '$SEAPBILLINGCODE' AND itemcode='$itemcode' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' AND itemgodownname = '$itemgodownname' AND itembatchname = '$itembatchname' ORDER BY id ASC LIMIT 1";
								$batchstock = selectone($q);
								if ($batchstock) {
									$batchstockid = $batchstock['id'];
									if (in_array($itemcode, $primaryBatchIds) && in_array($itemgodownname . '-' . $itembatchname, $primaryBatchNames)) {
										$batchopenqty  = (float)$batchopenqty + (float) $batchstock['batchopenqty'];
										$alternatebatchopenqty  = (float)$alternatebatchopenqty + (float) $batchstock['alternatebatchopenqty'];
										$tailbatchopenqty  = (float)$tailbatchopenqty + (float) $batchstock['tailbatchopenqty'];
									}
									$primaryBatchIds[] = $itemcode;
									$primaryBatchNames[] = $itemgodownname . '-' . $itembatchname;
									$sql = "UPDATE pre_stockreports set batchopenqty = '$batchopenqty', alternatebatchopenqty = '$alternatebatchopenqty', tailbatchopenqty = '$tailbatchopenqty', bw_posted=NULL WHERE id = $batchstockid";
									mysqli_query($link, $sql);
									if ($batchstockid && $batchstockid != '') {
										updateClosingStock($link, $batchstockid);
									}
								} else {
									unset($data['OPENQTY']);
									unset($data['ALTERNATEOPENQTY']);
									unset($data['TAILOPENQTY']);
									$data['ITEMGODOWNNAME'] = $btch['ITEMGODOWNNAME'];
									$data['ITEMBATCHNAME'] = $btch['ITEMBATCHNAME'];
									$data['BATCHOPENQTY'] = $batchopenqty;
									$data['ALTERNATEBATCHOPENQTY'] = $alternatebatchopenqty;
									$data['TAILBATCHOPENQTY'] = $tailbatchopenqty;
									if (isset($data['itemname'])) {
										unset($data['itemname']);
									}
									if (isset($data['ITEMNAME'])) {
										unset($data['ITEMNAME']);
									}
									$data['itemname'] = $products['product_name'];
									$stkId = insert($tablename = 'pre_stockreports', $data);
									if ($stkId && $stkId != '') {
										updateClosingStock($link, $stkId);
									}
									$primaryBatchIds[] = $itemcode;
								}
							}
							unset($data);
							unset($batch);
						} else {
							$itemcode = $itemname;
							$data = $val;
							$form_date = $data['FROMDATE'];
							$data['FROMDATE'] = date('Y-m-d', strtotime($data['FROMDATE']));
							$data['TODATE'] = date('Y-m-d', strtotime($data['TODATE']));

							$year = date('Y', strtotime($data['FROMDATE']));
							$month = date('m', strtotime($data['FROMDATE']));

							$q = "select * from pre_stockreport_errors where distid = '$SEAPBILLINGCODE' AND itemcode='$itemcode' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' ORDER BY id ASC LIMIT 1";
							$stock = selectone($q);
							unset($data['BATCHDETAILS']);
							if ($stock) {
								$stkid = $stock['id'];
								$openqty = $data['OPENQTY'];
								$alternateopenqty = $data['ALTERNATEOPENQTY'];
								$tailopenqty = $data['TAILOPENQTY'];

								if (in_array($itemcode, $primaryIds)) {
									$openqty  = (float)$openqty + (float) $stock['openqty'];
									$alternateopenqty  = (float)$alternateopenqty + (float) $stock['alternateopenqty'];
									$tailopenqty  = (float)$tailopenqty + (float) $stock['tailopenqty'];
								}
								$primaryIds[] = $itemcode;

								$sql = "UPDATE pre_stockreport_errors set openqty = $openqty, alternateopenqty = $alternateopenqty, tailopenqty = $tailopenqty, bw_posted=NULL WHERE id = $stkid";
								mysqli_query($link, $sql);

								$sql = "DELETE FROM pre_stockreport_errors where distid = '$SEAPBILLINGCODE' AND itemcode='$itemcode' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' AND id !=$stkid";
								mysqli_query($link, $sql);
							} else {

								$data['itemcode'] = $itemcode;
								$primaryIds[] = $itemcode;
								insert($tablename = 'pre_stockreport_errors', $data);
							}

							if (!isset($val['BATCHDETAILS'][0])) {
								$batch[] = $val['BATCHDETAILS'];
							} else {
								$batch = $val['BATCHDETAILS'];
							}
							foreach ($batch as $btch) {
								$data['itemcode'] = $itemcode;
								unset($data['OPENQTY']);
								unset($data['ALTERNATEOPENQTY']);
								unset($data['TAILOPENQTY']);
								$data['ITEMGODOWNNAME'] = $btch['ITEMGODOWNNAME'];
								$data['ITEMBATCHNAME'] = $btch['ITEMBATCHNAME'];
								$data['BATCHOPENQTY'] = $btch['BATCHOPENQTY'];
								$data['ALTERNATEBATCHOPENQTY'] = $btch['ALTERNATEBATCHOPENQTY'];
								$data['TAILBATCHOPENQTY'] = $btch['TAILBATCHOPENQTY'];
								insert($tablename = 'pre_stockreport_errors', $data);
							}
							unset($data);
							unset($batch);
						}
					}
					$date = date('Y-m-d', strtotime($form_date));
					$sql = "UPDATE pre_distributors set last_stock_date = '$date' WHERE id = '$SEAPBILLINGCODE' AND last_stock_date < '$date'";
					mysqli_query($link, $sql);
					echo "Success!";
				} else {
					echo "Invalid Request!";
				}
				break;
			case "DEALER OUTSTANDING":
				$BILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$PRINCIPALID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$OTP = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				// for check password
				$distributor_varification = selectone("SELECT * FROM pre_distributors WHERE id ='$BILLINGCODE' and password='$OTP' and pid='$PRINCIPALID'");
				if ($distributor_varification) {
					if (!isset($array['BODY']['DATA']['REQUESTDATA'][0])) {
						$valss[] = $array['BODY']['DATA']['REQUESTDATA'];
					} else {
						$valss = $array['BODY']['DATA']['REQUESTDATA'];
					}


					foreach ($valss as $val) {

						$disp_name = $val['DSPACCNAME']['DSPDISPNAME'];
						if ($disp_name) {
							$date = date('Y-m-d', strtotime($val['TODATE']));
							if (strtotime($date) > strtotime($distributor_varification['last_os_date'])) {
								$data['PID'] = $PRINCIPALID;
								$data['DIST_ID'] = $BILLINGCODE;
								$data['code'] = $val['ID'];
								$data['DSPDISPNAME'] = $disp_name;

								$fromdate = $val['FROMDATE'];
								$todate = $val['TODATE'];

								$data['FROMDATE'] = date('Y-m-d', strtotime($fromdate));
								$data['TODATE'] = date('Y-m-d', strtotime($todate));

								$data['pending_debit'] = $val['DSPACCINFO'][0]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['pending_credit'] = $val['DSPACCINFO'][0]['DSPCLCRAMT']['DSPCLCRAMTA'];

								$data['thirty_debit'] = $val['DSPACCINFO'][1]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['thirty_credit'] = $val['DSPACCINFO'][1]['DSPCLCRAMT']['DSPCLCRAMTA'];

								$data['thirty_sixty_debit'] = $val['DSPACCINFO'][2]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['thirty_sixty_credit'] = $val['DSPACCINFO'][2]['DSPCLCRAMT']['DSPCLCRAMTA'];

								$data['sixty_ninty_debit'] = $val['DSPACCINFO'][3]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['sixty_ninty_credit'] = $val['DSPACCINFO'][3]['DSPCLCRAMT']['DSPCLCRAMTA'];

								$data['ninty_debit'] = $val['DSPACCINFO'][4]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['ninty_credit'] = $val['DSPACCINFO'][4]['DSPCLCRAMT']['DSPCLCRAMTA'];

								$data['acc_debit'] = $val['DSPACCINFO'][5]['DSPCLDRAMT']['DSPCLDRAMTA'];
								$data['acc_credit'] = $val['DSPACCINFO'][5]['DSPCLCRAMT']['DSPCLCRAMTA'];
								insert($tablename = 'pre_dealer_outstanding', $data);
								$data = array();
								$sql = "UPDATE pre_distributors set last_os_date = '$date' WHERE id = '$BILLINGCODE' AND last_os_date < '$date'";
								mysqli_query($link, $sql);
							}
						}
					}

					echo "Success!";
				} else {
					echo "Invalid Request!";
				}

				break;
				/* case "permpass":
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
            break; */
			case "REQMASTER UOM":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$qs = "select pid as PID, nature as TYPE, symbol as SYMBOL, name as FORMALNAME, decimals as DECIMALS, first_unit as FIRSTUNIT, conversion as CONVERSION, second_unit as SECONDUNIT from pre_uoms";

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
			case "REQMASTER LOB MASTER":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$qs = "select pid as PID,id as ID,lob_name as NAME from pre_lobs";

					$rows = select($qs);
					$result['HEADER'] = array('TALLYRESPONSE' => 'LOB MASTER');
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
				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$qs = "select pid as PID, rid as RID, id as ID, name as NAME, nature as NATURE from pre_sales_hierarchy";

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

			case "REQMASTER SYNCSTATUS":
				try {
					$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
					$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
					$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
					$LASTSYNCDATE = convertAndHandleDate($array['BODY']['DATA']['LASTSYNCDATE']);
					$LASTTRANSACTIONDATE = convertAndHandleDate($array['BODY']['DATA']['LASTTRANSACTIONDATE'], 'Y-m-d');
					$LASTSTOCKUPLOADEDON = convertAndHandleDate($array['BODY']['DATA']['LASTSTOCKUPLOADEDON']);
					$LASTOSUPLOADEDON = $array['BODY']['DATA']['LASTOSUPLOADEDON'];

					$LASTSENTDEALERMASTERID = $array['BODY']['DATA']['LASTSENTDEALERMASTERID'];
					$LASTSENTDEALERALTERID = $array['BODY']['DATA']['LASTSENTDEALERALTERID'];

					$LASTSENTTRANSMASTERID = $array['BODY']['DATA']['LASTSENTTRANSMASTERID'];
					$LASTSENTTRANSALTERID = $array['BODY']['DATA']['LASTSENTTRANSALTERID'];

					$TALLYSERIALNO = $array['BODY']['DATA']['TALLYSERIALNO'];
					$TALLYVERSION = $array['BODY']['DATA']['TALLYVERSION'];
					$TALLYRELEASE = $array['BODY']['DATA']['TALLYRELEASE'];
					$TSSEXPIRY = $array['BODY']['DATA']['TSSEXPIRY'];
					$TCPVERSION = $array['BODY']['DATA']['TCPVERSION'];


					$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
					$rows = selectone($q);
					if ($rows) {

						$last_sync_sql = "INSERT INTO `last_sync_status` ( `distributor_id`, `last_sync_date`, `last_transaction_date`, `last_stock_date`, `last_os_date`, `last_trans_master_id`, `last_trans_alter_id`, `last_dealer_master_id`, `last_dealer_alter_id`, `created_at`) VALUES 				('$SEAPBILLINGCODE','$LASTSYNCDATE','$LASTTRANSACTIONDATE','$LASTSTOCKUPLOADEDON','$LASTOSUPLOADEDON','$LASTSENTTRANSMASTERID','$LASTSENTTRANSALTERID','$LASTSENTDEALERMASTERID','$LASTSENTDEALERALTERID',NOW())";
						mysqli_query($link, $last_sync_sql);
						//$sql = "UPDATE pre_distributors set last_sync_date = NOW() WHERE id = '$SEAPBILLINGCODE'"; 
						$sql = "UPDATE pre_distributors set last_sync_date = '$LASTSYNCDATE',last_transaction_date = '$LASTTRANSACTIONDATE',
					 last_stock_date = '$LASTSTOCKUPLOADEDON',last_os_date = '$LASTOSUPLOADEDON',
					 last_trans_master_id='$LASTSENTTRANSMASTERID',last_trans_alter_id='$LASTSENTTRANSALTERID',
					 tally_serial_no='$TALLYSERIALNO',tally_serial_no='$TALLYSERIALNO',tally_version='$TALLYVERSION',
					 tally_release='$TALLYRELEASE',tss_expiry='$TSSEXPIRY',tcp_version='$TCPVERSION',
					 last_dealer_master_id= '$LASTSENTDEALERMASTERID',last_dealer_alter_id='$LASTSENTDEALERALTERID',updated_at=NOW()			 
					 WHERE id = '$SEAPBILLINGCODE'";

						mysqli_query($link, $sql);

						$rows = selectone($q);
						$array['BODY']['DATA'];
						$array['BODY']['DATA'] = [];
						$array['BODY']['DATA']['PRINCIPALID'] = $PRINCIPALID;
						$array['BODY']['DATA']['SEAPBILLINGCODE'] = $SEAPBILLINGCODE;
						$array['BODY']['DATA']['PASSWORD'] = $PASSWORD;
						$array['BODY']['DATA']['REQUESTDATA']['LASTSYNCDATE'] = $rows['last_sync_date'];
						$array['BODY']['DATA']['REQUESTDATA']['LASTTRANSACTIONDATE'] = $rows['last_transaction_date'];
						$result['HEADER'] = array('TALLYRESPONSE' => 'SYNCSTATUS');
						$result['DATA'] = $array['BODY']['DATA'];
						header("Content-type: text/xml");
						$xml = Array2XML::createXML('ENVELOPE', $result);
						$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
						echo $output;
					} else {
						echo "Invalid Request!";
					}
				} catch (Exception $e) {
					$error_message = mysqli_real_escape_string($link, $e->getMessage());
					$error_code = $e->getCode();
					$error_line = $e->getLine();
					$insert_query = "INSERT INTO error_logs (tally_request,error_message, error_code, error_line) 
				VALUES ('REQMASTER SYNCSTATUS','$error_message', $error_code, $error_line)";
					mysqli_query($link, $insert_query);
					echo "Invalid Request!";
				}
				break;
			case "MESSAGE_BOARD REQUEST":
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];

				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$sql = "SELECT * FROM `pre_companies` WHERE pid = com_id limit 1";
					$hasSyncMessageBoard = selectone($sql);
					if (isset($hasSyncMessageBoard['sync_message_board']) && $hasSyncMessageBoard['sync_message_board'] == 'YES') {
						$q = "SELECT id,message,color,style FROM pre_message_boards";
						$messages = select($q);
						foreach ($messages as $key => $message) {
							$array['BODY']['DATA']['REQUESTDATA']['MESSAGEBOARD'][]
								= ['MESSAGETEXT' => $message['message'], 'MESSAGECOLOR' => $message['color'], 'MESSAGESTYLE' => $message['style']];
						}
					}

					$result['DATA'] = $array['BODY']['DATA'];

					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Request!";
				}
				break;
			case "ROLLBACK REQUEST":

				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$DATA = $array['BODY']['DATA'];

				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);

				if ($rows) {
					$pid = $DATA['PRINCIPALID'];
					$distributor_id = $DATA['SEAPBILLINGCODE'];
					$password = $DATA['PASSWORD'];
					$tally_last_trans_mst_id = $DATA['TALLYLASTTRANSMSTID'];
					$tally_last_trans_alt_id = $DATA['TALLYLASTTRANSALTID'];
					$tally_last_trans_date = $DATA['TALLYLASTTRANSDATE'];
					$tally_last_stock_date = $DATA['TALLYLASTSTOCKDATE'];
					$rollback_message = $DATA['ROLLBACKMESSAGE'];
					$rollback_reason = $DATA['ROLLBACKREASON'];


					$tally_last_trans_date = strtotime($tally_last_trans_date) ? date('Y-m-d', strtotime($tally_last_trans_date)) : null;
					$tally_last_stock_date = strtotime($tally_last_stock_date) ? date('Y-m-d', strtotime($tally_last_stock_date)) : null;
					$garruda_last_masterid = $rows['last_trans_master_id'];
					$garruda_last_alterid = $rows['last_trans_alter_id'];
					$garruda_last_transdate = strtotime($rows['last_transaction_date']) ? date('Y-m-d', strtotime($rows['last_transaction_date'])) : '';
					$garruda_last_stockdate = strtotime($rows['last_stock_date']) ? date('Y-m-d', strtotime($rows['last_stock_date'])) : '';

					$created_at = date('Y-m-d H:i:s');
					$updated_at = date('Y-m-d H:i:s');
					$q = "select * from pre_rollbacks where pid = '$pid' and distributor_id = '$distributor_id' and tally_last_masterid='$tally_last_trans_mst_id' and tally_last_alterid = '$tally_last_trans_alt_id' and tally_last_transdate='$tally_last_trans_date' and tally_last_stockdate = '$tally_last_stock_date' AND rollback_status != 'CANCELED' AND rollback_status != 'COMPLETE' AND rollback_status != 'COMPLETED'";
					$rows = selectone($q);
					$status = 'PENDING';
					$remark = '';

					if ($rows || ($tally_last_trans_mst_id == $garruda_last_masterid && $tally_last_trans_alt_id == $garruda_last_alterid && $tally_last_trans_date == $garruda_last_transdate && $tally_last_stock_date == $garruda_last_stockdate)) {
						$status = 'CANCELED';
						$remark = 'Duplicate Entry';
					}

					$sql = "INSERT INTO `pre_rollbacks`(`pid`, `distributor_id`, `password`, `rollback_message`, `rollback_reason`,  `created_at`, `updated_at`, `tally_last_masterid`, `tally_last_alterid`, `tally_last_transdate`, `tally_last_stockdate`, `garruda_last_masterid`, `garruda_last_alterid`, `garruda_last_transdate`, `garruda_last_stockdate`,`rollback_status`,`remark`) VALUES ('$pid','$distributor_id','$password','$rollback_message','$rollback_reason','$created_at','$updated_at','$tally_last_trans_mst_id','$tally_last_trans_alt_id','$tally_last_trans_date','$tally_last_stock_date','$garruda_last_masterid','$garruda_last_alterid','$garruda_last_transdate','$garruda_last_stockdate','$status','$remark')";
					mysqli_query($link, $sql);
					addUpdateTallyLog($link, $distributor_id, "", "", "", "", "");
					echo "success";
				} else {
					echo "Invalid Id Or Password!";
				}
				break;

			case "ROLLBACK ACKNOWLEDGEMENT":
				$SEAPBILLINGCODE = $array['BODY']['DATA']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['PRINCIPALID'];
				$DATA = $array['BODY']['DATA'];
				$ROLLBACK_STATUS = $DATA['ROLLBACKSTATUS'];

				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					$q = "select * from pre_rollbacks where distributor_id = '$SEAPBILLINGCODE' order by id desc limit 1";
					$rollback = selectone($q);
					if ($rollback) {
						$rollback_id = $rollback['id'];
						$ROLLBACK_STATUS = 'COMPLETED';
						$currentTime = date('Y-m-d H:i:s');
						$sql = "UPDATE pre_rollbacks SET rollback_status = '$ROLLBACK_STATUS', rollback_completed_on='$currentTime' where id='$rollback_id'";
						mysqli_query($link, $sql);
					}
					$ROLLBACKSTATUS = $DATA['ROLLBACKSTATUS'];
					$sql = "UPDATE pre_distributors SET initiate_rollback = 'NO' where id='$SEAPBILLINGCODE'";
					mysqli_query($link, $sql);
					echo "success";
				} else {
					echo "Invalid Id Or Password!";
				}
				break;
			case "VALIDATE PRODUCT REQUEST":

				$SEAPBILLINGCODE = $array['BODY']['DATA']['VALIDATION']['SEAPBILLINGCODE'];
				$PASSWORD = $array['BODY']['DATA']['VALIDATION']['PASSWORD'];
				$PRINCIPALID = $array['BODY']['DATA']['VALIDATION']['PRINCIPALID'];
				$DATA = $array['BODY']['DATA']['REQUESTDATA'];

				$q = "select * from pre_distributors where pid = '$PRINCIPALID' and password = '$PASSWORD' and id='$SEAPBILLINGCODE'";
				$rows = selectone($q);
				if ($rows) {
					//var_dump(is_array($DATA));die;
					foreach ($DATA as $pKey => $productData) {

						$product_code = isset($productData['PRODUCTCODE']) ? $productData['PRODUCTCODE'] : $productData;
						$q = "select * from pre_product_management where '$product_code' IN (`id`, `sfield6`, `sfield7`, `sfield8`, `sfield9`)";
						$products = selectone($q);
						if ($products) {
							if (isset($productData['PRODUCTCODE'])) {
								$array['BODY']['DATA']['REQUESTDATA'][$pKey]['STATUS'] = 'Product Code Found';
								$array['BODY']['DATA']['REQUESTDATA'][$pKey]['PRODUCTCODE'] = $product_code;
								$array['BODY']['DATA']['REQUESTDATA'][$pKey]['PRODUCTPRIMARYCODE'] = $products['id'];
							} else {
								$array['BODY']['DATA']['REQUESTDATA']['STATUS'] = 'Product Code Found';
								$array['BODY']['DATA']['REQUESTDATA']['PRODUCTCODE'] = $product_code;
								$array['BODY']['DATA']['REQUESTDATA']['PRODUCTPRIMARYCODE'] = $products['id'];
							}
						} else {
							$array['BODY']['DATA']['REQUESTDATA'][$pKey]['STATUS'] = 'Product Code Not Found';
							$array['BODY']['DATA']['REQUESTDATA'][$pKey]['PRODUCTCODE'] = $product_code;
							$array['BODY']['DATA']['REQUESTDATA'][$pKey]['PRODUCTPRIMARYCODE'] = '';
						}
					}



					$result['DATA'] = $array['BODY']['DATA'];
					header("Content-type: text/xml");
					$xml = Array2XML::createXML('ENVELOPE', $result);
					$output = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml->saveXML());
					echo $output;
				} else {
					echo "Invalid Id Or Password!";
				}
				break;
				/* case "Purchase Request":
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
			break; */
				//----
				/* case "district":
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
				break; */
				///-----


			default:
				echo "Invalid XML Request";
				break;
		}
	}
} catch (Exception $e) {
	Logger::log("ERROR : LINE NO - " . $e->getLine() . " | " . $e->getMessage());
}

function addUpdateTallyLog($link, $distributorId, $tally_license = null, $tally_version = null, $tally_release = null, $tss_expiry_date, $tcp_version)
{
	$distQuery = "SELECT pid,rid,id FROM pre_distributors where id = '$distributorId'";
	$distributor = selectone($distQuery);
	$pid = $distributor['pid'];
	$rid = $distributor['rid'];
	if ($distributor) {
		$rollbackQuery = "SELECT id FROM pre_rollbacks where distributor_id = '$distributorId'";
		$rollback = selectone($rollbackQuery);
		if ($rollback) {
			$sql = "UPDATE `pre_tally_logs` SET `tally_license` = '$tally_license', `tally_version`='$tally_version', `tally_release`='$tally_release',`tss_expiry_date` = '$tss_expiry_date', `tcp_version`='$tcp_version' WHERE distributor_id = '$distributorId'";
			mysqli_query($link, $sql);
		} else {
			$sql = "INSERT INTO `pre_tally_logs`(`pid`, `rid`, `distributor_id`, `tally_license`, `tally_version`, `tally_release`, 		`tss_expiry_date`, `tcp_version`) VALUES 	('$pid','$rid','$distributorId','$tally_license','$tally_version','$tally_release','$tss_expiry_date','$tcp_version')";
			mysqli_query($link, $sql);
		}
	}
}

function updateClosingStock($link, $id)
{
	$sql = "UPDATE pre_stockreports SET closing_stock = openqty + 	
	(ABS(purchase) - (purchase_reversed) - (purchase_removed))
	- ((debit_note) - ABS(debit_note_reversed) - ABS(debit_note_removed))
	- ((sales) - ABS(sales_reversed) - ABS(sales_removed)) 
	+ (ABS(credit_note) - (credit_note_reversed) - ((credit_note_removed)))
	- ((stock_journal)  + (stock_journal_removed)) 
	where id = '$id'";
	mysqli_query($link, $sql);
}


function clearTransType($type)
{
	$type = str_replace(' ', '_', strtolower($type));
	switch ($type) {
		case "sales-dn":
			return "sales";
			break;
		case "sales-dn_removed":
			return "sales_removed";
			break;
		case "credit_note-ri":
			return "credit_note";
			break;
		case "credit_note-ri_removed":
			return "credit_note_removed";
			break;
		case "purchase-rn":
			return "purchase";
			break;
		case "purchase-rn_removed":
			return "purchase_removed";
			break;
		case "debit_note-ro":
			return "debit_note";
			break;
		case "debit_note-ro_removed":
			return "debit_note_removed";
			break;
		case "stock_journal-mo":
			return "stock_journal";
			break;
		case "stock_journal-mo_removed":
			return "stock_journal_removed";
			break;
		case "stock_journal-mi":
			return "stock_journal";
			break;
		case "stock_journal-mi_removed":
			return "stock_journal_removed";
			break;
		case "sales-jv":
			return "sales";
			break;
		case "sales-jv_removed":
			return "sales_removed";
			break;
		case "purchase-jv":
			return "purchase";
			break;
		case "purchase-jv_removed":
			return "purchase_removed";
			break;
		default:
			return $type;
			break;
	}
}
