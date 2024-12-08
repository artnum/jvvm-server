<?php

namespace JJVM\Utils;

trait AVS {
    public static function toint (string $avs):int {
        $avsid = 0;
        for($i = 0; $i < strlen($avs); $i++) {
            if (!is_numeric($avs[$i])) { continue; }
            $avsid = $avsid * 10 + intval($avs[$i]);
        }
        return $avsid;
    }

    public static function format(string|int $avs):string {
        if (is_int($avs)) { $avs = self::tostring($avs); }
        return substr($avs, 0, 3) . '.' . 
            substr($avs, 3, 4) . '.' . 
            substr($avs, 7, 4) . '.' . 
            substr($avs, 11, 2);
    }

    public static function tostring (int $avs):string {
        $avsstr = '';
        while ($avs > 0) {
            $avsstr = strval($avs % 10) . $avsstr;
            $avs = intdiv($avs, 10);
        }
        return self::format($avsstr);
    }

    /* AVS use EAN-13 checksum */
    public static function check(string|int $avs):bool {
        if (is_int($avs)) { $avs = self::tostring($avs); }
        /* code for switzerland */
        if (substr($avs, 0, 3) !== '756') { return false; }
        $sum = 0;
        $j = 0;
        for ($i = 0; $i < strlen($avs) - 1; $i++) {
            if (!is_numeric($avs[$i])) { continue; }
            $sum += intval($avs[$i]) * (($j % 2) ? 3 : 1);
            $j++;
        }
        $check = 10 - ($sum % 10);
        return $check === intval($avs[12]);
    }
}

