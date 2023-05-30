<?php

namespace Selective\Rar;

use DateTimeImmutable;

/**
 * A RAR entry, representing a directory or a compressed file inside a RAR archive.
 */
final class RarEntry
{
    /**
     * @var int
     */
    private $attr = 0;

    /**
     * @var string
     */
    private $crc = '';

    /**
     * @var DateTimeImmutable
     */
    private $fileTime;

    /**
     * @var int
     */
    private $hostOs = 0;

    /**
     * @var int
     */
    private $method = 0;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var int
     */
    private $packedSize = 0;

    /**
     * @var int
     */
    private $unpackedSize = 0;

    /**
     * @var int
     */
    private $version = 0;

    /**
     * @var bool
     */
    private $isDirectory = false;

    /**
     * @var bool
     */
    private $isEncrypted = false;

    /**
     * Get value.
     *
     * @return int The attr
     */
    public function getAttr(): int
    {
        return $this->attr;
    }

    /**
     * Get value.
     *
     * @return string The crc
     */
    public function getCrc(): string
    {
        return $this->crc;
    }

    /**
     * Get value.
     *
     * @return DateTimeImmutable The fileTime
     */
    public function getFileTime(): DateTimeImmutable
    {
        return $this->fileTime;
    }

    /**
     * Get value.
     *
     * @return int The hostOs
     */
    public function getHostOs(): int
    {
        return $this->hostOs;
    }

    /**
     * Get value.
     *
     * @return int The method
     */
    public function getMethod(): int
    {
        return $this->method;
    }

    /**
     * Get value.
     *
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get value.
     *
     * @return float The packedSize
     */
    public function getPackedSize(): float
    {
        return $this->packedSize;
    }

    /**
     * Get value.
     *
     * @return int The unpackedSize
     */
    public function getUnpackedSize(): int
    {
        return $this->unpackedSize;
    }

    /**
     * Get value.
     *
     * @return int The version
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Get value.
     *
     * @return bool The isDirectory
     */
    public function isDirectory(): bool
    {
        return $this->isDirectory;
    }

    /**
     * Get value.
     *
     * @return bool The isEncrypted
     */
    public function isEncrypted(): bool
    {
        return $this->isEncrypted;
    }

    /**
     * Set value.
     *
     * @param int $attr The attr
     *
     * @return self
     */
    public function withAttr(int $attr): self
    {
        $clone = clone $this;
        $clone->attr = $attr;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param string $crc The crc
     *
     * @return self
     */
    public function withCrc(string $crc): self
    {
        $clone = clone $this;
        $clone->crc = $crc;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param DateTimeImmutable $fileTime The fileTime
     *
     * @return self
     */
    public function withFileTime(DateTimeImmutable $fileTime): self
    {
        $clone = clone $this;
        $clone->fileTime = $fileTime;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param int $hostOs The host OS
     *
     * @return self
     */
    public function withHostOs(int $hostOs): self
    {
        $clone = clone $this;
        $clone->hostOs = $hostOs;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param int $method The method
     *
     * @return self
     */
    public function withMethod(int $method): self
    {
        $clone = clone $this;
        $clone->method = $method;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param string $name The name
     *
     * @return self
     */
    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param int $packedSize The packedSize
     *
     * @return self
     */
    public function withPackedSize(int $packedSize): self
    {
        $clone = clone $this;
        $clone->packedSize = $packedSize;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param int $unpackedSize The unpackedSize
     *
     * @return self
     */
    public function withUnpackedSize(int $unpackedSize): self
    {
        $clone = clone $this;
        $clone->unpackedSize = $unpackedSize;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param int $version The version
     *
     * @return self
     */
    public function withVersion(int $version): self
    {
        $clone = clone $this;
        $clone->version = $version;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param bool $isDirectory The isDirectory
     *
     * @return self
     */
    public function withIsDirectory(bool $isDirectory): self
    {
        $clone = clone $this;
        $clone->isDirectory = $isDirectory;

        return $clone;
    }

    /**
     * Set value.
     *
     * @param bool $isEncrypted The isEncrypted
     *
     * @return self
     */
    public function withIsEncrypted(bool $isEncrypted): self
    {
        $clone = clone $this;
        $clone->isEncrypted = $isEncrypted;

        return $clone;
    }
}
