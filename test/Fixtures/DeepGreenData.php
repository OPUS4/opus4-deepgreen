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

namespace OpusTest\DeepGreen\Fixtures;

class DeepGreenData
{
    const PACKAGE_1 = 'deepgreen_9c04a870275840c3a857fc382dff373f.zip';
    const PACKAGE_2 = 'deepgreen_9f2781200c20437792934e670f687798.zip';
    const PACKAGE_3 = 'deepgreen_f36774908e794a3ebf03e130483d3381.zip';

    const METADATA_FILE_1 = 'soil-11-1095-2025.xml';
    const METADATA_FILE_2 = 'genes-16-01377.xml';
    const METADATA_FILE_3 = 'fcvm-12-1650138.xml';

    const NOTIFICATION_1 = 'notification_9c04a870275840c3a857fc382dff373f.json';
    const NOTIFICATION_2 = 'notification_9f2781200c20437792934e670f687798.json';
    const NOTIFICATION_3 = 'notification_f36774908e794a3ebf03e130483d3381.json';

    const NOTIFICATION_ID_1 = '9c04a870275840c3a857fc382dff373f';
    const NOTIFICATION_ID_2 = '9f2781200c20437792934e670f687798';
    const NOTIFICATION_ID_3 = 'f36774908e794a3ebf03e130483d3381';

    public static function getPath(string $file): string
    {
        return APPLICATION_PATH . '/test/Fixtures/files/' . $file;
    }
}
