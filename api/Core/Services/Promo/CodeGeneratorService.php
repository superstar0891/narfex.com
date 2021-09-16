<?php

namespace Core\Services\Promo;

class CodeGeneratorService {
    private static $alphabet_encoding_symbols = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';

    public static function encodeAgentId(int $id) {
        $mask = ['B', 'T', 'C', 'N', 'E', 'T', 'X'];
        return self::encodeByMask($mask, $id);
    }

    public static function encodeUserId(int $id) {
        $mask = ['F', 'N', 'D', 'R', 'X', 'X', 'X'];
        return self::encodeByMask($mask, $id);
    }

    private static function encodeByMask(array $mask, int $id) {
        $code = str_split(self::encode($id));
        $code_length = count($code);

        return implode('', array_merge(
                array_slice($mask, 0, count($mask) - count($code)),
                $code
            )) . self::encode($code_length);
    }

    public static function decodeUserCode(string $code) {
        $str_len = strlen($code);
        $encoded_length = substr($code, $str_len - 1, $str_len);
        $length = self::decode($encoded_length);
        $code = substr($code, 0, $str_len - 1);
        $code = str_split($code);
        $code = array_slice($code, count($code) - $length, $length);
        $code = implode('', $code);
        return self::decode($code);
    }

    public static function encode(int $id): string {
        $len = strlen(self::$alphabet_encoding_symbols);
        $code = '';
        $val = $id;
        while ($val >= 1) {
            $code .= self::$alphabet_encoding_symbols[$val % $len];
            $val /= $len;
        }
        return $code;
    }

    public static function decode(string $code): int {
        $val = 0;
        if (!$code) {
            return $val;
        }
        $len = strlen(self::$alphabet_encoding_symbols);
        $pow = 1;
        foreach (str_split($code) as $symbol) {
            $pos = strpos(self::$alphabet_encoding_symbols, $symbol);
            $val += $pos * $pow;
            $pow *= $len;
        }
        return $val;
    }
}