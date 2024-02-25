<?php
include "mysql_functions.php";

$page = $_GET['page'];

$per_page = 10000;

$limitfrom= ($page*$per_page-$per_page);

//and month(docdate)=12

$sql = "SELECT `disti_code`,`docno`,`docdate`, `trntype`,`stockno`,stockname,SUM(`stkqty`) as qty FROM `pre_transactions` WHERE `stkqty` !=''  and  month(dfield1)=4 and year(dfield1)=2023 and trntype in ('purchase','purchase reversed','purchase removed') GROUP BY `disti_code`, `docdate`,`stockno`,stockname,`trntype` ORDER BY id ASC";

//echo "ssss";

$results = select($sql);



//echo "<pre>";print_r($results);die;



foreach($results as $result){

    $distId = $result['disti_code'];

    $code = $result['stockno'];
	
	$stockname = $result['stockname'];
	
	$dcdate=$result['docdate'];

    $year = date('Y', strtotime($result['docdate']));

    $month = date('m', strtotime($result['docdate']));

    try
	{


    $q="select * from pre_stockreports where distid = '$distId' AND itemcode='$code' AND YEAR(`fromdate`)='$year' AND MONTH(`fromdate`)='$month' and itemgodownname='' and itembatchname='' ORDER BY id ASC LIMIT 1";
		$stock = selectone($q);
	}
	catch(Exception $e)
		{
			
		}

    
$entqty = $result['qty'];

        $type = str_replace(' ','_',strtolower($result['trntype']));


    if($stock)
	{

        

        $stkId = $stock['id'];
		
		try
		{

        $sql = "UPDATE pre_stockreports set $type = ($type + ($entqty)) WHERE id = $stkId";

        mysqli_query($link, $sql);
		}
		catch(Exception $e)
		{
			
		}

    }
	else
	{
								
                                $stockEntry = array(
                                    'pid'       => 'CANON001',
                                    'rid'       => 'CANON001',
                                    'distid'    => $distId,
                                    'fromdate'  => date('Y-m-01', strtotime($dcdate)),
                                    'todate'    => date('Y-m-t', strtotime($dcdate)),
                                    'itemcode'  => $code,
                                    'itemname'  => $stockname,
                                    'openqty'   => 0,
                                    $type     => $entqty
                                );
                                insert($tablename = 'pre_stockreports', $stockEntry);
	}

}

die("Done");