<?php

class Logger
{

    private $fileHandle;
    protected function __construct()
    {
        $this->fileHandle = fopen('api_error_log.log', 'a');
    }

    public function writeLog(string $message): void
    {
        $date = date('Y-m-d H:i:s');
        fwrite($this->fileHandle, "$date: $message\n");
    }

    public static function log(string $message): void
    {
        $logger = new self();
        $logger->writeLog($message);
    }
}

class Database extends Logger
{
    private static $connection;

    private function __construct()
    {
    }

    static function getInstance()
    {
        if (self::$connection === NULL) {
            try {
                self::$connection = mysqli_connect("p:localhost", "root", "", "sony_live_ssvuat");
                //Logger::log("Successfully  connected to the database.");
            } catch (Exception $e) {
                Logger::log("API Error | " . $e->getMessage());
            }
        }
        return self::$connection;
    }
}

//$link = mysqli_connect("localhost", "ssv_uat", "l8_9mOs54", "sony_live_ssvuat");
$link = Database::getInstance(); // getInstance with singleton


if (!$link) {
    die('Could not connect: ' . mysqli_error($link));
}

function my_to_array($result)
{
    global $link;
    $data = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}
//
function select($sql, $change_case = '')
{
    global $link;
    $result = mysqli_query($link, $sql);
    $data = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        if ($change_case != '') {
            $data[] = array_change_key_case($row, $change_case);
        } else {
            $data[] = $row;
        }
    }
    return $data;
}
function select_json($sql)
{
    return json_encode(select($sql));
}

function selectone($sql)
{
    global $link;
    $result = mysqli_query($link, $sql);
    $data = array();
    if ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        return $row;
    }
}

function countRecords($sql)
{
    global $link;
    $result = mysqli_query($link, $sql);
    if ($result) {
        $row = mysqli_fetch_array($result, MYSQLI_NUM);
        return $row[0];
    } else {
        return 0;
    }
}

function insert($tablename, $insData)
{
    global $link;
    $columns = implode(", ", array_keys($insData));
    $escaped_values = array_values($insData);
    $value = implode("','", $escaped_values);
    //$values=str_replace(",","','",$value);

    $sql = "INSERT INTO " . $tablename . " ($columns) VALUES ('$value')";
    if (mysqli_query($link, $sql)) {
        return mysqli_insert_id($link);
    } else {
        print_r(mysqli_error($link));
        die;
        return false;
    }
}

function update_distributor_status($id, $ROLLOUTDATE, $PASSWORD, $TALLYSERIALNO, $TALLYRELEASE, $TCPVERSION, $TSSEXPIRY, $TALLYVERSION)
{
    global $link;
    $sql = "UPDATE pre_distributors set status = 'Active', roll_out_date ='$ROLLOUTDATE',password ='$PASSWORD',tally_serial_no='$TALLYSERIALNO',tally_release='$TALLYRELEASE',tcp_version='$TCPVERSION',tss_expiry='$TSSEXPIRY',tally_version='$TALLYVERSION' WHERE id = '$id'";
    mysqli_query($link, $sql) or die(mysqli_error());
}

function select_permpass($otp)
{
    global $link;
    $sql = "SELECT d.id as SEAPBILLINGCODE,c.otp as PASSWORD FROM pre_distributors d LEFT JOIN pre_companies c on d.rid = c.id WHERE d.id = '$otp'";
    $result = mysqli_query($link, $sql);
    $data = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

function select_attributes($attType)
{
    global $link;
    $sql = "SELECT atv.pid as PID, atv.name as NAME, at.parent as PARENT FROM  pre_attributes at JOIN pre_attribute_value atv ON at.attribute_id = atv.attribute_id WHERE at.name = '$attType'";
    $result = mysqli_query($link, $sql);
    $data = array();
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

function convertAndHandleDate($dateString, $format = "Y-m-d H:i:s")
{
    // Set the default timezone to Asia/Kolkata
    date_default_timezone_set('Asia/Kolkata');
    try {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return null;
        }
        $formattedDate = date($format, $timestamp);
        return $formattedDate;
    } catch (Exception $e) {
        return null;
    }
}
