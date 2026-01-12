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

namespace OpusTest\DeepGreen\Import;

use DOMDocument;
use Opus\DeepGreen\DeepGreenException;
use Opus\DeepGreen\Import\FilesAndJatsPackage;
use OpusTest\DeepGreen\Fixtures\DeepGreenData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

use function basename;

/**
 * TODO unpack into temporary folder (underneath /build)
 */
class FilesAndJatsPackageTest extends TestCase
{
    /** @var string|null Path to extracted files */
    private $extractedPath;

    /** @var FilesAndJatsPackage */
    private $fixture;

    public function setUp(): void
    {
        parent::setUp();

        $this->extractedPath = null;
        $this->fixture       = null;
    }

    public function tearDown(): void
    {
        $filesystem = new Filesystem();
        if ($this->extractedPath !== null) {
            $filesystem->remove($this->extractedPath);
        }
        if ($this->fixture !== null) {
            $this->fixture->cleanup();
        }
        parent::tearDown();
    }

    public function testConstruct()
    {
        $filePath = DeepGreenData::getPath(DeepGreenData::PACKAGE_1);
        $package  = new FilesAndJatsPackage($filePath);
        $this->assertEquals($filePath, $package->getPath());
    }

    public function testConstructFileNotFound()
    {
        $filePath = APPLICATION_PATH . '/test/unknownFile.zip';

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage("[{$filePath}] does not exist");
        new FilesAndJatsPackage($filePath);
    }

    public function testUnpack()
    {
        $package             = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_1));
        $this->extractedPath = $package->unpack();
        $this->assertFileExists($this->extractedPath . '/' . DeepGreenData::METADATA_FILE_1);
    }

    public function testUnpackTwice()
    {
        $package = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_1));

        $extractedPath1      = $package->unpack();
        $this->extractedPath = $extractedPath1;

        $this->assertFileExists($this->extractedPath . '/' . DeepGreenData::METADATA_FILE_1);

        $extractedPath2 = $package->unpack();
        $this->assertEquals($extractedPath1, $extractedPath2);
    }

    public function testUnpackFolder()
    {
        $fixture             = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_2));
        $this->extractedPath = $fixture->unpack();

        $package = new FilesAndJatsPackage($this->extractedPath);

        $this->assertEquals($this->extractedPath, $package->unpack());
    }

    public function testGetMetadataXml()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_2));

        $metdataXml = $this->fixture->getMetadataXml();

        $this->assertInstanceOf(DOMDocument::class, $metdataXml);
    }

    public function testGetMetadataFile()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_2));

        $metdataFile = $this->fixture->getMetadataFile();

        $this->assertFileExists($metdataFile);
        $this->assertEquals(DeepGreenData::METADATA_FILE_2, basename($metdataFile));
    }

    public function testGetMetadataFileNotFound()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_3));
        $extractedPath = $this->fixture->unpack();
        $filesystem    = new Filesystem();
        $filesystem->remove($extractedPath . '/' . DeepGreenData::METADATA_FILE_3);

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('Metadata XML file not found.');
        $this->fixture->getMetadataFile();
    }

    public function testGetMetadataFileMoreThanOneXmlFile()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_3));
        $extractedPath = $this->fixture->unpack();
        $filesystem    = new Filesystem();
        $filesystem->touch($extractedPath . '/additional.xml');

        $this->expectException(DeepGreenException::class);
        $this->expectExceptionMessage('More than one XML file found.');
        $this->fixture->getMetadataFile();
    }

    public function testGetFiles()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_2));
        $this->fixture->unpack();

        $files = $this->fixture->getFiles();

        $this->assertCount(2, $files);
        $this->assertNotContains(DeepGreenData::METADATA_FILE_2, $files);
    }

    public function testCleanup()
    {
        $package       = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_1));
        $extractedPath = $package->unpack();

        $this->assertDirectoryExists($extractedPath);
        $this->assertFileExists($extractedPath . '/' . DeepGreenData::METADATA_FILE_1);

        $package->cleanup();

        $this->assertDirectoryDoesNotExist($extractedPath);
    }

    public function testCleanupTwice()
    {
        $package       = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_1));
        $extractedPath = $package->unpack();

        $this->assertDirectoryExists($extractedPath);
        $this->assertFileExists($extractedPath . '/' . DeepGreenData::METADATA_FILE_1);

        $package->cleanup();

        $this->assertDirectoryDoesNotExist($extractedPath);

        $package->cleanup();
    }

    public function testCleanupDisabledIfNotUnpacked()
    {
        $this->fixture = new FilesAndJatsPackage(DeepGreenData::getPath(DeepGreenData::PACKAGE_1));
        $extractedPath = $this->fixture->unpack();

        $package = new FilesAndJatsPackage($extractedPath);

        $package->unpack(); // already unpacked so this should do nothing

        $this->assertDirectoryExists($extractedPath);
        $this->assertFileExists($extractedPath . '/' . DeepGreenData::METADATA_FILE_1);

        $package->cleanup();

        $this->assertDirectoryExists($extractedPath);
        $this->assertFileExists($extractedPath . '/' . DeepGreenData::METADATA_FILE_1);
    }
}
