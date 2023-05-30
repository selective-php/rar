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
        $result = 0;
        do {
            $number = $this->readByte($file);
            // Applying bitmask to extract lower 7 bits
            $lower7Bits = $number & 0x7F;
            // Finding the highest bit
            $highestBit8 = ($number >> 7) & 1;

            $result += $lower7Bits;
        } while ($highestBit8 != 0);

        return $result;
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
        $result = unpack($format, $data);

        if ($result === false) {
            throw new UnexpectedValueException('The format string contains errors');
        }

        return (array)$result;
    }
}
