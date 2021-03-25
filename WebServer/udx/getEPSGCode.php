<?php
function getEPSGCode($cityCode)
{
    switch ($cityCode) {
        case '01100':
            $epsgCode = 6680;
            break;
        case '07203':
        case '07204':
        case '07205':
        case '08234':
        case '09201':
        case '10203':
        case '10207':
        case '11100':
        case '11202':
        case '11230':
        case '11326':
        case '12217':
        case '13100':
        case '13213':
        case '14100':
        case '14130':
        case '14150':
        case '14201':
        case '14382':
            $epsgCode = 6677;
            break;
        case '15100':
        case '20202':
        case '20204':
        case '20209':
        case '20214':
        case '22203':
        case '22213':
        case '22224':
            $epsgCode = 6676;
            break;
        case '17201':
        case '17206':
        case '21201':
        case '23100':
        case '23202':
        case '23208':
        case '23212':
            $epsgCode = 6675;
            break;
        case '27100':
        case '27203':
        case '27204':
        case '27207':
        case '27224':
        case '27341':
            $epsgCode = 6674;
            break;
        case '28210':
        case '31201':
            $epsgCode = 6673;
            break;
        case '38201':
            $epsgCode = 6672;
            break;
        case '34202':
        case '34207':
            $epsgCode = 6671;
            break;
        case '40100':
        case '40203':
        case '40205':
        case '40220':
        case '43100':
        case '43204':
        case '43206':
        case '43443':
        case '44204':
            $epsgCode = 6670;
            break;
        case '47201':
            $epsgCode = 6683;
            break;
        default:
            $epsgCode = 6677;
            break;
    }
    return $epsgCode;
}
?>