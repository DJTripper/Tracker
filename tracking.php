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
        `sequence` = :sequence,
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

function reformatNmeaCoordinate($coord)
{
    $p = explode(",", $coord);

    $splitPos = strpos($p[0], ".")-2;
    $str = intval(substr($p[0], 0, $splitPos));
    return $p[1].$str."° ".floatval(substr($p[0], $splitPos));
}

foreach ($lines as $line) {
    if (empty($line)) {
        continue;
    }
    try {
        $sentence = NMEA\Sentence\Factory::create(trim($line));
        $data = $sentence->getValues();
        $lat = reformatNmeaCoordinate($data['latitud']);
        $lon = reformatNmeaCoordinate($data['longitud']);
        $point = \Location\Factory\CoordinateFactory::fromString($lat.", ".$lon)->format(
            new \Location\Formatter\Coordinate\DecimalDegrees()
        );

        $ret = $query->execute(
            array(
                ":trackerId"                => $_GET['trackerId'],
                ":sequence"                 => $_GET['sequence'],
                ":fix"                      => date("Y-m-d ").implode(":", str_split(substr($data['fix'], 0, 6), 2)),
                ":lat"                      => explode(" ", $point)[0],
                ":latType"                  => explode(",", $data['latitud'])[1],
                ":long"                     => explode(" ", $point)[1],
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
    } catch (\Exception $e) {
        // Do nothing
    }
}

