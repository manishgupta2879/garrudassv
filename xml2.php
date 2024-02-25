<?php
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

$response ='<?xml version="1.0" encoding="UTF-8"?>
<note>
	<to>Tove</to>
	<from>Jani</from>
    <india /> 
    <pac></pac>
	<heading>Reminder</heading>
	<body>Dont forget me this weekend!</body>
</note>';

	$xml = new SimpleXMLElement($response);

	$arr = objectsIntoArray($xml);
echo '<pre>';	print_r($arr);