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
 * @copyright   Copyright (c) 2024, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\DeepGreen\Console;

use Opus\DeepGreen\Import\FilesAndJatsImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\MimeTypes;

use function date;
use function file_exists;
use function is_dir;
use function strtotime;

/**
 * TODO import FilesAndJats file directly
 * TODO import folder with files
 * TODO import list of Notifications (JSON)
 * TODO import single notification
 */
class ImportCommand extends Command
{
    public const OPTION_NOTIFICATION_ID = 'nid'; // TODO find better name?

    public const OPTION_SINCE = 'since';

    public const ARGUMENT_INPUT = 'input';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
Retrieves document(s) from DeepGreen and imports them into OPUS 4.

If OPUS 4 already contains a document for the DeepGreen notification ID, it
will not be imported again.

If OPUS 4 already contains a document with the same DOI, the new package
will be stored in the <info>inbox</info> folder for manual review.

The <info>import</info> command can be used in various ways. 

Import notifications for the last month. Imports will include notifications
starting from the same day in the previous month. 

  <info>deepgreen import</info>
 
Import notifications starting from a specific date.
  
  <info>deepgreen import --since 2025-01-01</info>
  
Import a single DeepGreen notification.
  
  <info>deepgreen import [NOTIFICATION_ID]</info>
  
Import notifications stored in a JSON file.
  
  <info>deepgreen import [JSON FILE]</info>

Import a single FilesAndJATS package file.

  <info>deepgreen import [FilesAndJATS.zip]</info>
  
Import entire folder with a FilesAndJATS package.

  <info>deepgreen import [FOLDER]</info>
EOT;

        $this->setName('deepgreen:import')
            ->setDescription('Imports documents from DeepGreen')
            ->setHelp($help)
            ->addOption(
                self::OPTION_SINCE,
                null,
                InputOption::VALUE_REQUIRED,
                'Start date (YYYY-MM-DD)'
            )
            ->addArgument(
                self::ARGUMENT_INPUT,
                InputArgument::OPTIONAL,
                'Notification-ID|file|folder'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $argument = $input->getArgument(self::ARGUMENT_INPUT);

        if ($argument === null) {
            return $this->importFromServer($input, $output);
        }

        if (file_exists($argument)) {
            if (is_dir($argument)) {
                return $this->importFromFolder($input, $output, $argument);
            } else {
                return $this->importFromFile($input, $output, $argument);
            }
        }

        return $this->importFromServer($input, $output, $argument);
    }

    protected function importFromServer(InputInterface $input, OutputInterface $output, ?string $notificationId = null): int
    {
        $output->writeln('Importing from DeepGreen...');

        $since = $input->getOption(self::OPTION_SINCE);

        if ($since === null) {
            $since = date('Y-m-d', strtotime('-1 month'));
        }

        return self::SUCCESS;
    }

    protected function importFromFile(InputInterface $input, OutputInterface $output, string $path): int
    {
        $mimeTypes = new MimeTypes();
        $mimeType  = $mimeTypes->guessMimeType($path);

        switch ($mimeType) {
            case "application/json":
                return $this->importFromJson($input, $output, $path);

            case "application/zip":
                return $this->importFilesAndJats($input, $output, $path);

            default:
                $output->writeln("<error>Unknown MIME type: {$mimeType}</error>");
                return self::FAILURE;
        }
    }

    /**
     * TODO Folder can only be a single package because packages can contain ZIP files. How to distinguish between
     *      package and folder with multiple packed packages? Maybe use an option? Or try to detect metadata XML file?
     */
    protected function importFromFolder(InputInterface $input, OutputInterface $output, string $path): int
    {
        return self::SUCCESS;
    }

    protected function importFromJson(InputInterface $input, OutputInterface $output, string $path): int
    {
        return self::SUCCESS;
    }

    protected function importFilesAndJats(InputInterface $input, OutputInterface $output, string $path): int
    {
        $importer = new FilesAndJatsImporter();

        $importer->import($path);

        return self::SUCCESS;
    }
}
