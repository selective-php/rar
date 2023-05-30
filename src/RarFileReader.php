<?php

namespace Selective\Rar;

use Selective\Rar\Struct\RarArchiveStruct;
use SplFileObject;
use UnexpectedValueException;

/**
 * RAR file reader.
 */
final class RarFileReader
{
    /**
     * @var Rar4FileReader
     */
    private $rar4;

    /**
     * @var Rar5FileReader
     */
    private $rar5;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->rar4 = new Rar4FileReader();
        $this->rar5 = new Rar5FileReader();
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

            $rarArchive = $rarArchive->addEntry(
                $entry
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
                    ->withIsEncrypted(false)
            );
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

        $this->readSignature($file, $rarFile);

        if ($rarFile->version === 4) {
            $this->rar4->readRarFile($file, $rarFile);
        }

        if ($rarFile->version === 5) {
            $this->rar5->readRarFile($file, $rarFile);
        }

        return $rarFile;
    }

    private function readSignature(SplFileObject $file, RarArchiveStruct $rarFile): void
    {
        $signature = bin2hex((string)$file->fread(7));

        if ($signature === '526172211a0700') {
            // Rar 4 signature
            $rarFile->version = 4;
            $rarFile->signature = $signature;

            return;
        }

        $file->fseek(0);
        $signature = bin2hex((string)$file->fread(8));

        if ($signature === '526172211a070100') {
            // Rar 5 signature
            $rarFile->version = 5;
            $rarFile->signature = $signature;

            return;
        }

        throw new UnexpectedValueException('This is not a valid RAR file');
    }
}
