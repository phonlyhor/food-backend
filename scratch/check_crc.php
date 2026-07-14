<?php

function my_crc16_checksum($str)
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($str); $i++) {
        $x = (($crc >> 8) ^ ord($str[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ ($x << 0)) & 0xFFFF;
    }
    return sprintf('%04X', $crc);
}

function stack_crc16_checksum($data) {
    $crc = 0xFFFF; // Initial value
    $polynomial = 0x1021; // Polynomial

    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if (($crc & 0x8000) !== 0) {
                $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

$testString = "00020101021229350010hps.bakong0117lyhor_phon@bkrt520459995303840540510.005802KH5910LIHOR Phon6010Phnom Penh62240120BAKONG-1-17838402926304";

echo "Mine: " . my_crc16_checksum($testString) . "\n";
echo "Stack: " . stack_crc16_checksum($testString) . "\n";
