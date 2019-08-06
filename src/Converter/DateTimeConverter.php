<?php

namespace Selective\Rar\Converter;

use DateTimeImmutable;

/**
 * DateTime Converter.
 */
final class DateTimeConverter
{
    /**
     * Convert 2 Byte DOS Time & 2 Byte Date to DateTime.
     *
     * @param int $time The 2 byte DOS time
     * @param int $date The 2 byte DOS date
     *
     * @return DateTimeImmutable
     */
    public function getGetDateTimeFromDosDateTime(int $time, int $date): DateTimeImmutable
    {
        $second = ($time & 0x1F) * 2;
        $minute = (($time & 0x07E0) >> 5);
        $hour = (($time & 0x0F800) >> 11);

        $day = ($date & 0x1F);
        $month = (($date & 0x01E0) >> 5);
        $year = 1980 + (($date & 0xFE00) >> 9);

        return (new DateTimeImmutable())->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }
}
