<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$xml_data = '<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>VALIDATE PRODUCT REQUEST</TALLYREQUEST>
 </HEADER>
 <BODY>
  <DATA>
   <VALIDATION>
    <PRINCIPALID>SONY001</PRINCIPALID>
    <SEAPBILLINGCODE>1010974</SEAPBILLINGCODE>
    <PASSWORD>MNOP@9946</PASSWORD>
   </VALIDATION>
   <REQUESTDATA>
    <PRODUCTCODE>36493787</PRODUCTCODE>
   </REQUESTDATA>
  </DATA>
 </BODY>
</ENVELOPE>';
$xml_file = 'data2.xml';

$xml_data = file_get_contents($xml_file);


// $URL = "https://ssvuat.garruda.co.in/webservice/api.php";
$URL = "http://localhost/webservice_zip/api.php";
$ch = curl_init($URL);
//curl_setopt($ch, CURLOPT_MUTE, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
print_r($output);
curl_close($ch);
?>