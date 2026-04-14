<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2025, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\DeepGreen\Import;

use DirectoryIterator;
use DOMDocument;
use Opus\DeepGreen\DeepGreenException;
use Opus\Import\Extract\ZipPackageExtractor;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

use function basename;
use function count;
use function file_exists;
use function glob;
use function is_dir;
use function is_readable;

/**
 * TODO proper function depends on order of function calls - make robust!
 */
class FilesAndJatsPackage
{
    /** @var string Path to package file or folder */
    private $path;

    /** @var string Path to extracted files */
    private $extractedPath;

    /** @var string Name of metadata XML file */
    private $metadataFile;

    /** @var bool Enable/disable cleanup of extracted files */
    private $cleanup = true;

    public function __construct(string $path)
    {
        if (! file_exists($path) || ! is_readable($path)) {
            throw new FileNotFoundException("File [$path] does not exist.");
        }

        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getExtractedPath(): string|null
    {
        return $this->extractedPath;
    }

    public function unpack(): string
    {
        if (null !== $this->extractedPath) {
            return $this->extractedPath;
        }

        if (! is_dir($this->path)) {
            $extractor = new ZipPackageExtractor();

            // TODO where to extract to?
            $this->extractedPath = $extractor->extract($this->path);
        } else {
            $this->extractedPath = $this->path;
            $this->cleanup       = false;
        }

        return $this->extractedPath;
    }

    public function cleanup()
    {
        if ($this->cleanup && $this->extractedPath !== null) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->extractedPath);
            $this->extractedPath = null;
        }
    }

    public function getMetadataXml(): DOMDocument
    {
        $this->metadataFile = $this->getMetadataFile();

        $metadataXml = new DOMDocument();
        $metadataXml->load($this->metadataFile);

        return $metadataXml;
    }

    public function getMetadataFile(): string
    {
        if (null === $this->extractedPath) {
            $this->unpack();
        }

        $xmlFiles = glob($this->extractedPath . '/*.xml');

        if (! $xmlFiles || count($xmlFiles) === 0) {
            throw new FileNotFoundException('Metadata XML file not found.');
        }

        if (count($xmlFiles) > 1) {
            throw new DeepGreenException('More than one XML file found.');
        }

        $this->metadataFile = basename($xmlFiles[0]);

        return $xmlFiles[0];
    }

    /**
     * TODO option to return complete paths?
     */
    public function getFiles(): array
    {
        if (null === $this->metadataFile) {
            $this->getMetadataFile();
        }

        $files = [];

        $iterator = new DirectoryIterator($this->extractedPath);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $filename = $fileInfo->getBasename();
                if ($filename === $this->metadataFile) {
                    continue;
                }
                $files[] = $filename;
            }
        }

        return $files;
    }
}
