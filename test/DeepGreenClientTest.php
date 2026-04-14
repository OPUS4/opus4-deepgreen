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
use PHPUnit\Framework\TestCase;
use Zend_Config;

class DeepGreenClientTest extends TestCase
{
    /** @var DeepGreenClient */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new DeepGreenClient();
        $this->client->setConfig(new Zend_Config([
            'deepgreen' => [
                'configFile'   => 'unknownConfigFile.ini', // do not load local configuration TODO better way?
                'serviceUrl'   => 'https://test.oa-deepgreen.de/api/v1/',
                'apiKey'       => 'testKey',
                'repositoryId' => 'testRepositoryId',
            ],
        ]));
    }

    public function testGetConfigFilePath()
    {
        $this->client->setConfig(null); // Use default configuration
        $this->assertEquals(APPLICATION_PATH . '/deepgreen.ini', $this->client->getConfigFilePath());
    }

    public function testGetDeepGreenConfig()
    {
        $config = $this->client->getDeepGreenConfig();

        $this->assertNotNull($config);
    }

    public function testGetServiceUrl()
    {
        $this->assertEquals('https://test.oa-deepgreen.de/api/v1/', $this->client->getServiceUrl());
    }

    public function testSetServiceUrl()
    {
        $this->client->setServiceUrl('https://example.org/api/v1');
        $this->assertEquals('https://example.org/api/v1/', $this->client->getServiceUrl());

        $this->client->setServiceUrl(null);
        $this->assertEquals('https://test.oa-deepgreen.de/api/v1/', $this->client->getServiceUrl());
    }

    public function testGetApiKey()
    {
        $this->assertEquals('testKey', $this->client->getApiKey());
    }

    public function testSetApiKey()
    {
        $this->client->setApiKey('mykey');
        $this->assertEquals('mykey', $this->client->getApiKey());

        $this->client->setApiKey(null);
        $this->assertEquals('testKey', $this->client->getApiKey());
    }

    public function testGetRepositoryId()
    {
        $this->assertEquals('testRepositoryId', $this->client->getRepositoryId());
    }

    public function testSetRepositoryId()
    {
        $this->client->setRepositoryId('myRepositoryId');
        $this->assertEquals('myRepositoryId', $this->client->getRepositoryId());

        $this->client->setRepositoryId(null);
        $this->assertEquals('testRepositoryId', $this->client->getRepositoryId());
    }

    public function testGetDefaultServiceUrl()
    {
        $this->assertEquals('https://test.oa-deepgreen.de/api/v1/', $this->client->getDefaultServiceUrl());
    }
}
