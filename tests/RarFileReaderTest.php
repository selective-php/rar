<?php

namespace Selective\Rar\Test;

use PHPUnit\Framework\TestCase;
use Selective\Rar\RarFileReader;
use SplFileObject;

/**
 * Test.
 */
class RarFileReaderTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testOpenFile(): void
    {
        $filename = __DIR__ . '/files/test.rar';

        self::assertFileExists($filename);

        $fileReader = new RarFileReader();

        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        foreach ($rarArchive->getEntries() as $entry) {
            echo $entry->getName() . "\n";
        }

        $entries = $rarArchive->getEntries();
        self::assertSame(2, $entries[0]->getHostOs());
        self::assertSame('test.txt', $entries[0]->getName());
        self::assertSame('test2.txt', $entries[1]->getName());
        self::assertSame('261DAEE5', $entries[0]->getCrc());
    }

    /**
     * Test.
     *
     * @dataProvider providerTestOpenFile2
     *
     * @param string $filename The filename
     *
     * @return void
     */
    public function disabledTestOpenFile2(string $filename): void
    {
        self::assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $ration = 0;

        // http://www.aerasec.de/security/advisories/decompression-bomb-vulnerability.html
        foreach ($rarArchive->getEntries() as $file) {
            $compressedSize = $file->getPackedSize();
            $originalSize = $file->getUnpackedSize();
            $ration = $originalSize / $compressedSize;
        }

        self::assertSame(3, $rarArchive->getEntries()[0]->getHostOs());
        self::assertCount(1, $rarArchive->getEntries());
        self::assertNotEmpty($rarArchive->getEntries()[0]->getName());
        self::assertGreaterThan(2000, $ration);
    }

    /**
     * Provider.
     *
     * @return array<mixed>
     */
    public function providerTestOpenFile2(): array
    {
        $result = [];
        $result[] = [__DIR__ . '/files/excluded/10GB.rar'];
        $result[] = [__DIR__ . '/files/excluded/30GB.rar'];
        $result[] = [__DIR__ . '/files/excluded/50GB.rar'];
        $result[] = [__DIR__ . '/files/excluded/100GB.rar'];
        $result[] = [__DIR__ . '/files/excluded/200GB.rar'];
        $result[] = [__DIR__ . '/files/excluded/300GB.rar'];

        return $result;
    }
}
