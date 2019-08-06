<?php

namespace Selective\Rar\Converter;

/**
 * Bit Converter.
 */
final class BitConverter
{
    /**
     * Convert fom 2x bytes (32bit, high and low) to 64 bit unsigned integers (uint64).
     *
     * Code from https://stackoverflow.com/a/49740818/1461181
     *
     * @param string $hi The high number
     * @param string $lo The low number
     *
     * @return string The 64 bit unsigned integer (as string)
     */
    public function toInt64(string $hi, string $lo): string
    {
        // On x64, we can just use int
        if ((int)4294967296 != 0) {
            return strval(((int)$hi << 32) + (int)$lo);
        }

        // Workaround signed/unsigned braindamage on x32
        $hi = sprintf('%u', $hi);
        $lo = sprintf('%u', $lo);

        // Use GMP or bcmath if possible
        if (function_exists('gmp_mul')) {
            return gmp_strval(gmp_add(gmp_mul($hi, '4294967296'), $lo));
        }

        if (function_exists('bcmul')) {
            return bcadd(bcmul($hi, '4294967296'), $lo);
        }

        // compute everything manually
        return $this->bcAdd($this->bcMultiply($hi, '4294967296'), $lo, 0);
    }

    /**
     * BC multiply.
     *
     * Code from: https://www.php.net/manual/de/function.bcmul.php.
     *
     * @param string $number1 The number 1
     * @param string $number2 The number 2
     *
     * @return string The result
     */
    private function bcMultiply(string $number1 = '0', string $number2 = '0'): string
    {
        // Check if they're both plain numbers
        if (!preg_match("/^\d+$/", $number1) || !preg_match("/^\d+$/", $number2)) {
            return '0';
        }

        // remove zeroes from beginning of numbers
        $number1length = strlen($number1);
        for ($i = 0; $i < $number1length; $i++) {
            if ($number1[$i] != '0') {
                $number1 = substr($number1, $i);
                break;
            }
        }

        $number2length = strlen($number2);
        for ($i = 0; $i < $number2length; $i++) {
            if ($number2[$i] != '0') {
                $number2 = substr($number2, $i);
                break;
            }
        }

        // get both number lengths
        $len1 = strlen($number1);
        $len2 = strlen($number2);

        // $rema is for storing the calculated numbers and $rema2 is for carrying the remainders
        $rema = $rema2 = [];

        // we start by making a $Len1 by $Len2 table (array)
        for ($y = $i = 0; $y < $len1; $y++) {
            for ($x = 0; $x < $len2; $x++) {
                $idx = $i++ % $len2;
                $rema[$idx] = $rema[$idx] ?? '';
                // we use the classic lattice method for calculating the multiplication..
                // this will multiply each number in $Num1 with each number in $Num2 and store it accordingly
                $rema[$idx] .= sprintf('%02d', (int)$number1[$y] * (int)$number2[$x]);
            }
        }

        // cycle through each stored number
        for ($y = 0; $y < $len2; $y++) {
            for ($x = 0; $x < $len1 * 2; $x++) {
                // add up the numbers in the diagonal fashion the lattice method uses
                $idx = floor(($x - 1) / 2) + 1 + $y;
                $rema2[$idx] = $rema2[$idx] ?? 0;
                $rema2[$idx] += (int)$rema[$y][$x];
            }
        }

        // reverse the results around
        $rema2 = array_reverse($rema2);

        // cycle through all the results again
        $rename2count = count($rema2);

        for ($i = 0; $i < $rename2count; $i++) {
            // reverse this item, split, keep the first digit, spread the other digits down the array
            $rema3 = str_split(strrev($rema2[$i]));
            $rename3count = count($rema3);

            for ($o = 0; $o < $rename3count; $o++) {
                $idx = $i + $o;
                $rema2[$idx] = $rema2[$idx] ?? 0;

                if ($o === 0) {
                    $rema2[$idx] = $rema3[$o];
                } else {
                    $rema2[$idx] += $rema3[$o];
                }
            }
        }

        // Implode $rema2 so it's a string and reverse it, this is the result!
        $rema2 = strrev(implode($rema2));

        // Just to make sure, we delete the zeros from the beginning of the result and return
        while (strlen($rema2) > 1 && $rema2[0] == '0') {
            $rema2 = substr($rema2, 1);
        }

        return (string)$rema2;
    }

    /**
     * https://github.com/freemed/freemed-0.8.x/blob/master/lib/bcadd.php.
     *
     * @param string $left The left value
     * @param string $right The right value
     * @param int $scale The scale
     *
     * @return string The result
     */
    private function bcAdd(string $left, string $right, int $scale): string
    {
        // Deal with numbers smaller than $scale
        $left1 = ($left < pow(10, -$scale)) ? 0 : $left;
        $right1 = ($right < pow(10, -$scale)) ? 0 : $right;

        // first add the two numbers
        $sum = (float)$left1 + (float)$right1;

        // check for a dot in the number
        if (strpos((string)$sum, '.') === false) {
            // not found, integer
            $intPart = (string)$sum;
            $realPart = '0';
        } else {
            // if not, we split
            [$intPart, $realPart] = explode('.', (string)$sum);
        }

        // handle scale of 0
        if ($scale === 0) {
            return $intPart;
        }

        // handle real parts that need more precision
        if ($scale > strlen($realPart)) {
            for ($i = 0; $i <= ($scale - strlen($realPart)); $i++) {
                $realPart .= '0';
            }
        }

        // return built string
        return $intPart . '.' . substr($realPart, 0, $scale);
    }

    /**
     * Is flag set.
     *
     * @param int $flags The flags
     * @param int $flag The flag
     *
     * @return int Status
     */
    public function isFlagSet($flags, $flag): int
    {
        return $flags & $flag;
    }
}
