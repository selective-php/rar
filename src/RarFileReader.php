<?php

namespace Selective\Rar;

use DateTimeImmutable;
use Selective\Rar\Converter\BitConverter;
use Selective\Rar\Converter\DateTimeConverter;
use Selective\Rar\Flag\RarFileBitFlag;
use Selective\Rar\Flag\RarVolumeHeaderFlag;
use Selective\Rar\Struct\RarExtTimeStruct;
use Selective\Rar\Struct\RarArchiveStruct;
use Selective\Rar\Struct\RarFileHeadStruct;
use Selective\Rar\Struct\RarMainHeadStruct;
use Selective\Rar\Struct\RarVolumeHeaderStruct;
use SplFileObject;

/**
 * RAR file reader.
 *
 * Specifications:
 *
 * https://www.forensicswiki.org/wiki/RAR
 * https://www.rarlab.com/technote.htm#dtypes
 * https://codedread.github.io/bitjs/docs/unrar.html
 * http://acritum.com/winrar/rar-format
 * https://www.loc.gov/preservation/digital/formats/fdd/fdd000450.shtml
 * https://www.opennet.ru/docs/formats/rar2.txt
 * http://www.iesleonardo.com/ele/gs/Utilidades/WinRAR/TechNote.txt
 * http://www.forensicswiki.org/w/images/5/5b/RARFileStructure.txt
 * https://formats.kaitai.io/rar/index.html
 * https://rarfile.readthedocs.io/en/latest/news.html
 */
final class RarFileReader
{
    /**
     * @var BitConverter
     */
    private $bit;

    /**
     * @var DateTimeConverter
     */
    private $dateTime;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->bit = new BitConverter();
        $this->dateTime = new DateTimeConverter();
    }

    /**
     * Open RAR file.
     *
     * @param SplFileObject $file The rar file
     *
     * @return RarArchive The RAR archive
     */
    public function openFile(SplFileObject $file): RarArchive
    {
        $file->rewind();

        return $this->createRarArchive($this->createRarArchiveStruct($file));
    }

    /**
     * Create RarArchive instance.
     *
     * @param RarArchiveStruct $rarArchiveStruct The archive struct
     *
     * @return RarArchive The RAR archive
     */
    private function createRarArchive(RarArchiveStruct $rarArchiveStruct): RarArchive
    {
        $rarArchive = new RarArchive();

        foreach ($rarArchiveStruct->files as $file) {
            $entry = new RarEntry();

            $rarArchive = $rarArchive->addEntry($entry
                ->withAttr($file->fileAttr)
                ->withCrc($file->fileCRC)
                ->withFileTime($file->fileTime)
                ->withHostOs($file->hostOS)
                ->withMethod($file->method)
                ->withName($file->fileName)
                ->withPackedSize($file->packSize)
                ->withUnpackedSize($file->unpackSize)
                ->withVersion($file->unpVer)
                ->withIsDirectory(false)
                ->withIsEncrypted(false));
        }

        return $rarArchive;
    }

    /**
     * Create struct instance.
     *
     * @param SplFileObject $file The rar file
     *
     * @return RarArchiveStruct The result
     */
    private function createRarArchiveStruct(SplFileObject $file): RarArchiveStruct
    {
        $rarFile = new RarArchiveStruct();

        $rarFile->signature = (string)$file->fread(7);

        while (!$file->eof()) {
            $volumeHeader = $this->readRarVolumeHeader($file);

            if ($volumeHeader->type === RarVolumeHeaderFlag::MAIN_HEAD) {
                $rarFile->mainHead = $this->readRarMainHead($file);
                $rarFile->volumeHeaders[] = $volumeHeader;

                // Jump to next block
                $file->fseek($volumeHeader->headerSize + $volumeHeader->blockSize);

                continue;
            }

            if ($volumeHeader->type === RarVolumeHeaderFlag::FILE_HEAD) {
                $rarFile->files[] = $this->readFileHead($file, $volumeHeader);
                continue;
            }
            if ($volumeHeader->type === RarVolumeHeaderFlag::ENDARC_HEAD) {
                break;
            }
        }

        return $rarFile;
    }

    /**
     * Read file header.
     *
     * @param SplFileObject $file The file
     * @param RarVolumeHeaderStruct $rarVolumeHeader The header
     *
     * @return RarFileHeadStruct The result
     */
    private function readFileHead(SplFileObject $file, RarVolumeHeaderStruct $rarVolumeHeader): RarFileHeadStruct
    {
        $fileHead = new RarFileHeadStruct();

        // Compressed file size
        $fileHead->packSize = $rarVolumeHeader->addSize;

        // Lower uncompressed file size
        $fileHead->lowUnpackSize = $this->readBigInt($file);

        // Operating system used for archiving
        $fileHead->hostOS = $this->readByte($file);

        // File CRC
        $fileHead->fileCRC = strtoupper(bin2hex(strrev((string)$file->fread(4))));

        // Date and time in standard MS DOS format
        $fileHead->fileTime = $this->dateTime->getGetDateTimeFromDosDateTime(
            $this->readInt($file),
            $this->readInt($file)
        );

        // RAR version
        // Version number is encoded as: 10 * Major version + minor version.
        $fileHead->unpVer = $this->readByte($file);

        // Packing method: 0x30 - 0x35
        $fileHead->method = $this->readByte($file);

        // File name size
        $fileHead->nameSize = $this->readInt($file);

        // File attributes
        $fileHead->fileAttr = $this->readBigInt($file);

        // Optional value, presents only if bit 0x100 in HEAD_FLAGS is set.
        if ($this->bit->isFlagSet($rarVolumeHeader->flags, RarFileBitFlag::HIGH_PACK_AND_UNP_SIZE)) {
            // High 4 bytes of 64-bit value of compressed file size.
            $fileHead->highPackSize = $this->readBigInt($file);
            // High 4 bytes of 64-bit value of uncompressed file size.
            $fileHead->highUnpackSize = $this->readBigInt($file);
        }

        $fileHead->unpackSize = $this->bit->toInt64(
            (string)$fileHead->highUnpackSize,
            (string)$fileHead->lowUnpackSize
        );

        //$gb = $fileHead->unpackSize / 1024 / 1024 / 1024;

        // File name - string of NAME_SIZE bytes size
        $fileHead->fileName = (string)$file->fread($fileHead->nameSize);

        // Optional
        if ($this->bit->isFlagSet($rarVolumeHeader->flags, RarFileBitFlag::HEADER_WITH_SALT)) {
            $fileHead->salt = (string)$file->fread(8);
        }

        // Optional
        if ($this->bit->isFlagSet($rarVolumeHeader->flags, RarFileBitFlag::EXTENDED_TIME_FIELD)) {
            $fileHead->extTime = $this->readExtTime($file, $fileHead->fileTime);
        }
        $start2 = $file->ftell();

        //$packedData = (string)$file->fread($fileHead->packSize);

        // Jump to end of block
        $file->fseek($start2 + $rarVolumeHeader->addSize - 1);
        $headerByte = $this->readByte($file);

        if ($headerByte === RarVolumeHeaderFlag::ENDARC_HEAD) {
            $file->fseek($start2 + $rarVolumeHeader->addSize - 3);
        } else {
            $file->fseek($start2 + $rarVolumeHeader->addSize);
        }

        return $fileHead;
    }

    /**
     * Read file time record.
     *
     * https://www.rarlab.com/technote.htm#timerecord
     * https://github.com/markokr/rarfile/blob/master/rarfile.py#L2686
     * https://github.com/vadmium/vadmium.github.com/blob/master/rar.md
     * https://github.com/larrykoubiak/minirar/blob/master/rar_time.c#L8
     *
     * @param SplFileObject $file The file
     * @param DateTimeImmutable $fileTime The base file time
     *
     * @return RarExtTimeStruct The result
     */
    private function readExtTime(SplFileObject $file, DateTimeImmutable $fileTime): RarExtTimeStruct
    {
        $extTime = new RarExtTimeStruct();

        // The block is in every case 5 bytes long (i.e. 0x00 0xF0 0x70 0x38 0x39 or 0x00 0xF0 0x32 0x24 0x45)

        // Flags and rest of data can be missing
        $extTime->flags = 0;
        if ($file->ftell() + 2 >= $file->getSize()) {
            return $extTime;
        }

        // 2 bytes
        $extTime->flags = $this->readInt($file);

        if ($this->bit->isFlagSet($extTime->flags, 0x0001)) {
            // Timestamp 32-bit
            $extTime->isUnixFormat = true;
        } else {
            // Windows file time format (64-bit)
            $extTime->isUnixFormat = false;
        }

        $pos = (int)$file->ftell();

        $extTime->mtime = $this->parseExtTime($extTime->flags >> 3 * 4, $file, $fileTime);

        $file->fseek($pos);
        $extTime->ctime = $this->parseExtTime($extTime->flags >> 2 * 4, $file);

        $file->fseek($pos);
        $extTime->atime = $this->parseExtTime($extTime->flags >> 1 * 4, $file);

        $file->fseek($pos);
        $extTime->arctime = $this->parseExtTime($extTime->flags >> 0 * 4, $file);

        // 2 bytes + 3 bytes = 5 bytes (fix)
        $file->fseek($pos + 3);

        return $extTime;
    }

    /**
     * Parse EXT_TIME to date time.
     *
     * @param int $flag The flag
     * @param SplFileObject $file The file
     * @param DateTimeImmutable|null $baseTime The base time
     *
     * @return DateTimeImmutable|null The date time or null
     */
    private function parseExtTime(
        int $flag,
        SplFileObject $file,
        DateTimeImmutable $baseTime = null
    ): ?DateTimeImmutable {
        // Must be valid
        if (!$this->bit->isFlagSet($flag, 0x0008)) {
            return null;
        }

        if ($baseTime === null) {
            $baseTime = $this->dateTime->getGetDateTimeFromDosDateTime($this->readInt($file), $this->readInt($file));
        }

        // load second fractions
        $reminder = 0;
        $count = $flag & 3;
        for ($i = 0; $i < $count; $i++) {
            $byte = $this->readByte($file);
            $reminder = ($byte << 16) | ($reminder >> 8);
        }

        // Convert 100ns units to microseconds
        $usec = $reminder; // 10
        if ($usec > 1000000) {
            $usec = 999999;
        }

        // Dostime has room for 30 seconds only, correct if needed
        // 0x4000: If set, adds 1 s to the DOS time
        if ($flag & 4 && $baseTime->format('s') < 59) {
            return $baseTime->modify('+1 s');
        } else {
            // Replace microseconds
            return $baseTime->modify(sprintf('+%s ms', $usec));
        }
    }

    /**
     * Read main head.
     *
     * @param SplFileObject $file The file
     *
     * @return RarMainHeadStruct The result
     */
    private function readRarMainHead(SplFileObject $file): RarMainHeadStruct
    {
        $mainHead = new RarMainHeadStruct();
        $mainHead->highPosAv = $this->readInt($file);
        $mainHead->posAv = $this->readBigInt($file);
        $mainHead->encryptVer = $this->readByte($file);

        return $mainHead;
    }

    /**
     * Read volume header.
     *
     * @param SplFileObject $file The file
     *
     * @return RarVolumeHeaderStruct The result
     */
    private function readRarVolumeHeader(SplFileObject $file): RarVolumeHeaderStruct
    {
        $volumeHeader = new RarVolumeHeaderStruct();

        $volumeHeader->crc = strtoupper(bin2hex(strrev((string)$file->fread(2))));
        $volumeHeader->type = $this->readByte($file);
        $volumeHeader->flags = $this->readInt($file);
        $volumeHeader->size = $this->readInt($file);

        $volumeHeader->blockSize = $volumeHeader->size;
        $volumeHeader->hasAdd = ($volumeHeader->flags & 0x8000) != 0;
        $volumeHeader->headerSize = $volumeHeader->hasAdd ? 11 : 7;
        $volumeHeader->bodySize = $volumeHeader->blockSize;

        $volumeHeader->addSize = 0;
        if ($volumeHeader->hasAdd) {
            // Compressed size
            $volumeHeader->addSize = $this->readBigInt($file);
            $volumeHeader->bodySize = $volumeHeader->blockSize - $volumeHeader->headerSize;
            $volumeHeader->blockSize = $volumeHeader->size + $volumeHeader->addSize;
        }

        return $volumeHeader;
    }

    /**
     * Reade 4 bytes and convert to big int.
     *
     * @param SplFileObject $file The file
     *
     * @return int The value
     */
    private function readBigInt(SplFileObject $file): int
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
    private function readInt(SplFileObject $file): int
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
    private function readByte(SplFileObject $file): int
    {
        return ord((string)$file->fread(1));
    }

    /**
     * Convert 2 bytes to unsigned little-endian int.
     *
     * @param string $data The data
     *
     * @return int The value
     */
    private function getInt(string $data): int
    {
        $result = unpack('v', $data)[1]; // 2 bytes

        return (int)$result;
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
        $result = unpack('V', $data)[1]; // 4 bytes

        return (int)$result;
    }
}
