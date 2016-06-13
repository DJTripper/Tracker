<?php
require_once(__DIR__."/vendor/autoload.php");

$content = gzuncompress(base64_decode(file_get_contents("php://input")));
$lines = explode("\n", $content);

$dsn = 'mysql:dbname=tracking;host=127.0.0.1';
$user = 'root';
$password = 'ygecZGGNZYXBssj6pp9g';

try {
    $db = new PDO($dsn, $user, $password);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}

$sql = 'INSERT INTO
        `tracking`
    SET
        `tracker_id` = :trackerId,
        `fix` = :fix,
        `lat` = :lat,
        `lat_type` = :latType,
        `long` = :long,
        `long_type` = :longType,
        `fix_quality` = :fixQuality,
        `numSatellites` = :numSatellites,
        `horizontalDilution` = :horizontalDilution,
        `altitude` = :altitude,
        `altitudUnit` = :altitudeUnit,
        `geoidalSeparation` = :geoidalSeparation,
        `geoidalSeparationUnit` = :geoidalSeparationUnit,
        `timeSinceLastUpdate` = :timeSinceLastUpdate,
        `dgpsStationId` = :dgpsStationId';
$query = $db->prepare($sql);

foreach ($lines as $line) {
    if (empty($line)) {
        continue;
    }
    try {
        $sentence = NMEA\Sentence\Factory::create(trim($line));
        $data = $sentence->getValues();
        $ret = $query->execute(
            array(
                ":trackerId"                => $_GET['trackerId'],
                ":fix"                      => date("Y-m-d ").implode(":", str_split(substr($data['fix'], 0, 6), 2)),
                ":lat"                      => explode(",", $data['latitud'])[0],
                ":latType"                  => explode(",", $data['latitud'])[1],
                ":long"                     => explode(",", $data['longitud'])[0],
                ":longType"                 => explode(",", $data['longitud'])[1],
                ":fixQuality"               => $data['fixQuality'],
                ":numSatellites"            => $data['numSatellites'],
                ":horizontalDilution"       => $data['horizontalDilution'],
                ":altitude"                 => $data['altitud'],
                ":altitudeUnit"             => $data['altitudUnit'],
                ":geoidalSeparation"        => $data['geoidalSeparation'],
                ":geoidalSeparationUnit"    => $data['geoidalSeparationUnit'],
                ":timeSinceLastUpdate"      => intval($data['timeSinceLastUpdate']),
                ":dgpsStationId"            => intval($data['dgpsStationId'])
            )
        );
        if (!$ret) {
//			mail("test@alexdelius.com", "GPS", print_r($data, true).print_r($query->errorInfo(), true));
        }
    } catch (\Exception $e) {
//		mail("test@alexdelius.com", "GPS", print_r($e, true));
    }
}

