<?php

shell_exec("stty -F /dev/serial/by-id/usb-Prolific_Technology_Inc._USB-Serial_Controller_D-if00-port0 38400");

$f = fopen("/dev/serial/by-id/usb-Prolific_Technology_Inc._USB-Serial_Controller_D-if00-port0", "r");

$buffer = "";
$i = 10;
$sequence = uniqid();
while ($row = fgets($f)) {
    if ($row && strpos($row, '$GPGGA') === 0) {
        $buffer .= $row;
        $i++;

        if($i >= 10) {
            $ch = curl_init($_SERVER['argv'][1]."?trackerId=1&sequence=".$sequence);
            curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode(gzcompress($buffer, 9)));
            $res = curl_exec($ch);
            curl_close($ch);
            $i = 0;
            if ($res) {
                $buffer = "";
            }
        }
    }
}

