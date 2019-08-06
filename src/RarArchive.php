<?php

namespace Selective\Rar;

/**
 * A RAR archive.
 */
final class RarArchive
{
    /**
     * @var RarEntry[]
     */
    private $entries = [];

    /**
     * Get entries.
     *
     * @return RarEntry[] The entries
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Add entry.
     *
     * @param RarEntry $entry The rar entry
     *
     * @return self
     */
    public function addEntry(RarEntry $entry): self
    {
        $clone = clone $this;
        $clone->entries[] = $entry;

        return $clone;
    }
}
