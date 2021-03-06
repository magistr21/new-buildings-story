<?php
/**
 * Exports some data from DB to JSON files. Started by cron once or twice a day.
 * Time&dates are saved and operated only in UTC timezone. That means that all
 * snapshots are saved between 2am and 9pm UTC, 6am - next day 1am in Moscow UTC+4,
 * 7pm - next day 2pm in SF UTC-7.
 *
 * PHP Version 5
 *
 * @category File
 * @package  Crawler
 * @author   Malitsky Alexander <a.malitsky@gmail.com>
 * @license  GNU GENERAL PUBLIC LICENSE
 *
 */
$time_start = microtime(true);
ob_start();
date_default_timezone_set("UTC");
$nbsCrConf = array();
error_reporting(E_ALL);
require_once dirname(__FILE__)."/../nbs_conf.php";
require_once dirname(__FILE__)."/../db.php";
require_once dirname(__FILE__)."/../output.php";

echo "<p>Export run on ".date("Y-m-d H:i:s T").".</p>";

$db = mysqli_init();
$db -> real_connect($nbsCrConf['dbServer'], $nbsCrConf['dbLogin'], $nbsCrConf['dbPassword'], $nbsCrConf['dbName']);
if ($db -> connect_errno) {
    echo "<p>Error: Failed to connect to MySQL: (".$db->connect_errno.") ".$db->connect_error ."</p>\r\n"; }
$db -> query("SET time_zone = '+0:00'");
$bIds = array(1, 2, 3);

//data is ready for export on UTC 10pm
$now = time();

for ($i = 0; $i < count($bIds); $i++){
    //*2 for the case when previous export has failed for some reason
    $updatedFlats = getListOfUpdatedFlats($db, $bIds[$i], $now - 2*86400);
    if($updatedFlats){
       foreach($updatedFlats as $flat){
           exportFlatHistoryJSON($db, $bIds[$i], $flat['flatId']);
       }
    }
    //exportDateSnapJSON($db, $bIds[$i], $now);
    //exportAvMeterPriceJSON($db, $bIds[$i]);
    //exportAvailFlatsQuantityHistoryJSON($db, $bIds[$i]);
}

$db -> close();

$output = ob_get_contents();
$ifErrors = stripos($output , 'error') || stripos($output, 'warning');
sendMailNotice($output, $ifErrors, $nbsCrConf['pearMail'], $nbsCrConf['serverName']);
$time_end = microtime(true);
echo "<p class='execTime'>Execution time: ".round($time_end - $time_start, 2)." s.</p>";
saveLog(ob_get_contents());
ob_end_flush();
