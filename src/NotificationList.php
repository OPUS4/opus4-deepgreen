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

use function count;
use function json_decode;

class NotificationList
{
    /** @var array */
    private $data;

    public function __construct(string $json)
    {
        $this->load($json);
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

    protected function isValid(): bool
    {
        $data = $this->data;

        if (! isset($data['since'])) {
            return false;
        }

        if (! isset($data['notifications'])) {
            return false;
        }

        return true;
    }

    public function getSince(): string
    {
        return $this->data['since'];
    }

    /**
     * Add notifications to list.
     */
    public function addNotifications(self $notifications)
    {
    }

    public function addNotification(Notification $notification)
    {
    }

    public function getNotifications(): array
    {
    }

    public function getCount(): int
    {
        return count($this->data['notifications']);
    }

    public function isSorted(): bool
    {
    }
}
