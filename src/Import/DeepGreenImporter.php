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

use Exception;
use Opus\Common\Repository;
use Opus\DeepGreen\DeepGreenClient;
use Opus\DeepGreen\DeepGreenException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;

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

    /**
     * @param string $since
     * @return void
     * @throws DeepGreenException
     *
     * TODO use progress bar for interactive import
     */
    public function import($since)
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('import');

        $output = $this->getOutput();

        $client = new DeepGreenClient();

        $response = $client->fetchNotifications($since);

        $notifications = $response['notifications'];

        $importedCount = 0;
        $errorCount    = 0;
        $skippedCount  = 0;

        foreach ($notifications as $notification) {
            if (! isset($notification['id'])) {
                // TODO log, output
                continue;
            }

            $notificationId = $notification['id'];

            if ($this->isAlreadyImported($notificationId))
            {
                $output->writeln(sprintf('Notification %s has already been imported.', $notificationId));
                $skippedCount++;
                continue;
            }

            $output->writeln(sprintf('Importing notification ID: %s', $notificationId));

            // TODO location underneath workspace
            $outputFile = "download-{$notificationId}.zip";

            try {
                $client->fetchDocument($notification, $outputFile, DeepGreenClient::FORMAT_FILES_AND_JATS);
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
                $errorCount++;
                continue;
            }

            try {
                $importer = new FilesAndJatsImporter();
                $importer->import($outputFile, $notificationId);
                $importedCount++;
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
                $errorCount++;
            } finally {
                // TODO keep output file if duplicate DOI (move to inbox)
                // TODO keep output file if error (move to import/error)? Move to inbox, tagged with '-error'?
                $filesystem = new Filesystem();
                $filesystem->remove($outputFile);
            }
        }

        $event = $stopwatch->stop('import');

        $output->writeln(sprintf(
            'DeepGreen import finished (%s, %s)',
            Helper::formatMemory($event->getMemory()),
            Helper::formatTime($event->getDuration() / 1000, 3)
        ));

        // TODO output as table (is this verbose)?
        $output->writeln(sprintf("  %s \tNew documents", $importedCount));
        $output->writeln(sprintf("  %s \tSkipped documents (already imported)", $skippedCount));
        $output->writeln(sprintf("  %s \tDocuments with errors", $errorCount));
    }

    public function isAlreadyImported(string $notificationId): bool
    {
        $finder = Repository::getInstance()->getDocumentFinder();

        $finder->setEnrichmentValue('deepgreen.notificationId', $notificationId);

        $documentIds = $finder->getIds();

        return count($documentIds) > 0;
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
