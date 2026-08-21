<?php

class Utilities
{
    /**
     * Get error Code Name
     * @param $errorCode
     */

    public static function getErrorCodeString($errorCode)
    {
        $errorCodeString = "";

        switch ($errorCode) {
            case 1:
                $errorCodeString = "SUCCESSFUL";
                break;
            case 0:
                $errorCodeString = "SYSTEM_BUSY";
                break;
            case -1:
                $errorCodeString = "FAIL_TO_RECEIVE_REQ_MSG";
                break;
            case -2:
                $errorCodeString = "INVALID_REQ_LEN";
                break;
            case -3:
                $errorCodeString = "INVALID_REQ_TYPE";
                break;
            case -4:
                $errorCodeString = "FAIL_TO_DECODE_MSG";
                break;
            case -5:
                $errorCodeString = "INVALID_DEFAULT_REQ_LEN";
                break;
            case -6:
                $errorCodeString = "NOT_ALLOWED_IP";
                break;
            case -7:
                $errorCodeString = "INVALID_INPUT";
                break;
            case -8:
                $errorCodeString = "INVALID_PRODUCT_ID";
                break;
            case -9:
                $errorCodeString = "CLIENT_DISCONNECTED";
                break;
            case -10:
                $errorCodeString = "UNDEFINE_ERRORCODE";
                break;
            case -11:
                $errorCodeString = "UNDEFINE_ERRORCODE";
                break;

                //Account
            case -101:
                $errorCodeString = "INVALID_ACCOUNT_NO";
                break;
            case -102:
                $errorCodeString = "INVALID_ACCOUNT_NAME";
                break;
            case -103:
                $errorCodeString = "SUSPENDED_ACCOUNT";
                break;

                //Cash
            case -201:
                $errorCodeString = "INVALID_CASH_ID";
                break;
            case -202:
                $errorCodeString = "INVALID_CASH_TYPE";
                break;
            case -203:
                $errorCodeString = "INVALID_CASH_AMT";
                break;
            case -204:
                $errorCodeString = "INSUFFICIENT_CASH_POSSESSION";
                break;

                //Purchase
            case -301:
                $errorCodeString = "INVALID_PURCHASE_ID";
                break;

                //Item
            case -401:
                $errorCodeString = "INVALID_ITEM_ID";
                break;
            case -402:
                $errorCodeString = "INVALID_ITEM_NAME";
                break;
            case -403:
                $errorCodeString = "INVALID_ITEM_QUANTITY";
                break;
        }

        return $errorCodeString;
    }

    public static function packInt32($int32)
    {
        $data = "";
        $data .= chr($int32 & 0xFF);
        $data .= chr(($int32 >> 8) & 0xFF);
        $data .= chr(($int32 >> 16) & 0xFF);
        $data .= chr(($int32 >> 24) & 0xFF);

        return $data;
    }

    public static function unpackInt32($data)
    {
        return (float) sprintf(
            "%u",
            ((ord($data[3]) << 24) |
            (ord($data[2]) << 16) |
            (ord($data[1]) << 8) |
            (ord($data[0])))
        );
    }

    public static function packInt64Hex($int64Hex)
    {
        //$data is hex number string
        //return 8bytes in little-endian
        return strrev(pack("H*", $int64Hex));
    }

    public static function unpackInt64Hex($data)
    {
        //$data is 8bytes in little-endian
        //return hex number string
        $tokens = unpack("H*", strrev($data));
        return $tokens[1];
    }

    public static function int64FromInt64Hex($int64Hex)
    {
        return (float)sprintf("%.0f", hexdec($int64Hex));
    }

    public static function decFromInt64Hex($int64Hex)
    {
        return hexdec($int64Hex);
    }

    public static function packString($str, $packlen)
    {
        $data = "";
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $data .= pack("c", ord(substr($str, $i, 1)));
        }
        return pack("a$packlen", $data);
    }

    public static function unpackString($data, $len)
    {
        $tmp_arr = unpack("c".$len."chars", $data);
        $str = "";
        foreach ($tmp_arr as $v) {
            if ($v > 0) {
                $str .= chr($v);
            }
        }

        return $str;
    }

    public static function packUniString($str, $packlen)
    {
        $data = mb_convert_encoding($str, 'UCS-2LE', 'UTF-8');
        return pack("a$packlen", $data);
    }

    public static function unpackUniString($data, $len)
    {
        $tmp_arr = unpack("C".$len."chars", $data);
        $uniStr = "";
        foreach ($tmp_arr as $v) {
            if ($v > 0) {
                $uniStr .= chr($v);
            }
        }

        return $uniStr;
    }

    public static function showDecBlock($data)
    {
        $length = strlen($data);

        echo("<b><u>[$length bytes]</u></b><br>{");
        for ($i = 0; $i < $length; $i++) {
            if ($length === 116) {
                if ($i === 2) {
                    echo("<b><u>");
                } elseif ($i === 4) {
                    echo("</u></b>");
                } elseif ($i === 6) {
                    echo("<b><u>");
                } elseif ($i === 108) {
                    echo("</u></b>");
                } elseif ($i === 116) {
                    echo("<b><u>");
                }
            }

            if ($i > 0) {
                echo(":");
            }
            echo(hexdec(bin2hex($data[$i])));
        }
        echo("}<br>");
    }

    public static function showHexBlock($data)
    {
        $length = strlen($data);

        echo("<b><u>[$length bytes]</u></b><br>{");
        for ($i = 0; $i < $length; $i++) {
            if ($length === 116) {
                if ($i === 2) {
                    echo("<b><u>");
                } elseif ($i === 4) {
                    echo("</u></b>");
                } elseif ($i === 6) {
                    echo("<b><u>");
                } elseif ($i === 108) {
                    echo("</u></b>");
                } elseif ($i === 116) {
                    echo("<b><u>");
                }
            }

            if ($i > 0) {
                echo(":");
            }
            echo(bin2hex($data[$i]));
        }
        echo("}<br>");
    }

    public static function showCharBlock($data)
    {
        $length = strlen($data);

        echo("<b><u>[$length bytes]</u></b><br>{");
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0) {
                echo(":");
            }
            echo($data[$i]);
        }
        echo("}<br>");
    }
}
