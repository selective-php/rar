<?php

namespace Selective\Rar;

use SplFileObject;
use UnexpectedValueException;

class BinaryFileReader
{
    /**
     * Reade 4 bytes and convert to big int.
     *
     * @param SplFileObject $file The file
     *
     * @return int The value
     */
    public function readBigInt(SplFileObject $file): int
    {
        return $this->getBigInt((string)$file->fread(4));
    }

    /**
     * Reade 2 bytes and convert to int.
     *
     * @param SplFileObject $file The file
     *
     * @return int The value
     */
    public function readInt(SplFileObject $file): int
    {
        return $this->getInt((string)$file->fread(2));
    }

    /**
     * Reade 1 byte and convert to int.
     *
     * @param SplFileObject $file The file
     *
     * @return int The value
     */
    public function readByte(SplFileObject $file): int
    {
        return ord((string)$file->fread(1));
    }

    public function readVint(SplFileObject $file): int
    {
        $shift = 0;
        $low = 0;
        $high = 0;
        $count = 1;

        while (!$file->eof() && $count <= 10) {
            $byte = ord((string)$file->fread(1));

            if ($count < 5) {
                $low += ($byte & 0x7F) << $shift;
            } elseif ($count === 5) {
                $low += ($byte & 0x0F) << $shift; // 4 bits
                $high += ($byte >> 4) & 0x07; // 3 bits
                $shift = -4;
            } else {
                $high += ($byte & 0x7F) << $shift;
            }

            if (($byte & 0x80) === 0) {
                if ($low < 0) {
                    $low += 0x100000000;
                }
                if ($high < 0) {
                    $high += 0x100000000;
                }

                return ($high !== 0) ? $this->int64($low, $high) : $low;
            }

            $shift += 7;
            $count++;
        }

        return 0;
    }

    /**
     * Convert 2 bytes to unsigned little-endian int.
     *
     * @param string $data The data
     *
     * @return int The value
     */
    public function getInt(string $data): int
    {
        if ($data === '') {
            return 0;
        }

        // 2 bytes
        return (int)$this->unpack('v', $data)[1];
    }

    /**
     * Convert 4 bytes to unsigned little-endian big int.
     *
     * @param string $data The data
     *
     * @return int The value
     */
    private function getBigInt(string $data): int
    {
        // 4 bytes
        return (int)$this->unpack('V', $data)[1];
    }

    /**
     * Unpack data from binary string.
     *
     * @param string $format The format
     * @param string $data The data
     *
     * @throws UnexpectedValueException
     *
     * @return array<mixed> The unpacked data
     */
    private function unpack(string $format, string $data): array
    {
        if ($data === '') {
            return [];
        }

        $result = unpack($format, $data);

        if ($result === false) {
            throw new UnexpectedValueException('The format string contains errors');
        }

        return (array)$result;
    }

    private function int64(int $low, int $high): int
    {
        return $low + ($high * 0x100000000);
    }
}
