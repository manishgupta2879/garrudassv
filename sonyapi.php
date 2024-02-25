<?php
// error_reporting(0);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

include "mysql_functions.php";
date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_SERVER['PHP_AUTH_USER'];
    $pass = $_SERVER['PHP_AUTH_PW'];
}else{
    $user = "SONY001";
    $pass = "sony@123#";
}

// $varification = selectone("SELECT * FROM pre_companies WHERE pid ='$user' and otp='$pass'");
// if(!$varification){
//     echo json_encode(['result'=>false, 'message'=>'You are not authorized to access this resource']);die;
// }
// echo "Done";die;
$action = $_GET['action'];

$json = file_get_contents('php://input');

$dataPOST = json_decode(file_get_contents('php://input'));
// file_put_contents('json.txt', $json . "\n", FILE_APPEND);
switch($action){
    case 'PurchasesUpload':
        insert($tablename = 'pre_voucher_temp', ["type"=>"PurchasesUpload", "json_data"=>json_encode($dataPOST)]);
        $errorInVoucher = array();
        $ledgerEntry = array();
        $partyName = "";
        $distId = "";
        $dataPOST->InvoiceNumber = (int)$dataPOST->InvoiceNumber;
        $dataPOST->SoldToParty = (int)$dataPOST->SoldToParty;
        $dataPOST->ShipToParty = (int)$dataPOST->ShipToParty;

        $invoice = selectone("SELECT * FROM pre_vouchers where vchno='$dataPOST->InvoiceNumber'");
        if($invoice && $dataPOST->IsCancelled ==''){
            echo json_encode(['status'=>'fail', 'message'=>'Duplicate Invoice Uploaded', 'InvoiceID'=>$dataPOST->InvoiceID]);die;
        }

        $distributor = selectone("SELECT * FROM pre_distributors where {$dataPOST->SoldToParty} IN (`id`, `sfield1`, `sfield2`, `sfield3`, `sfield4`, `sfield5`)");
        // print_r($distributor);die;
        if($distributor){
            $distId = $distributor['id'];
            $comId = $distributor['company'];
            $com = selectone("SELECT * FROM pre_companies where id=$comId");
            if($com){
                $partyName = $com['com_id'];
            }
        }
        
        foreach($dataPOST->InvoiceDetails as $details){
			$errors = array();
            $rowError = [];
            // print_r($details);die;
            $error = '';
            $product = array();
            $itemName = '';
            $isEditable = 0;
            $productArray = array();
            $updatesql = "";
            # check Item
            if($details->MaterialCode !=''){
				
                $productID = (int)$details->MaterialCode;
                // $productName = $details->MaterialName;
                $productName = "SONY"." ". $details->BasicMaterial ." ". $details->Color ." ". $details->IndustryStdDesc;
                $eanCode = $details->EANCode;

                $product = selectone("SELECT * FROM pre_product_management where product_name LIKE '$productName'");
                
                if(!$product){
                    $product = selectone("SELECT * FROM pre_product_management where id=$productID");
                }
                if($product){
					//Update CGST AND SGST FIELDS
					$igst = $details->IGSTPercentage>0?$details->IGSTPercentage:$details->CGSTPercentage*2;
					$cgst = $details->CGSTPercentage>0?$details->CGSTPercentage:$details->IGSTPercentage/2;
					$sgst = $details->CGSTPercentage>0?$details->CGSTPercentage:$details->IGSTPercentage/2;	
					if($igst > 0 && $igst != $product['nfield2']){
						$fincialYear = getFinancialYear();						
						$nfield4 = isset($details->UTGSTPercentage) && $details->UTGSTPercentage?$details->UTGSTPercentage:$details->SGSTPercentage;
						$sql = "UPDATE pre_product_management 
                                SET nfield2 = '" .$igst . "',
                                    nfield3 = '" . $cgst . "',
                                    nfield4 = '" . $sgst. "',
                                    nfield5 = '" . $details->CessPercentage . "',
                                    dfield5 = '" . $fincialYear . "'
                                    WHERE id = " . $product['id'];
								mysqli_query($link, $sql);
					}
					
					//Update CGST AND SGST FIELDS
                    if($productID != $product['id']){
						
                        $matchsfield = selectone("SELECT * FROM pre_product_management where $productID IN (`sfield6`, `sfield7`, `sfield8`, `sfield9`)");
                        if(!$matchsfield){
                            if(!$matchsfield['sfield6']){
                                $sql = "UPDATE pre_product_management SET sfield6=$productID WHERE id='{$product['id']}'";
                                mysqli_query($link, $sql);
                            }elseif(!$matchsfield['sfield7']){
                                $sql = "UPDATE pre_product_management SET sfield7=$productID WHERE id='{$product['id']}'";
                                mysqli_query($link, $sql);
                            }elseif(!$matchsfield['sfield8']){
                                $sql = "UPDATE pre_product_management SET sfield8=$productID WHERE id='{$product['id']}'";
                                mysqli_query($link, $sql);
                            }elseif(!$matchsfield['sfield9']){
                                $sql = "UPDATE pre_product_management SET sfield9=$productID WHERE id='{$product['id']}'";
                                mysqli_query($link, $sql);
                            }
                        }
                    }
                    $matchsfield = selectone("SELECT * FROM pre_product_management where $eanCode IN (`sfield1`, `sfield2`, `sfield3`, `sfield4`, `sfield5`)");
                    if(!$matchsfield){
                        if(!$matchsfield['sfield1']){
                            $sql = "UPDATE pre_product_management SET sfield1=$eanCode WHERE id='{$product['id']}'";
                            mysqli_query($link, $sql);
                        }elseif(!$matchsfield['sfield2']){
                            $sql = "UPDATE pre_product_management SET sfield2=$eanCode WHERE id='{$product['id']}'";
                            mysqli_query($link, $sql);
                        }elseif(!$matchsfield['sfield3']){
                            $sql = "UPDATE pre_product_management SET sfield3=$eanCode WHERE id='{$product['id']}'";
                            mysqli_query($link, $sql);
                        }elseif(!$matchsfield['sfield4']){
                            $sql = "UPDATE pre_product_management SET sfield4=$eanCode WHERE id='{$product['id']}'";
                            mysqli_query($link, $sql);
                        }elseif(!$matchsfield['sfield5']){
                            $sql = "UPDATE pre_product_management SET sfield5=$eanCode WHERE id='{$product['id']}'";
                            mysqli_query($link, $sql);
                        }
                    }
                }
                if(!$product){
                    $group = selectone("SELECT * FROM pre_product_groups WHERE name='$details->MaterialGroupDesc'");
                    if(!$group){
                        //$error = "Group Not Found";
                        //$rowError[] = "Group Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        $isEditable = 1;
                    }
                    $category = selectone("SELECT * FROM pre_product_categories WHERE name='Sony'");
                    if(!$category){
                       // $error = "Category Not Found";
                        //$rowError[] = "Category Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        $isEditable = 1;
                    }
                    $uom = selectone("SELECT * FROM pre_uoms WHERE symbol='Pcs'");
                    if(!$uom){
                      //  $error = "UOM Not Found";
                       // $rowError[] = "UOM Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        $isEditable = 1;
                    }
                    $attr1 = selectone("SELECT * FROM `pre_attributes` WHERE `name` LIKE '{$details->DivisionDesc}' AND `pid`='SONY001' AND `parent_name` LIKE 'Item'");
                    if(!$attr1){
                     //   $error = "Attribute1 Not Found";
                       // $rowError[] = "Attribute1 Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        //$isEditable = 1;
                    }
                    $attr2 = selectone("SELECT * FROM `pre_attributes` WHERE `name` LIKE '{$details->ExtMaterialGroup}' AND `pid`='SONY001' AND `parent_name` LIKE 'Item'");
                    if(!$attr2){
                       // $error = "Attribute2 Not Found";
                        //$rowError[] = "Attribute2 Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        //$isEditable = 1;
                    }
                    $attr3 = selectone("SELECT * FROM `pre_attributes` WHERE `name` LIKE '{$details->MaterialGroup}' AND `pid`='SONY001' AND `parent_name` LIKE 'Item'");
                    if(!$attr3){
                        //$error = "Attribute3 Not Found";
                        //$rowError[] = "Attribute3 Not Found";
                        //$errors[$dataPOST->InvoiceNumber][] = $error;
                        $isEditable = 1;
                    }

                    $nfield4 = ($details->UTGSTPercentage)?$details->UTGSTPercentage:$details->SGSTPercentage;
					$igst = $details->IGSTPercentage>0?$details->IGSTPercentage:$details->CGSTPercentage*2;
					$cgst = $details->CGSTPercentage>0?$details->CGSTPercentage:$details->IGSTPercentage/2;
					$sgst = $details->CGSTPercentage>0?$details->CGSTPercentage:$details->IGSTPercentage/2;	
                    $productArray = array(
                        'pid'               => $varification['pid'],
                        'rid'               => $varification['rid'],
                        'id'                => $productID,
                        'product_name'      => $productName,
                        'description'       => $productName,
                        'product_group'     => ($group['id'])?$group['id']:$details->MaterialGroupDesc,
                        'product_category'  => $category['id'],
                        'batchwise'         => 'NO',
                        'uom'               => $uom['id'],
                        'attribute1'        => $details->DivisionDesc,
                        'attribute2'        => $details->ExtMaterialGroup,
                        'attribute3'        => $details->MaterialGroup,
                        'sfield1'           => $details->EANCode,
                        'sfield10'          => $details->HSNCode,
                        'nfield2'           => $igst,
                        'nfield3'           => $cgst,
                        'nfield4'           => $sgst, //$nfield4
                        'nfield5'           => $details->CessPercentage,
                    );
                    // print_r($productArray);die;
                    //if($isEditable===0){
                        $insertedId = insert($tablename = 'pre_product_management_sap', $productArray);
                        if(!$insertedId){
                            $postError[] = "Product Not created: $productID";
                        }
                       // $product = selectone("SELECT * FROM pre_product_management where product_id=$insertedId");
                    //}
					$error = "Ssv Item Missing";
                    $errors[$dataPOST->InvoiceNumber][] = $error;
                    $isEditable = 1;
                }
                if($product){
                    $itemName = $product['id'];
                }
                if($dataPOST->BillingType=='L'){
                    $salestaxprecentage = $details->CGSTPercentage+$details->SGSTPercentage+$details->UTGSTPercentage;
                }else{
                    $salestaxprecentage = $details->IGSTPercentage;
                }

                $voucherArray = array(
                    'pid'               => $varification['pid'],
                    'dist_id'           => $distId,
                    'vchno'             => $dataPOST->InvoiceNumber,
                    'vchdate'           => date('Y-m-d', strtotime($dataPOST->BillingDate)),
                    'partyname'         => $partyName,
                    'amount'            => $dataPOST->NetValue+$dataPOST->TaxValue,
                    'vouchertype'       => 'PURCHASE',
                    'item_name'         => $productID,
                    'item_qty'          => $details->NetQty,
                    'alt_quantity'      => $details->NetQty,
                    'rate'              => number_format($details->NetValue/$details->NetQty,2),
                    'salesledgername'   => 'PURCHASE',
                    'salestaxprecentage'=> $salestaxprecentage,
                    'salesamount'       => $details->NetValue,
                    'req_dist_id'       => $dataPOST->SoldToParty,
                    'req_item_id'       => $productID,
                    'editable'          => $isEditable,
                    'posted'            => implode(',', $rowError),
                    'json_data'         => json_encode($details)
                );
                // print_r($voucherArray);die;
                $insertedId = insert($tablename = 'pre_vouchers', $voucherArray);
                if(!$insertedId){
                    $postError[] = "Voucher Not created: $dataPOST->InvoiceNumber";
                }

                if($details->IGSTPercentage > 0){
                    $ledgerEntry['IGST_PAY_'.$details->IGSTPercentage]['amount'][] = $details->IGSTAmount;
                    $ledgerEntry['IGST_PAY_'.$details->IGSTPercentage]['percentage'] = $details->IGSTPercentage;
                }else{
                    if($details->UTGSTPercentage > 0){
                        $ledgerEntry['UTGST_PAY_'.$details->UTGSTPercentage]['amount'][] = $details->UGSTAmount;
                        $ledgerEntry['UTGST_PAY_'.$details->UTGSTPercentage]['percentage'] = $details->UTGSTPercentage;
                    }else{
                        $ledgerEntry['SGST_PAY_'.$details->SGSTPercentage]['amount'][] = $details->SGSTAmount;
                        $ledgerEntry['SGST_PAY_'.$details->SGSTPercentage]['percentage'] = $details->SGSTPercentage;
                    }
                    $ledgerEntry['CGST_PAY_'.$details->CGSTPercentage]['amount'][] = $details->CGSTAmount;
                    $ledgerEntry['CGST_PAY_'.$details->CGSTPercentage]['percentage'] = $details->CGSTPercentage;
                    
                }
                //if($details->TCSPercentage > 0){
                    $ledgerEntry['TCS_PAY_'.$details->TCSPercentage]['amount'][] = $details->TCSAmount;
                    $ledgerEntry['TCS_PAY_'.$details->TCSPercentage]['percentage'] = $details->TCSPercentage;
                //}
                if($details->CessPercentage > 0){
                    $ledgerEntry['Cess_PAY_'.$details->CessPercentage]['amount'][] = $details->CessAmount;
                    $ledgerEntry['Cess_PAY_'.$details->CessPercentage]['percentage'] = $details->CessPercentage;
                }

            }else{
                $postError[] = "MaterialCode Missing";
            }
        }
        // echo "<pre>";print_r($ledgerEntry);die;
        foreach($ledgerEntry as $key=>$entry){
            $voucherArray = array(
                'pid'               => $varification['pid'],
                'dist_id'           => $distId,
                'vchno'             => $dataPOST->InvoiceNumber,
                'vchdate'           => date('Y-m-d', strtotime($dataPOST->BillingDate)),
                'partyname'         => $partyName,
                'amount'            => $dataPOST->NetValue+$dataPOST->TaxValue,
                'vouchertype'       => 'PURCHASE',
                'rate'              => "0.00",
                'salesledgername'   => $key.'%',
                'salestaxprecentage'=> $entry['percentage'],
                'salesamount'       => array_sum($entry['amount']),
                'req_dist_id'       => $dataPOST->SoldToParty,
            );
            $insertedId = insert($tablename = 'pre_vouchers', $voucherArray);
            if(!$insertedId){
                $postError[] = "ledgerEntry Not created: $dataPOST->InvoiceNumber";
            }
        }
        if($errors){
            foreach($errors as $key => $values){
                $err = implode(',', $values);
                $sql = "UPDATE pre_vouchers SET editable=1, posted= CONCAT(posted, ',{$err}') WHERE vchno='{$key}'";
                mysqli_query($link, $sql);
            }
        }
        if(!$distributor){
            $sql = "UPDATE pre_vouchers SET editable=1, posted= CONCAT(posted, ',Distributor Not Found') WHERE vchno='{$dataPOST->InvoiceNumber}'";
            mysqli_query($link, $sql);
        }
        if($dataPOST->IsCancelled !=''){
            $sql = "UPDATE pre_vouchers SET is_cancel=1, posted='canceled' WHERE vchno='{$dataPOST->InvoiceNumber}'";
            mysqli_query($link, $sql);
        }
        echo json_encode(['status'=>'success', 'message'=>'Invoice Uploaded', 'InvoiceID'=>$dataPOST->InvoiceID, 'error'=>$postError]);die;
    break;
    /* 
    {
        "SoldToParty": "1009618",
        "SubDealerCode": "W08-106367",
        "TransactionCtrlNo": "56814",
        "DocDate": "20220512",
        "TransDate": "20220602",
        "EAN": "4548736115576",
        "MaterialCode": "12442301",
        "InQty": "5",
        "OutQty": "3",
        "TransactionType": "2100",
        "Warehouse": "1009618-BACK GODOWN",
        "ShipToParty": "1009618"
    }
    */
    case 'DailyTransaction':
        
		//$sqlid = "SELECT t.id FROM `pre_transactions` t WHERE t.batchqty IS NOT NULL AND t.batchqty !='' AND t.bw_posted IS NULL ORDER BY t.id ASC";
		$sqlid = "SELECT t.id FROM `pre_transactions` t WHERE t.batchqty IS NOT NULL AND t.batchqty !='' AND (t.bw_posted IS NULL or t.bw_posted=0) ORDER BY t.id ASC";
		
		 $resultdata = mysqli_query($link, $sqlid);
        // print_r($result);
        $rowcountdata=mysqli_num_rows($resultdata);
        if($rowcountdata <= 0){
            return false;
        }
		$i=0;
		$data = array();
		$ids_id = array();
		foreach ($resultdata as $rowdata) 
		{
             $data[$i] = $rowdata;
			 $ids_id[] = $data[$i]['id'];
			 $i++;
		}
		
		$idStr1 = implode(',',$ids_id);
        $sql="UPDATE pre_transactions set bw_posted=0 WHERE id IN ($idStr1)";
        mysqli_query($link, $sql);
		
		//$sql = "SELECT t.id, t.disti_code as SoldToParty, t.partycode as SubDealerCode, t.masterid as TransactionCtrlNo, DATE_FORMAT(t.docdate, '%Y%m%d') as DocDate, DATE_FORMAT(t.sync_date, '%Y%m%d') as TransDate, (SELECT sfield1 FROM pre_products WHERE id=t.stockno) as EAN, t.stockno as MaterialCode, CONCAT(t.disti_code, '-',t.godownname) as Warehouse, t.disti_code as ShipToParty, t.trntype, t.batchqty, t.docno, t.alterid FROM `pre_transactions` t WHERE t.batchqty IS NOT NULL AND t.batchqty !='' AND t.bw_posted IS NULL ORDER BY t.id ASC";
		
			
		$sql = "SELECT t.id,t.disti_code as SoldToParty, t.partycode as SubDealerCode, t.masterid as TransactionCtrlNo, DATE_FORMAT(t.docdate, '%Y%m%d') as DocDate, DATE_FORMAT(t.sync_date, '%Y%m%d') as TransDate, (SELECT sfield1 FROM pre_product_management WHERE id=t.stockno) as EAN, t.stockno as MaterialCode, CONCAT(t.disti_code, '-',t.godownname) as Warehouse, t.disti_code as ShipToParty, t.trntype, sum(t.batchqty) as batchqty, t.docno, t.alterid FROM `pre_transactions` t WHERE t.batchqty IS NOT NULL AND t.batchqty !='' AND t.bw_posted =0 group by t.disti_code, t.partycode,t.docdate,t.stockno, t.trntype, t.batchqty, t.docno ORDER BY t.id ASC";
		
		
		
        $result = mysqli_query($link, $sql);
        // print_r($result);
        $rowcount=mysqli_num_rows($result);
        if($rowcount <= 0){
            return false;
        }
        $finalData = [];
        $findData = [];
        $ids = array();
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $compare = [
                'SoldToParty' => $row['SoldToParty'],
                'TransactionCtrlNo' => $row['TransactionCtrlNo'],
                'MaterialCode' => $row['MaterialCode'],
                'trntype' => $row['trntype'],
                'docno' => $row['docno'],
                'alterid' => $row['alterid'],
                'Warehouse'=> $row['Warehouse']
            ];
            $key = array_keys($findData, $compare);
            if($key){
                $upKey = $key[0];
                $finalData[$upKey]['batchqty'] = $finalData[$upKey]['batchqty'] + $row['batchqty'];
            }else{
                $finalData[] = $row;
                $findData[] = $compare;
            }
            $ids[] = $row['id'];
        }
        // echo "<pre>";print_r($finalData);die;
        
        $data = array();
        $i=0;
        foreach ($finalData as $row) {
             $data[$i] = $row;
            switch ($row['trntype']) {
                case 'Sales':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = abs($row['batchqty']);
                    $data[$i]['TransactionType'] = 2100;
                    break;
                case 'Sales Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2100;
                    break;					
                case 'Sales Reversed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2100;
                    break;
				case 'Sales-DN':
					$data[$i]['InQty'] = 0;
					$data[$i]['OutQty'] = abs($row['batchqty']);
					$data[$i]['TransactionType'] = 2100;
					break;	
				case 'Sales-DN Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2100;
                    break;	
				case 'Sales-JV':
					$data[$i]['InQty'] = 0;
					$data[$i]['OutQty'] = abs($row['batchqty']);
					$data[$i]['TransactionType'] = 2100;
					break;	
				case 'Sales-JV Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2100;
                    break;		
                case 'Credit Note':
                    $data[$i]['InQty'] = abs($row['batchqty']);
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1300;
                    break;
                case 'Credit Note Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1300;
                    break;
                case 'Credit Note Reversed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1300;
                    break;
				case 'Credit Note-RI':
                    $data[$i]['InQty'] = abs($row['batchqty']);
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1300;
                    break;
                case 'Credit Note-RI Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1300;
                    break;	
                case 'Purchase':
                    $data[$i]['InQty'] = abs($row['batchqty']);
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;
                case 'Purchase Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;
                case 'Purchase Reversed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;
				case 'Purchase-RN':
                    $data[$i]['InQty'] = abs($row['batchqty']);
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;
                case 'Purchase-RN Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;	
				case 'Purchase-JV':
                    $data[$i]['InQty'] = abs($row['batchqty']);
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;
                case 'Purchase-JV Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1100;
                    break;		
                case 'Debit Note':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = abs($row['batchqty']);
                    $data[$i]['TransactionType'] = 2300;
                    break;
                case 'Debit Note Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2300;
                    break;
                case 'Debit Note Reversed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2300;
                    break;
				case 'Debit Note-RO':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = abs($row['batchqty']);
                    $data[$i]['TransactionType'] = 2300;
                    break;
                case 'Debit Note-RO Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2300;
                    break;	
                case 'Stock Journal':
                    if($row['batchqty'] > 0){
                        $data[$i]['InQty'] = 0;
                        $data[$i]['OutQty'] = abs($row['batchqty']);
                        $data[$i]['TransactionType'] = 2200;
                    }elseif($row['batchqty'] < 0){
                        $data[$i]['InQty'] = abs($row['batchqty']);
                        $data[$i]['OutQty'] = 0;
                        $data[$i]['TransactionType'] = 1200;
                    }
                    break;
				case 'Stock Journal-MO':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = abs($row['batchqty']);
                    $data[$i]['TransactionType'] = 2200;
                    break;
                case 'Stock Journal-MO Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 2200;
                    break;
				case 'Stock Journal-MI':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = abs($row['batchqty']);
                    $data[$i]['TransactionType'] = 1200;
                    break;
                case 'Stock Journal-MI Removed':
                    $data[$i]['InQty'] = 0;
                    $data[$i]['OutQty'] = 0;
                    $data[$i]['TransactionType'] = 1200;
                    break;	
                default:
                    $data[$i]['InQty'] = "";
                    $data[$i]['OutQty'] = "";
                    $data[$i]['TransactionType'] = "";
                    break;
            }
            $ids[] = $data[$i]['id'];
            unset($data[$i]['trntype']);
            unset($data[$i]['stkqty']);
            unset($data[$i]['id']);
            unset($data[$i]['docno']);
            unset($data[$i]['alterid']);
            $i++;
        }
       
        // print_r($ids);die;

        $payload = json_encode($data);

        $ch = curl_init('https://piwebsvcuat.sony.co.in:4444/RESTAdapter/DailyTransactions');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Authorization: Basic WlBJX0dBUlVEQTpJTklUc29ueWluZGlhQEAyMzE='
            )
        );
        // curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo $error_msg = curl_error($ch);
        }
        curl_close($ch);
        $resArr = json_decode($response);
        if($resArr->MessageStatus=="Data updated successfully"){
            $sql="UPDATE pre_transactions set bw_posted=1 WHERE bw_posted=0";
            mysqli_query($link, $sql);
        }else{
            $sql="UPDATE pre_transactions set bw_posted=NULL WHERE bw_posted=0";
            mysqli_query($link, $sql);
        }
        // var_dump($resArr->MessageStatus);
        print_r($resArr);
    break;
    case 'CustomerMaster':
        $sql = "SELECT d.dis_id, t.disti_code as SoldToParty, t.partycode as SubDealerCode, t.partyname as SubDealerName, d.tinno as TIN_GSTIN_Number, (SELECT name FROM pre_companygeodetails WHERE id=d.City) as City, (SELECT name FROM pre_companygeodetails WHERE id=d.District) as District, (SELECT name FROM pre_companygeodetails WHERE id=d.State) as `State`, '' as 'AMBOCode' FROM `pre_transactions` t LEFT JOIN `pre_distributors` d ON (d.id=t.disti_code) WHERE t.bw_posted IS NULL GROUP BY t.partycode ORDER BY t.id ASC";
        $results = select($sql);
        // print_r($results);die;
        if(!$results){
            return false;
        }
        $data = array();
        $ids = array();
        $i = 0;
        foreach($results as $result){
            $data[$i] = $result;
            $ids[] = $data[$i]['dis_id'];
            unset($data[$i]['dis_id']);
            $i++;
        }
        $idStr = implode(',',$ids);
        $sql="UPDATE pre_distributors set bw_posted=0 WHERE dis_id IN ($idStr)";
        mysqli_query($link, $sql);
        // print_r($data);die;
        $payload = json_encode($data);

        $ch = curl_init('https://piwebsvcuat.sony.co.in:4444/RESTAdapter/CustomerMaster');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Authorization: Basic WlBJX0dBUlVEQTpJTklUc29ueWluZGlhQEAyMzE='
            )
        );
        // curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo $error_msg = curl_error($ch);
        }
        curl_close($ch);
        $resArr = json_decode($response);
        if($resArr->MessageStatus=="Data updated successfully"){
            $sql="UPDATE pre_distributors set bw_posted=1 WHERE dis_id IN ($idStr)";
            mysqli_query($link, $sql);
        }else{
            $sql="UPDATE pre_distributors set bw_posted=NULL WHERE dis_id IN ($idStr)";
            mysqli_query($link, $sql);
        }
        print_r($resArr);
    break;
    case 'CustomerText':
        $sql = "SELECT t.partycode as SubDealerCode, 'EN' as `Language`, t.partyname as SubDealerName FROM `pre_transactions` t WHERE t.bw_posted IS NULL GROUP BY t.partycode ORDER BY t.id ASC";
        $results = select($sql);
        $payload = json_encode($results, JSON_UNESCAPED_SLASHES);

        $ch = curl_init('https://piwebsvcuat.sony.co.in:4444/RESTAdapter/CustomerText');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Authorization: Basic WlBJX0dBUlVEQTpJTklUc29ueWluZGlhQEAyMzE='
            )
        );
        // curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo $error_msg = curl_error($ch);
        }
        curl_close($ch);
        $resArr = json_decode($response);
        print_r($resArr);
    break;
    case 'OpeningStock':
        $sql = "SELECT srn.id, srn.distid as SoldToParty, DATE_FORMAT(srn.fromdate, '%Y%m%d') as StockDate, srn.itemcode as MaterialCode, pp.sfield1 as EAN, srn.batchopenqty as StockQty, CONCAT(srn.distid, '-',srn.itemgodownname) as Warehouse, srn.distid as ShipToParty FROM `pre_stockreports` srn LEFT JOIN pre_product_management pp ON (srn.itemcode=pp.id) WHERE srn.batchopenqty IS NOT NULL AND srn.batchopenqty !='' AND srn.bw_posted IS NULL ORDER BY srn.id ASC  LIMIT 4000";
        $results = select($sql);
        if(!$results){
            return false;
        }
        $data = array();
        $ids = array();
        $i = 0;
        foreach($results as $result){
            $data[$i] = $result;
            $ids[] = $data[$i]['id'];
            unset($data[$i]['id']);
            $i++;
        }
        $idStr = implode(',',$ids);
        $sql="UPDATE pre_stockreports set bw_posted=0 WHERE id IN ($idStr)";
        mysqli_query($link, $sql);
        // print_r($data);die;

        $payload = json_encode($data);

        $ch = curl_init('https://piwebsvcuat.sony.co.in:4444/RESTAdapter/OpeningStock');
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:application/json',
                'Authorization: Basic WlBJX0dBUlVEQTpJTklUc29ueWluZGlhQEAyMzE='
            )
        );
        // curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            echo $error_msg = curl_error($ch);
        }
        curl_close($ch);
        $resArr = json_decode($response);
        if($resArr->MessageStatus=="Data updated successfully"){
            $sql="UPDATE pre_stockreports set bw_posted=1 WHERE id IN ($idStr)";
            mysqli_query($link, $sql);
        }else{
            $sql="UPDATE pre_stockreports set bw_posted=NULL WHERE id IN ($idStr)";
            mysqli_query($link, $sql);
        }
        print_r($resArr);
    break;
    default:
        echo json_encode(['status'=>'error', 'message'=>'Action Not Found']);die;
    break;
}

function getFinancialYear() {
    if (date('n') < 4) {
        return (date('Y') - 1) . '-04-01';
    } else {
        return date('Y') . '-04-01';
    }
}