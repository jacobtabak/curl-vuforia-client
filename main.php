<?php
/**
 * Created by PhpStorm.
 * User: jacob
 * Date: 8/12/14
 * Time: 11:31 AM
 */
require_once 'VuforiaClient.php';

$handle = fopen('data.csv', 'r');
if ($handle === false) {
    exit('Unable to open file handle');
}

$client = new VuforiaClient();
$client->deleteAllTargets();
while ($row = fgetcsv($handle, 1000, ",")) {
    $client->addTarget($row);
}

