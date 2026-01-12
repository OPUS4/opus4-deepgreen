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

namespace Opus\DeepGreen;

use Exception;
use Opus\Common\ConfigTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zend_Config;
use Zend_Config_Exception;
use Zend_Config_Ini;

use function dirname;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function getenv;
use function json_decode;
use function json_encode;
use function rtrim;

/**
 * TODO handle 400 bad requests (json error message)
 * TODO add options for most common formats
 */
class DeepGreenClient
{
    use ConfigTrait;

    const FORMAT_FILES_AND_JATS = 'https://datahub.deepgreen.org/FilesAndJATS';
    const FORMAT_SIMPLE_ZIP     = 'http://purl.org/net/sword/package/SimpleZip';
    const FORMAT_OPUS4_ZIP      = 'http://purl.org/net/sword/package/OPUS4Zip';

    /** @var string Base URL for DeepGreen web API */
    private $serviceUrl;

    /** @var string Repository ID for account */
    private $repositoryId;

    /** @var string API key for account */
    private $apiKey;

    /**
     * @param string   $since
     * @param int|null $page
     * @param int|null $size
     * @return mixed
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * TODO Should everything be returned or just the notifications? Array or object?
     */
    public function fetchNotifications($since, $page = null, $size = null)
    {
        $httpClient = $this->getHttpClient();

        // TODO validate $since format AND/OR support Datetime objects

        $serviceUrl = $this->getServiceUrl();
        $apiKey     = $this->getApiKey();
        $repoId     = $this->getRepositoryId();

        $fullUrl = "{$serviceUrl}routed/{$repoId}?since={$since}";

        if ($page !== null) {
            if ($page < 1) {
                throw new DeepGreenException('Page value must be greater than 0');
            }
            $fullUrl .= "&page={$page}";
        }

        if ($size !== null) {
            $fullUrl .= "&pageSize={$size}";
        }

        $fullUrl .= "&api_key={$apiKey}";

        $response = $httpClient->request('GET', $fullUrl);

        $json = $response->getContent();

        return json_decode($json, true);
    }

    /**
     * @param string $notificationId
     * @return array
     * @throws ClientExceptionInterface
     * @throws DeepGreenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchNotification($notificationId)
    {
        $httpClient = $this->getHttpClient();

        $serviceUrl = $this->getServiceUrl();
        $apiKey     = $this->getApiKey();
        $fullUrl    = "{$serviceUrl}notification/{$notificationId}?api_key={$apiKey}";

        $response = $httpClient->request('GET', $fullUrl);

        try {
            $json = $response->getContent();
        } catch (Exception $e) {
            // TODO logging
            return null;
        }

        return json_decode($json, true);
    }

    /**
     * @param array|Notification $notification
     * @param string             $outputFile
     * @param string|null        $format
     * @param callable           $callback
     * @return string Path to downloaded file
     * @throws DeepGreenException
     * @throws TransportExceptionInterface
     *
     * TODO generate output file name if non is provided?
     */
    public function fetchDocument($notification, $outputFile, $format = null, $callback = null)
    {
        if ($format === null) {
            $format = self::FORMAT_FILES_AND_JATS;
        }

        if ($notification instanceof Notification) {
            $notificationObj = $notification;
        } else {
            $notificationObj = new Notification(json_encode($notification));
        }

        $link = $notificationObj->getDownloadLink($format);

        if ($link === null) {
            throw new Exception('Requested format not found');
        }

        $httpClient = $this->getHttpClient();
        $apiKey     = $this->getApiKey();

        $options = [];

        if ($callback !== null) {
            $options['on_progress'] = $callback;
        }

        $downloadLink = "{$link}?api_key={$apiKey}";

        $response   = $httpClient->request('GET', $downloadLink, $options);
        $statusCode = $response->getStatusCode();

        if (200 !== $statusCode) {
            throw new Exception("Error downloading package ({$statusCode})", $statusCode);
        }

        $filePath = $outputFile; // TODO configure default target folder?

        $file = fopen($filePath, 'w');
        foreach ($httpClient->stream($response) as $chunk) {
            fwrite($file, $chunk->getContent());
        }
        fclose($file);

        return $filePath;
    }

    /**
     * @return string Server URL.
     * @throws DeepGreenException
     */
    public function getServiceUrl()
    {
        if ($this->serviceUrl !== null) {
            return $this->serviceUrl;
        }

        $config = $this->getDeepGreenConfig();

        if (isset($config->deepgreen->serviceUrl)) {
            return $config->deepgreen->serviceUrl;
        }

        throw new DeepGreenException('No server URL configured');
    }

    /**
     * @param string|null $serviceUrl
     * @return $this
     */
    public function setServiceUrl($serviceUrl)
    {
        if ($serviceUrl !== null) {
            $this->serviceUrl = rtrim($serviceUrl, '/') . '/';
        } else {
            $this->serviceUrl = null;
        }

        return $this;
    }

    /**
     * @return string DeepGreen API key
     * @throws DeepGreenException
     */
    public function getApiKey()
    {
        if ($this->apiKey !== null) {
            return $this->apiKey;
        }

        $config = $this->getDeepGreenConfig();

        if (isset($config->deepgreen->apiKey)) {
            return $config->deepgreen->apiKey;
        }

        throw new DeepGreenException('No api key provided');
    }

    /**
     * @param string|null $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     * @throws DeepGreenException
     * @throws Zend_Config_Exception
     */
    public function getRepositoryId()
    {
        if ($this->repositoryId !== null) {
            return $this->repositoryId;
        }

        $config = $this->getDeepGreenConfig();

        if (isset($config->deepgreen->repositoryId)) {
            return $config->deepgreen->repositoryId;
        }

        throw new DeepGreenException('No repository id provided');
    }

    /**
     * @param string|null $repositoryId
     * @return $this
     */
    public function setRepositoryId($repositoryId)
    {
        $this->repositoryId = $repositoryId;
        return $this;
    }

    /**
     * @return Zend_Config
     * @throws Zend_Config_Exception
     *
     * TODO remove 'deepgreen.' prefix?
     */
    public function getDeepGreenConfig()
    {
        $configFile = $this->getConfigFilePath();

        if ($configFile === null || ! file_exists($configFile)) {
            // TODO log, but where in standalone mode
            return $this->getConfig();
        }

        return new Zend_Config_Ini($configFile);
    }

    /**
     * @return string
     */
    public function getConfigFilePath()
    {
        $config = $this->getConfig();

        if (! isset($config->deepgreen->configFile)) {
            return dirname(__DIR__) . '/deepgreen.ini';
        }

        return $config->deepgreen->configFile;
    }

    /**
     * @return HttpClientInterface
     */
    protected function getHttpClient()
    {
        return HttpClient::create();
    }

    /**
     * @return string
     */
    public function getDefaultServiceUrl()
    {
        if (getenv('APPLICATION_ENV') !== false || getenv('APPLICATION_ENV') === 'production') {
            return 'https://www.oa-deepgreen.de/api/v1/';
        } else {
            return 'https://test.oa-deepgreen.de/api/v1/';
        }
    }
}
