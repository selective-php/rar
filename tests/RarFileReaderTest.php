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
    public function testOpenFileRar4(): void
    {
        $filename = __DIR__ . '/files/test.rar';
        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(2, $entries);
        // 2 = Windows
        $this->assertSame(2, $entries[0]->getHostOs());
        $this->assertSame('test.txt', $entries[0]->getName());
        $this->assertSame('test2.txt', $entries[1]->getName());
        $this->assertSame('261DAEE5', $entries[0]->getCrc());
    }

    public function testOpenFileRar5Unix(): void
    {
        $filename = __DIR__ . '/files/test-rar5-unix.rar';
        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(5, $entries[0]->getVersion());
        $this->assertSame(0, $entries[0]->getMethod()); // 0 = No compression
        $this->assertSame(1, $entries[0]->getHostOs());
        $this->assertSame('testfile.txt', $entries[0]->getName());
        $this->assertSame('6EC18FFE', $entries[0]->getCrc());
    }

    public function testOpenFileRar5ChinaWindows(): void
    {
        date_default_timezone_set('UTC');

        $filename = __DIR__ . '/files/test-rar5-china-win.rar';
        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(2, $entries);
        $this->assertSame(9, $entries[0]->getUnpackedSize());
        $this->assertSame('2023-05-30 07:56:30', $entries[0]->getFileTime()->format('Y-m-d H:i:s'));
        $this->assertSame('test2/很好。.txt', $entries[0]->getName());
        $this->assertSame('10F28531', $entries[0]->getCrc());

        $this->assertSame(12, $entries[1]->getUnpackedSize());
        $this->assertSame('2023-05-30 07:55:36', $entries[1]->getFileTime()->format('Y-m-d H:i:s'));
        $this->assertSame('test2/祝你一天过得愉快。.txt', $entries[1]->getName());
        $this->assertSame('E3C94841', $entries[1]->getCrc());
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
        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $ration = 0;

        // http://www.aerasec.de/security/advisories/decompression-bomb-vulnerability.html
        foreach ($rarArchive->getEntries() as $file) {
            $compressedSize = $file->getPackedSize();
            $originalSize = $file->getUnpackedSize();
            $ration = $originalSize / $compressedSize;
        }

        $this->assertSame(3, $rarArchive->getEntries()[0]->getHostOs());
        $this->assertCount(1, $rarArchive->getEntries());
        $this->assertNotEmpty($rarArchive->getEntries()[0]->getName());
        $this->assertGreaterThan(2000, $ration);
    }

    /**
     * Provider.
     *
     * @return array
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

    public function testOpenFileRarWinRar(): void
    {
        $filename = __DIR__ . '/files/test-rar5-winrar.rar';

        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(2, $entries);
        $this->assertSame(0, $entries[0]->getHostOs());
        $this->assertSame('Manuscript-Symmetry/Figures.jpg', $entries[0]->getName());
        $this->assertSame('9CFE7142', $entries[0]->getCrc());
    }

    public function testOpenFileRarWinRar02(): void
    {
        $filename = __DIR__ . '/files/test-rar5-winrar-02.rar';

        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(2, $entries);
        $this->assertSame(5, $entries[0]->getVersion());
        $this->assertSame(0, $entries[0]->getHostOs());
        $this->assertSame('test.txt', $entries[0]->getName());
        $this->assertSame('261DAEE5', $entries[0]->getCrc());
        $this->assertSame(5, $entries[0]->getMethod()); // 5 = Best compression method
        $this->assertSame('2019-08-04 15:06:49', $entries[0]->getFileTime()->format('Y-m-d H:i:s'));
        $this->assertSame('test2.txt', $entries[1]->getName());
        $this->assertSame('261DAEE5', $entries[1]->getCrc());
    }

    public function testOpenFileRarWinRarMultiVolume0001(): void
    {
        $filename = __DIR__ . '/files/test-rar5-multivolume.rar-part0001.rar';

        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('dummy.pdf', $entries[0]->getName());
    }

    public function testOpenFileRarWinRarMultiVolume0002(): void
    {
        $filename = __DIR__ . '/files/test-rar5-multivolume.rar-part0002.rar';

        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertCount(1, $entries);
        $this->assertSame('dummy.pdf', $entries[0]->getName());
    }

    /**
     * @dataProvider providerTestOpenFileTestfile
     * @param string $filename
     */
    public function testOpenFileTestfile(string $filename): void
    {
        $this->assertFileExists($filename);

        $fileReader = new RarFileReader();
        $rarArchive = $fileReader->openFile(new SplFileObject($filename));

        $entries = $rarArchive->getEntries();
        $this->assertGreaterThan(0, count($entries));

        foreach ($entries as $entry) {
            if ($entry->getName() === 'testfile.jpg') {
                $this->assertSame('DA70B16C', $entry->getCrc());
            }
            if ($entry->getName() === 'testfile.txt') {
                $this->assertSame('6EC18FFE', $entry->getCrc());
            }
        }
    }

    /**
     * Provider.
     *
     * @return array
     */
    public static function providerTestOpenFileTestfile(): array
    {
        $files = (array)glob(__DIR__ . '/files/build/*.*');
        $result = [];

        foreach ($files as $file) {
            $result[basename($file)] = [$file];
        }

        return $result;
    }
}
