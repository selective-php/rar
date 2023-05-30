<?php

namespace Selective\Rar;

use DateTimeImmutable;
use Selective\Rar\Converter\BitConverter;
use Selective\Rar\Struct\RarArchiveStruct;
use Selective\Rar\Struct\RarFileHeadStruct;
use Selective\Rar\Struct\RarVolumeHeaderStruct;
use SplFileObject;
use UnexpectedValueException;

/**
 * RAR 5 file reader.
 *
 * Specifications: https://www.rarlab.com/technote.htm#filehead
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
        $this->readRar5MainArchiveHeader($file);

        while (!$file->eof()) {
            // File CRC uint32
            $file->fread(4);

            // Header size
            $this->fileReader->readVint($file);

            $headerType = $this->fileReader->readVint($file);

            // 2 for file header, 3 for service header (CMT = comments, QO, ACL, STM, RR)
            if ($headerType === 2 || $headerType === 3) {
                // File header and service header
                $fileHeader = $this->readRar5FileHeader($file);

                // Append only files
                if ($headerType === 2 && $fileHeader->isDirectory === false) {
                    $rarFile->files[] = $fileHeader;
                }

                continue;
            }

            // End of archive header
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
        $volumeHeader->crc = strtoupper(bin2hex(strrev((string)$file->fread(2))));

        // The header size indicates how many total bytes the header requires
        $volumeHeader->size = $this->fileReader->readVint($file);
        $volumeHeader->type = $this->fileReader->readVint($file);
        // Flags common for all headers
        $volumeHeader->flags = $this->fileReader->readVint($file);
        $extraAreaSize = 0;

        // 0x0001 Volume. Archive is a part of multivolume set.
        if ($this->bit->isFlagSet($volumeHeader->flags, 0x0001)) {
            // Size of extra area. Optional field, present only if 0x0001 header flag is set.
            $extraAreaSize = $this->fileReader->readVint($file);
        }

        $archiveFlags = $this->fileReader->readVint($file);
        if ($this->bit->isFlagSet($archiveFlags, 0x0002)) {
            throw new UnexpectedValueException('Multivolume RAR archive is not supported');
        }

        if ($extraAreaSize) {
            $file->fread($extraAreaSize);
        }

        return $volumeHeader;
    }

    private function readRar5FileHeader(SplFileObject $file): RarFileHeadStruct
    {
        $fileHeader = new RarFileHeadStruct();

        $fileHeader->unpVer = 5;

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
        $fileHeader->method = $this->fileReader->readVint($file);

        // 0 = Windows, 1 = Unix
        $fileHeader->hostOS = $this->fileReader->readVint($file);

        // File or service header name length.
        $fileHeader->nameSize = $this->fileReader->readVint($file);

        // Variable length field containing Name length bytes in UTF-8 format without trailing zero.
        $fileHeader->fileName = (string)$file->fread($fileHeader->nameSize);

        // Optional area containing additional header fields, present only if 0x0001 header flag is set.
        if ($this->bit->isFlagSet($headerFlags, 0x0001) && $extraAreaSize) {
            $extraOffset = $file->ftell();
            // extra area size 2
            $this->fileReader->readVint($file);
            $extraAreaType = $this->fileReader->readVint($file);

            // file time (extra record)
            if ($extraAreaType === 3) {
                $timeFlags = $this->fileReader->readVint($file);

                // Time is stored in Unix time_t format if this flags
                // is set and in Windows FILETIME format otherwise
                if ($this->bit->isFlagSet($timeFlags, 0x0002)) {
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

            // Jump to end of extra record
            $file->fseek($extraOffset + $extraAreaSize);
        }

        // Optional data area, present only if 0x0002 header flag is set.
        // Store file data in case of file header or service data for service header.
        // Depending on the compression method value in Compression information can
        // be either uncompressed (compression method 0) or compressed.
        if ($this->bit->isFlagSet($headerFlags, 0x0002) && $fileHeader->packSize) {
            // Move to end of compresses data
            $file->fseek($file->ftell() + $fileHeader->packSize);
        }

        return $fileHeader;
    }
}
