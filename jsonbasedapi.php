<?php
include "mysql_functions.php";
date_default_timezone_set('Asia/Kolkata');

$action = $_GET['action'];
$dataPOST = json_decode(trim(file_get_contents('php://input')), true);
switch($action){
    case 'ItemUpload':
        $firstRow = true;
		$stack = array();
		$sql = "SELECT id,name FROM pre_product_category";
		$cate = select($sql);
		foreach($cate as $cat){
			$category[$cat[name]] = $cat[id];
		}
		$category = array_change_key_case($category,CASE_LOWER);
		$sql = "SELECT id,name FROM pre_product_group";
		$grps = select($sql);
		foreach($grps as $grp){
			$group[$grp[name]] = $grp[id];
		}
		$group = array_change_key_case($group,CASE_LOWER);
		$sql = "SELECT id,symbol FROM pre_uom";
		$uoms = select($sql);
		foreach($uoms as $uom){
			$uomss[$uom[symbol]] = $uom[id];
		}
		$uomss =  array_change_key_case($uomss,CASE_LOWER);
		$sql = "SELECT attribute_id,name FROM pre_attribute";
		$attrs = select($sql);
		foreach($attrs as $attr){
			$attrss[$attr[name]] = $attr[attribute_id];
		}
		$attrss = array_change_key_case($attrss,CASE_LOWER);
		$i=1;
		foreach($dataPOST as $values){
			// echo "<pre>";print_r(array_values($data));die;
			$data = array_values($data);

			if($data[18]){ if(!$attrss[strtolower($data[18])]){array_push($stack,$i.'-18'); continue;}}
			if($data[19]){ if(!$attrss[strtolower($data[19])]){array_push($stack,$i.'-19'); continue;}}
			if($data[20]){ if(!$attrss[strtolower($data[20])]){array_push($stack,$i.'-20'); continue;}}
			if($data[21]){ if(!$attrss[strtolower($data[21])]){array_push($stack,$i.'-21'); continue;}}
			if($data[22]){ if(!$attrss[strtolower($data[22])]){array_push($stack,$i.'-22'); continue;}}
			if($data[23]){ if(!$attrss[strtolower($data[23])]){array_push($stack,$i.'-23'); continue;}}
			if($data[24]){ if(!$attrss[strtolower($data[24])]){array_push($stack,$i.'-24'); continue;}}
			if($data[25]){ if(!$attrss[strtolower($data[25])]){array_push($stack,$i.'-25'); continue;}}
			if($data[26]){ if(!$attrss[strtolower($data[26])]){array_push($stack,$i.'-26'); continue;}}
			if($data[27]){ if(!$attrss[strtolower($data[27])]){array_push($stack,$i.'-27'); continue;}}
			
			//print_r($category);
			//print_r($data[8]);die;
			$group_name = $group[strtolower($data[7])];
			$category_name = $category[strtolower($data[8])];
			$uom_name = $uomss[strtolower($data[12])];
			$alt_uom_name = $uomss[strtolower($data[13])];
			$third_uom_name = $uomss[strtolower($data[14])];

			if(!$group_name){array_push($stack,$i.'-7'); continue;}
			if(!$category_name){array_push($stack,$i.'-8'); continue;}
			if($data[12]){ if(!$uom_name){array_push($stack,$i.'-12'); continue;}}
			if($data[13]){ if(!$alt_uom_name){array_push($stack,$i.'-13'); continue;}}
			if($data[14]){ if(!$third_uom_name){array_push($stack,$i.'-14'); continue;}}
			
			$sql = "SELECT * FROM pre_products where id='$data[3]'";
			$products = selectone($sql);
			if($products){
				$product_name = addslashes($data[4]);
				$description = addslashes($data[5]);
				$query = "UPDATE pre_products set pid='$data[1]',rid='$data[2]',product_name='$product_name',description='$description',part_number='$data[6]',product_group='$group_name',product_category='$category_name',vat_rate='$data[9]',cst_tax='$data[10]',batchwise='$data[11]',uom='$uom_name',alt_uom='$alt_uom_name',thirduom='$third_uom_name',numerator='$data[15]',denominator='$data[16]',conversion='$data[17]',attribute1='$data[18]',attribute2='$data[19]',attribute3='$data[20]',attribute4='$data[21]',attribute5='$data[22]',attribute6='$data[23]',attribute7='$data[24]',attribute8='$data[25]',attribute9='$data[26]',attribute10='$data[27]',sfield1='$data[28]',sfield2='$data[29]',sfield3='$data[30]',sfield4='$data[31]',sfield5='$data[32]',sfield6='$data[33]',sfield7='$data[34]',sfield8='$data[35]',sfield9='$data[36]',sfield10='$data[37]',nfield1='$data[38]',nfield2='$data[39]',nfield3='$data[40]',nfield4='$data[41]',nfield5='$data[42]',dfield1='$data[43]',dfield2='$data[44]',dfield3='$data[45]',dfield4='$data[46]',dfield5='$data[47]',altered_on=NOW() where id='$data[3]'";
				//print_r($query);die;
				mysql_query($query);
			}else{
				$product_name = addslashes($data[4]);
				$description = addslashes($data[5]);
				$query = "INSERT into pre_products values('','$data[1]','$data[2]','$data[3]','$product_name','$description','$data[6]','$group_name','$category_name','$data[9]','$data[10]','$data[11]','$uom_name','$alt_uom_name','$third_uom_name','$data[15]','$data[16]','$data[17]','$data[18]','$data[19]','$data[20]','$data[21]','$data[22]','$data[23]','$data[24]','$data[25]','$data[26]','$data[27]','$data[28]','$data[29]','$data[30]','$data[31]','$data[32]','$data[33]','$data[34]','$data[35]','$data[36]','$data[37]','$data[38]','$data[39]','$data[40]','$data[41]','$data[42]','$data[43]','$data[44]','$data[45]','$data[46]','$data[47]',NOW(),NOW())";
				//print_r($query);die;
				mysql_query($query);
			}
			$i++;
		}
		return $stack;
    break;
    case 'PriceListUpload':
        $firstRow = true;
		$stack = array();
		$sql = "SELECT id,symbol FROM pre_uom";
		$uoms = select($sql);
		foreach($uoms as $uom){
			$uomss[$uom[symbol]] = $uom[id];
		}
		$uomss =  array_change_key_case($uomss,CASE_LOWER);
		
		$sql = "SELECT id,lob_name FROM pre_lob";
		$types = select($sql);
		foreach($types as $type){
			$typess[$type[lob_name]] = $type[id];
		}
		$typess =  array_change_key_case($typess,CASE_LOWER);
		
		$sql = "SELECT id,name FROM pre_company_geo_details where nature='State'";
		$states = select($sql);
		foreach($states as $state){
			$statess[$state[name]] = $state[id];
		}
		$statess =  array_change_key_case($statess,CASE_LOWER);
		foreach($uoms as $uom){
			$uomss[$uom[symbol]] = $uom[id];
		}
		$uomss =  array_change_key_case($uomss,CASE_LOWER);
		$i=0;
		foreach($dataPOST as $values){
			// echo "<pre>";print_r(array_values($data));die;
			$data = array_values($data);
			//print_r($data);die;
			//cost data
			$md = explode("/", $data[2]); // split the array
			$nd = $md[2]."-".$md[1]."-".$md[0]; // join them together
			$cost_date = date('Y-m-d', strtotime($nd));
			//price data
			$md = explode("/", $data[5]); // split the array
			$nd = $md[2]."-".$md[1]."-".$md[0]; // join them together
			$price_date = date('Y-m-d', strtotime($nd));
			//mrp data
			$md = explode("/", $data[8]); // split the array
			$nd = $md[2]."-".$md[1]."-".$md[0]; // join them together
			$mrp_date = date('Y-m-d', strtotime($nd));
			if($data[4]){ if(!$uomss[strtolower($data[4])]){array_push($stack,$i.'-4'); continue;}}
			if($data[7]){ if(!$uomss[strtolower($data[7])]){array_push($stack,$i.'-7'); continue;}}
			if($data[10]){ if(!$uomss[strtolower($data[10])]){array_push($stack,$i.'-10'); continue;}}
			if($data[17]=="State"){
				if($data[18] !='' && $data[18] !='All State'){ if(!$statess[strtolower($data[18])]){array_push($stack,$i.'-18'); continue;}}
			}
			if($data[17]=="Type"){
				if($data[18] !='' && $data[18] !='All Type'){ if(!$typess[strtolower($data[18])]){array_push($stack,$i.'-18'); continue;}}
			}
			
			$sql = "SELECT * FROM pre_products where id='$data[1]'";
			$products = selectone($sql);
			if($products){
				$sql = "SELECT * FROM pre_price_list where product_id='$data[1]' AND state='$data[18]' AND (cost_effect_date='$cost_date' OR price_effect_date='$price_date' OR mrp_effect_date='$mrp_date')";
				$products_data = selectone($sql);
				//print_r($products_data);die;
				$p_id = $products_data['id'];
				if($products_data){
						$query = "UPDATE pre_price_list set cost_effect_date='$cost_date', cost='$data[3]', cost_unit='$data[4]', price_effect_date='$price_date', price='$data[6]', price_unit='$data[7]', mrp_effect_date='$mrp_date', mrp='$data[9]', mrp_unit='$data[10]', tax_rate='$data[11]', addlvatrate='$data[12]', vatsurchargerate='$data[13]', vatcessrate='$data[14]', cstformrate='$data[15]', cstwoformrate='$data[16]',price_type='$data[17]' state='$data[18]',alter_date=NOW() where id=$p_id";
						//print_r($query);die;
						mysql_query($query);
				}
				else{
					$query = "INSERT into pre_price_list values('','$data[1]','$cost_date','$data[3]','$data[4]','$price_date','$data[6]','$data[7]','$mrp_date','$data[9]','$data[10]','$data[11]','$data[12]','$data[13]','$data[14]','$data[15]','$data[16]','$data[17]','$data[18]',now(),now())";
					//print_r($query);die;
					mysql_query($query);
				}
			}else{
				continue;
			}
			$i++;
		}
		return $stack;
    break;
    case 'PurchasesUpload':
        $error = array();
		$firstRow = true;
		$i=0;
		$voucher_no_array= array();
		$correct_voucher= array();
		foreach($dataPOST as $values){
			// echo "<pre>";print_r(array_values($data));die;
			$data = array_values($values);
			if($data[6]){
				$amount = $data[6];
			}
			
			/* for validation check */
			
			$item_name = $data[8];
			$voucher_no = $data[3];
			$dist_id = $data[2];
			$voucher_date = date("Y-m-d", strtotime($data[4]));

			$sql = "SELECT * FROM pre_distributor where id='$dist_id'";
			$dist = selectone($sql);
			if($dist =='' || $dist == null){
				array_push($voucher_no_array,$voucher_no);
				$query = "DELETE FROM pre_voucher WHERE vchno='$voucher_no'";
				mysql_query($query);
				$error[] = "Error at line number - $i. Error in Distributor Code - $dist_id";
			}
			
			if($item_name !='' || $item_name !=null){
				$sql = "SELECT * FROM pre_products where id='$item_name'";
				$products = selectone($sql);
				if($products =='' || $products == null){
					array_push($voucher_no_array,$voucher_no);
					$query = "DELETE FROM pre_voucher WHERE vchno='$voucher_no'";
					mysql_query($query);
					$error[] = "Error at line number - $i. Error in item name - $item_name";
				}
			}else{
				$sql = "SELECT * FROM pre_voucher where vchno='$voucher_no'";
				$vouchers = selectone($sql);
				if($vouchers =='' || $vouchers == null){
					array_push($voucher_no_array,$voucher_no);
					$query = "DELETE FROM pre_voucher WHERE vchno='$voucher_no'";
					mysql_query($query);
					$error[] = "Error at line number - $i. Error in voucher No - $voucher_no";
				}
			}
			
			/* for validation check end */
            $query = "INSERT into pre_voucher_temp values('','$data[1]','$data[2]','$data[3]','$voucher_date','$data[5]','$data[6]','$data[7]','$data[8]','$data[9]','$data[10]','$data[11]','$data[12]','$data[13]','$data[14]','$data[15]','$data[16]','$data[17]','$data[18]','$data[19]','$data[20]','$data[21]','$data[22]','$data[23]','$data[24]','$data[25]','$data[26]','$data[27]','$data[28]','$data[29]','$data[30]','', '')";
			
			if(!in_array($voucher_no,$voucher_no_array)){
                array_push($correct_voucher,$voucher_no);
				$query = "INSERT into pre_voucher values('','$data[1]','$data[2]','$data[3]','$voucher_date','$data[5]','$data[6]','$data[7]','$data[8]','$data[9]','$data[10]','$data[11]','$data[12]','$data[13]','$data[14]','$data[15]','$data[16]','$data[17]','$data[18]','$data[19]','$data[20]','$data[21]','$data[22]','$data[23]','$data[24]','$data[25]','$data[26]','$data[27]','$data[28]','$data[29]','$data[30]','')";
				//print_r($query);die;
				mysql_query($query);
			}else{
				// $error[] = "Error at line number - $i. Error in voucher No - $voucher_no and item name - $item_name";
			}
			$i++;
		}
        $correct_voucher = array_unique($correct_voucher);
        $voucher_no_array = array_unique($voucher_no_array);
        
		return $error;
    break;
    default:
        echo json_encode(['result'=>false, 'message'=>'Action Not Found']);
    break;
}

// $pass = selectone("SELECT * FROM pre_company WHERE pid = '$PRINCIPALID' and otp = '$OTP'");
// $distributor_varification = selectone("SELECT * FROM pre_distributor WHERE id ='$BILLINGCODE'");
// if ($pass && $distributor_varification) {}
