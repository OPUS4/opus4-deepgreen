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
use Opus\App\Common\Configuration;
use Opus\Common\Repository;
use Opus\DeepGreen\DeepGreenClient;
use Opus\DeepGreen\DeepGreenException;
use Opus\DeepGreen\Notification;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Stopwatch\Stopwatch;

use function count;
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

    /** @var string|null */
    private $downloadBasePath;

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

        $output->writeln(sprintf('Received <info>%d</info> notifications', count($notifications)));

        $importedCount = 0;
        $errorCount    = 0;
        $skippedCount  = 0;

        $filesystem = new Filesystem();

        // TODO make sure download folder exists
        $downloadBasePath = $this->getDownloadBasePath();
        if (! $filesystem->exists($downloadBasePath)) {
            $filesystem->mkdir($downloadBasePath);
        }

        foreach ($notifications as $notification) {
            if (! isset($notification['id'])) {
                // TODO log, output
                continue;
            }

            $notificationObj = new Notification($notification);

            $notificationId = $notificationObj->getId();

            $existingDocId = $this->getDocumentForNotification($notificationId);
            if (count($existingDocId) > 0) {

                $output->writeln(sprintf('Notification <info>%s</info> : Already imported (document <info>%d</info>)', $notificationId, implode(', ', $existingDocId)));
                $skippedCount++;
                continue;
            }

            $doi = $notificationObj->getDoi();
            if ($this->isDoiExists($doi)) {
                $output->writeln(sprintf('Notification <info>%s</info> : Document with DOI <info>%s</info> exists', $notificationId, $doi));
                $skippedCount++;
                continue;
            }

            $output->writeln(sprintf('Notification <info>%s</info> : Importing...', $notificationId));

            // TODO location underneath workspace
            $outputFile = Path::join($downloadBasePath, "deepgreen-{$notificationId}.zip");

            $output->writeln(sprintf('Download to %s', $outputFile), OutputInterface::VERBOSITY_DEBUG);

            try {
                $client->fetchDocument($notification, $outputFile, DeepGreenClient::FORMAT_FILES_AND_JATS);
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
                $errorCount++;
                continue;
            }

            $keepFile = false;

            try {
                $importer = new FilesAndJatsImporter();
                $importer->setOutput($output);
                $importer->import($outputFile, $notificationId);
                $importedCount++;
            } catch (Exception $ex) {
                $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));
                $errorCount++;
                $keepFile = true;
            } finally {
                // TODO keep output file if duplicate DOI (move to inbox)
                // TODO keep output file if error (move to import/error)? Move to inbox, tagged with '-error'?
                if (! $keepFile) {
                    $filesystem = new Filesystem();
                    $filesystem->remove($outputFile);
                }
            }
        }

        $event = $stopwatch->stop('import');

        $errorStyle = $errorCount > 0 ? '<error>%d</error>' : '<info>%d</info>';

        $output->writeln(sprintf(
            "DeepGreen import finished (%s, %s, Documents new <info>%d</info>, skipped <comment>%d</comment>, errors {$errorStyle})",
            Helper::formatMemory($event->getMemory()),
            Helper::formatTime($event->getDuration() / 1000, 3),
            $importedCount,
            $skippedCount,
            $errorCount
        ));
    }

    public function getDocumentForNotification(string $notificationId): array
    {
        $finder = Repository::getInstance()->getDocumentFinder();

        $finder->setEnrichmentValue('deepgreen.notificationId', $notificationId);

        return $finder->getIds();
    }

    public function isDoiExists(string $doi): bool
    {
        $finder = Repository::getInstance()->getDocumentFinder();

        $finder->setIdentifierValue('doi', $doi);

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

    public function getDownloadBasePath(): string
    {
        if ($this->downloadBasePath === null) {
            $workspacePath          = Configuration::getInstance()->getWorkspacePath();
            $this->downloadBasePath = Path::join($workspacePath, 'deepgreen');
        }

        return $this->downloadBasePath;
    }

    public function setDownloadBasePath(string $path): self
    {
        $this->downloadBasePath = $path;
        return $this;
    }
}
