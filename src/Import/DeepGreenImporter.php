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

use Opus\DeepGreen\DeepGreenClient;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/**
 * Imports one or more documents from DeepGreen.
 *
 * TODO set import parameters like 'since' or list of notifications
 * TODO check if notification has already happened
 */
class DeepGreenImporter
{
    /** @var OutputInterface */
    private $output;

    public function import()
    {
        $output = $this->getOutput();

        $client = new DeepGreenClient();

        $response = $client->fetchNotifications($since);

        $notifications = $response['notifications'];

        foreach ($notifications as $notification) {
            if (! isset($notification['id'])) {
                // TODO log, output
                continue;
            }

            $notificationId = $notification['id'];

            $output->writeln(sprintf('Importing notification ID: %s', $notificationId));

            // $downloadLink = $client->getLinkForFormat($notification, DeepGreenClient::FORMAT_FILES_AND_JATS);

            $outputFile = "download-{$notificationId}.zip";

            try {
                $client->fetchDocument($notification, $outputFile, DeepGreenClient::FORMAT_FILES_AND_JATS);
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
            }
        }

        $output->writeln('');
    }

    public function getOutput(): OutputInterface
    {
        if (null === $this->output) {
            $this->output = new ConsoleOutput();
        }

        return $this->output;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }
}
