<?php

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
		print_r($resArr);

?>