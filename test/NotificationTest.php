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

namespace OpusTest\DeepGreen;

use Opus\DeepGreen\DeepGreenClient;
use Opus\DeepGreen\DeepGreenException;
use Opus\DeepGreen\Notification;
use OpusTest\DeepGreen\Fixtures\DeepGreenData;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class NotificationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $json               = file_get_contents(DeepGreenData::getPath(DeepGreenData::NOTIFICATION_1));
        $this->notification = new Notification($json);
    }

    public function testConstruct()
    {
        $json = file_get_contents(DeepGreenData::getPath(DeepGreenData::NOTIFICATION_2));

        $notification = new Notification($json);

        $this->assertEquals(DeepGreenData::NOTIFICATION_ID_2, $notification->getId());
    }

    public function testConstructEmptyString()
    {
        $this->expectException(DeepGreenException::class);
        $this->expectExceptionMessage('Not valid JSON');
        new Notification('');
    }

    public function testConstructInvalidNotification()
    {
        $json = <<<JSON
{
  "created-date": "2025-12-15T06:46:51Z"
}
JSON;

        $this->expectException(DeepGreenException::class);
        $this->expectExceptionMessage('Not valid notification');
        new Notification($json);
    }

    public function testGetDownloadFormats()
    {
        $formats = $this->notification->getDownloadFormats();

        $this->assertEqualsCanonicalizing([
            'FilesAndJATS' => DeepGreenClient::FORMAT_FILES_AND_JATS,
            'SimpleZip'    => DeepGreenClient::FORMAT_SIMPLE_ZIP,
        ], $formats);
    }

    public function testGetDownloadLink()
    {
        $link = $this->notification->getDownloadLink();

        $this->assertEquals(
            'https://www.oa-deepgreen.de/api/v1/notification/9c04a870275840c3a857fc382dff373f/content',
            $link
        );
    }

    public function testGetDownloadLinkForFormat()
    {
        $link = $this->notification->getDownloadLink(DeepGreenClient::FORMAT_SIMPLE_ZIP);

        $this->assertEquals(
            'https://www.oa-deepgreen.de/api/v1/notification/9c04a870275840c3a857fc382dff373f/content/SimpleZip.zip',
            $link
        );
    }

    public function testGetDownloadLinkForUnknownFormat()
    {
        $this->expectException(DeepGreenException::class);
        $this->expectExceptionMessage('Format not available');
        $this->notification->getDownloadLink('unknownFormat');
    }

    public function testToArray()
    {
        $data = $this->notification->toArray();

        $this->assertIsArray($data);
        $this->assertEquals(DeepGreenData::NOTIFICATION_ID_1, $data['id']);
    }
}
