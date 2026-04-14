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

use DateTime;

use function is_array;
use function json_decode;
use function strrpos;
use function strtolower;
use function substr;

/**
 * TODO can a Notification object be reused (it probably should not)
 * TODO allow construction from array|file
 */
class Notification
{
    /** @var array */
    private $data;

    public function __construct(string|array $json)
    {
        if (is_array($json)) {
            $this->data = $json;
        } else {
            $this->load($json);
        }
    }

    protected function load(string $json): void
    {
        $data = json_decode($json, true);

        if ($data === null) {
            throw new DeepGreenException('Not valid JSON');
        }

        $this->data = $data;

        if (! $this->isValid()) {
            throw new DeepGreenException('Not valid notification (id or links missing)');
        }
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getDoi(): string|null
    {
        $identifiers = $this->data['metadata']['identifier'];

        foreach ($identifiers as $identifier) {
            if ($identifier['type'] === 'doi') {
                return $identifier['id'];
            }
        }

        return null;
    }

    public function getCreatedDate(): DateTime
    {
        return new DateTime($this->data['created_date']);
    }

    public function getDownloadFormats(): array
    {
        $data = $this->data;

        if (! isset($data['links'])) {
            throw new DeepGreenException('No links found for document.');
        }

        $links = $data['links'];

        $formats = [];

        foreach ($links as $link) {
            $format          = $link['packaging'];
            $label           = substr($format, strrpos($format, '/') + 1);
            $formats[$label] = $format;
        }

        return $formats;
    }

    public function getDownloadLink(?string $format = null): string|null
    {
        $data = $this->data;

        if (! isset($data['links'])) {
            throw new DeepGreenException('No links found for document.');
        }

        if (null === $format) {
            $format = DeepGreenClient::FORMAT_FILES_AND_JATS;
        }

        $links  = $data['links'];
        $format = strtolower($format);

        foreach ($links as $link) {
            if (strtolower($link['packaging']) === $format) {
                return $link['url'];
            }
        }

        throw new DeepGreenException('Format not available for document.');
    }

    public function toArray(): array
    {
        return $this->data;
    }

    protected function isValid(): bool
    {
        $data = $this->data;

        if (! isset($data['id'])) {
            return false;
        }

        if (! isset($data['links'])) {
            return false;
        }

        return true;
    }
}
