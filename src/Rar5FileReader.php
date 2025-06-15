<?php

namespace Selective\Rar;

use DateTimeImmutable;
use Selective\Rar\Converter\BitConverter;
use Selective\Rar\Struct\CompressionInfoStruct;
use Selective\Rar\Struct\RarArchiveStruct;
use Selective\Rar\Struct\RarFileHeadStruct;
use Selective\Rar\Struct\RarVolumeHeaderStruct;
use SplFileObject;
use UnexpectedValueException;

/**
 * RAR 5 file reader.
 *
 * Specifications: https://www.rarlab.com/technote.htm
 */
final class Rar5FileReader
{
    /**
     * @var BinaryFileReader
     */
    private $fileReader;

    /**
     * @var BitConverter
     */
    private $bit;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->fileReader = new BinaryFileReader();
        $this->bit = new BitConverter();
    }

    public function readRarFile(SplFileObject $file, RarArchiveStruct $rarFile): void
    {
        // Header type 1
        $this->readRar5MainArchiveHeader($file);

        while (!$file->eof()) {
            $start = (int)$file->ftell();

            // File CRC uint32
            $file->fread(4);

            // Header size
            $this->fileReader->readVint($file);

            $headerType = $this->fileReader->readVint($file);
            if ($headerType > 5) {
                throw new UnexpectedValueException(sprintf('Invalid header type: %s', $headerType));
            }

            // 0 = Main archive header
            // 2 = File header
            // 3 = Service header (CMT = comments, QO, ACL, STM, RR)
            if ($headerType === 2 || $headerType === 3) {
                $file->fseek($start);

                // File header and service header
                $fileHeader = $this->readRar5FileHeader($file);

                // Append only files
                if ($headerType === 2 && $fileHeader->isDirectory === false) {
                    $rarFile->files[] = $fileHeader;
                }
            }

            // 5 = End of archive
            if ($headerType === 5) {
                // Move the offset to the end of the file
                $file->fseek(0, SEEK_END);
                break;
            }
        }
    }

    private function readRar5MainArchiveHeader(SplFileObject $file): RarVolumeHeaderStruct
    {
        // https://www.rarlab.com/technote.htm#mainhead

        $volumeHeader = new RarVolumeHeaderStruct();

        // CRC uint32
        $volumeHeader->crc = strtoupper(bin2hex(strrev((string)$file->fread(4))));

        // The header size indicates how many total bytes the header requires
        $volumeHeader->size = $this->fileReader->readVint($file);
        $volumeHeader->type = $this->fileReader->readVint($file);

        if ($volumeHeader->type != 1) {
            throw new UnexpectedValueException(sprintf('Invalid main archive header type: %s', $volumeHeader->type));
        }

        // Flags common for all headers
        $volumeHeader->flags = $this->fileReader->readVint($file);

        // 0x0001 Volume. Archive is a part of multivolume set.
        $extraAreaSize = 0;
        if ($this->bit->isFlagSet($volumeHeader->flags, 0x0001)) {
            // Size of extra area. Optional field, present only if 0x0001 header flag is set.
            $extraAreaSize = $this->fileReader->readVint($file);
        }

        // Archive flags
        // 0x0001 Volume. Archive is a part of multi-volume set.
        // 0x0002 Volume number field is present. This flag is present in all volumes except first.
        // 0x0004 Solid archive.
        // 0x0008 Recovery record is present.
        // 0x0010 Locked archive.
        $archiveFlags = $this->fileReader->readVint($file);

        if ($this->bit->isFlagSet($archiveFlags, 0x0002)) {
            // Multivolume RAR archive (starts with the second file)
            $this->fileReader->readVint($file);
        }

        // Add offset for next block (if any)
        if ($extraAreaSize) {
            $file->fseek($extraAreaSize, SEEK_CUR);
        }

        return $volumeHeader;
    }

    private function readRar5FileHeader(SplFileObject $file): RarFileHeadStruct
    {
        $fileHeader = new RarFileHeadStruct();

        $fileHeader->unpVer = 5;

        // CRC
        $file->fread(4);

        // Size
        $this->fileReader->readVint($file);

        // Type
        $this->fileReader->readVint($file);

        // Flags common for all headers
        $headerFlags = $this->fileReader->readVint($file);

        // Size of extra area. Optional field, present only if 0x0001 header flag is set.
        $extraAreaSize = 0;
        if ($this->bit->isFlagSet($headerFlags, 0x0001)) {
            $extraAreaSize = $this->fileReader->readVint($file);
        }

        // Compressed file size
        $fileHeader->packSize = 0;

        // Size of data area. Optional field, present only if 0x0002 header flag is set.
        // For file header this field contains the packed file size.
        if ($this->bit->isFlagSet($headerFlags, 0x0002)) {
            $fileHeader->packSize = $this->fileReader->readVint($file);
        }

        // Flags specific for these header types:
        // 0x0001 Directory file system object (file header only).
        // 0x0002 Time field in Unix format is present.
        // 0x0004 CRC32 field is present.
        // 0x0008 Unpacked size is unknown.
        $fileFlags = $this->fileReader->readVint($file);

        if ($this->bit->isFlagSet($fileFlags, 0x0001)) {
            // This is just a directory, not a file
            $fileHeader->isDirectory = true;
        }

        // Unpacked file or service data size
        $fileHeader->unpackSize = $this->fileReader->readVint($file);
        $fileHeader->lowUnpackSize = $fileHeader->unpackSize;

        // Operating system specific file attributes in case of file header.
        // Might be either used for data specific needs or just reserved and set to 0 for service header.
        $fileHeader->fileAttr = $this->fileReader->readVint($file);

        // File modification time in Unix time format.
        // Optional, present if 0x0002 file flag is set.
        if ($this->bit->isFlagSet($fileFlags, 0x0002)) {
            // Convert uint32 (4 bytes) to Unix timestamp
            $fileTimeUnix = $this->fileReader->readBigInt($file);
            $fileHeader->fileTime = (new DateTimeImmutable())->setTimestamp($fileTimeUnix);
        }

        // 0x0004 CRC32 field is present.
        if ($this->bit->isFlagSet($fileFlags, 0x0004)) {
            $fileHeader->fileCRC = strtoupper(bin2hex(strrev((string)$file->fread(4))));
        }

        // compression information
        $compressionInfoRaw = $this->fileReader->readVint($file);
        $fileHeader->method = $this->parseCompressionInfo($compressionInfoRaw)->method;

        // 0 = Windows, 1 = Unix
        $fileHeader->hostOS = $this->fileReader->readVint($file);

        // File or service header name length
        $fileHeader->nameSize = $this->fileReader->readVint($file);

        // Variable length field containing Name length bytes in UTF-8 format without trailing zero
        $fileHeader->fileName = (string)$file->fread($fileHeader->nameSize);

        // Optional area containing additional header fields, present only if 0x0001 header flag is set
        if ($this->bit->isFlagSet($headerFlags, 0x0001) && $extraAreaSize) {
            $this->readExtraArea($file, $fileHeader, $extraAreaSize);
        }

        // Optional data area, present only if 0x0002 header flag is set.
        // Store file data in case of file header or service data for service header.
        // Depending on the compression method value in Compression information can
        // be either uncompressed (compression method 0) or compressed.
        if ($this->bit->isFlagSet($headerFlags, 0x0002) && $fileHeader->packSize) {
            // Move to end of compresses data
            $file->fseek($fileHeader->packSize, SEEK_CUR);
        }

        return $fileHeader;
    }

    private function parseCompressionInfo(int $raw): CompressionInfoStruct
    {
        $info = new CompressionInfoStruct();

        // Lower 6 bits (0x003f mask) contain the version of compression algorithm, resulting in possible 0 - 63 values.
        // Currently values 0 and 1 are possible. Version 0 archives can be unpacked by RAR 5.0 and newer.
        // Version 1 archives can be unpacked by RAR 7.0 and newer.
        $info->version = $raw & 0x003F;

        // 7th bit (0x0040) defines the solid flag. If it is set,
        // RAR continues to use the compression dictionary left after processing preceding files.
        // It can be set only for file headers and is never set for service headers.
        $info->solid = ($raw & 0x0040) !== 0;

        // Bits 8 - 10 (0x0380 mask) define the compression method.
        // Currently only values 0 - 5 are used. 0 means no compression.
        $info->method = ($raw & 0x0380) >> 7;

        // Bits 11 - 15 (0x7c00) specify the minimum dictionary size required to extract data.
        // If we define these bits as N, the dictionary size is 128 KB * 2^N.
        // So value 0 means 128 KB, 1 - 256 KB, ..., 15 - 4096 MB, ..., 19 - 64 GB. 23 means 1 TB,
        // which is the theoretical maximum allowed by this field.
        // Actual compression and decompression implementation might have a lower limit.
        // Values above 15 are used only if compression algorithm version is 1.
        $info->dictionarySizeClass = ($raw & 0x7C00) >> 10;

        // Base dictionary size: 128 KB × 2^N
        $info->dictionarySize = 128 * 1024 * (2 ** $info->dictionarySizeClass);

        if ($info->version === 1) {
            // Bits 16 - 20 (0xf8000) are present only if version of compression algorithm is 1.
            // Value in these bits is multiplied to the dictionary size in bits 11 - 15 and divided by 32,
            // the result is added to dictionary size.
            // It allows to specify up to 31 intermediate dictionary sizes between neighbouring power of 2 values.
            $info->dictionarySizeMultiplier = ($raw & 0xF8000) >> 15;

            // Bit 21 (0x100000) is present only if version of compression algorithm is 1.
            // It indicates that even though the dictionary size flags are in version 1 format,
            // the actual compression algorithm is version 0.
            // It is helpful when we append version 1 files to existing version 0 solid stream
            // and need to increase the dictionary size for version 0 files not touching their compressed data.
            $info->forcedVersion0 = ($raw & 0x100000) !== 0;

            if ($info->dictionarySizeMultiplier > 0) {
                $info->dictionarySize += intdiv($info->dictionarySize * $info->dictionarySizeMultiplier, 32);
            }
        }

        // stringify
        $dictSizeKB = (int)($info->dictionarySize / 1024);

        if ($dictSizeKB >= 1024) {
            $unit = 'm';
            $dictSize = $dictSizeKB / 1024;
        } else {
            $unit = 'k';
            $dictSize = $dictSizeKB;
        }

        $info->methodName = sprintf(
            'v%d:m%d:%d%s',
            $info->version,
            $info->method,
            $dictSize,
            $unit
        );

        return $info;
    }

    private function readExtraArea(SplFileObject $file, RarFileHeadStruct $fileHeader, int $extraAreaSize): void
    {
        $extraOffset = $file->ftell();

        // extra area size 2
        $this->fileReader->readVint($file);
        $extraAreaType = $this->fileReader->readVint($file);

        // file time (extra record)
        if ($extraAreaType === 3) {
            $this->readExtraAreaFileTime($file, $fileHeader);
        }

        // Jump to end of extra record
        $file->fseek($extraOffset + $extraAreaSize);
    }

    private function readExtraAreaFileTime(SplFileObject $file, RarFileHeadStruct $fileHeader): void
    {
        $timeFlags = $this->fileReader->readVint($file);

        if (!$this->bit->isFlagSet($timeFlags, 0x0002)) {
            return;
        }

        // Time is stored in Unix time_t format if this flags
        // is set and in Windows FILETIME format otherwise
        $isUnixTime = $this->bit->isFlagSet($timeFlags, 0x0001);

        if ($isUnixTime) {
            $fileTimeUnix = $this->fileReader->readBigInt($file);
            $fileHeader->fileTime = (new DateTimeImmutable())->setTimestamp($fileTimeUnix);
        } else {
            // Convert bytes to a 64-bit integer
            $fileTime = ((array)unpack('P', (string)$file->fread(8)))[1];

            // Adjust Windows FILETIME to Unix timestamp format
            $fileTimeUnix = ($fileTime - 116444736000000000) / 10000000;
            $fileHeader->fileTime = (new DateTimeImmutable())->setTimestamp((int)$fileTimeUnix);
        }
    }
}
