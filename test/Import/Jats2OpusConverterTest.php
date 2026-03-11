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
use DOMXPath;
use Opus\Common\Document;
use Opus\DeepGreen\Import\JatsToOpusConverter;
use Opus\I18n\Languages;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;

class Jats2OpusConverterTest extends TestCase
{
    /** @var JatsToOpusConverter */
    private $converter;

    public function setUp(): void
    {
        parent::setUp();

        $this->converter = new JatsToOpusConverter();
    }

    public function testConvert()
    {
        $jatsXml = file_get_contents(dirname(__DIR__) . '/Fixtures/files/example-jats.xml');

        $jatsDom = new DOMDocument();
        $jatsDom->loadXML($jatsXml);

        $opusXml = $this->converter->convert($jatsDom);

        $xpath = new DOMXPath($opusXml);

        $this->assertEquals('eng', $xpath->query('//opusDocument/@language')->item(0)->nodeValue);
    }

    public function testGetStylesheet()
    {
        $stylesheet = $this->converter->getStylesheet();

        $this->assertNotNull($stylesheet);
    }

    /**
     * TODO move somewhere else or remove
     */
    public function testDatabaseAvailable()
    {
        $doc = Document::new();

        $docId = $doc->store();

        $doc = Document::get($docId);

        $this->assertNotNull($doc);
    }

    public function testLanguageMapping()
    {
        $this->assertEquals('eng', Languages::getPart2b('EN'));
    }
}
