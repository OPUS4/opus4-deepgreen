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

namespace Opus\DeepGreen\Console;

use Opus\DeepGreen\DeepGreenClient;
use Opus\DeepGreen\Notification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

use function array_keys;
use function json_encode;
use function sprintf;
use function strtolower;

class DownloadCommand extends Command
{
    const OPTION_OUTPUT = 'output';

    const OPTION_FORMAT = 'format';

    const ARGUMENT_NOTIFICATION_ID = 'nid';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
The <info>deepgreen:download</info> command downloads the data file for a document.
EOT;

        $this->setName('deepgreen:download')
            ->setDescription('Retrieves document for DeepGreen notification')
            ->setHelp($help)
            ->addOption(
                self::OPTION_OUTPUT,
                null,
                InputOption::VALUE_REQUIRED,
                'Output file path'
            )
            ->addOption(
                self::OPTION_FORMAT,
                null,
                InputOption::VALUE_REQUIRED,
                'Download format'
            )
            ->addArgument(
                self::ARGUMENT_NOTIFICATION_ID,
                InputArgument::REQUIRED,
                'Notification ID'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new DeepGreenClient();

        $notificationId = $input->getArgument(self::ARGUMENT_NOTIFICATION_ID);

        $response = $client->fetchNotification($notificationId);

        if ($response === null) {
            $output->writeln('<error>Notification not found</error>');
            return self::FAILURE;
        }

        $notification = new Notification(json_encode($response));

        $formatOption = $input->getOption(self::OPTION_FORMAT);
        $formats      = $notification->getDownloadFormats();
        $formatKey    = null;

        if ($formatOption !== null) {
            foreach ($formats as $label => $formatUri) {
                if (strtolower($label) === strtolower($formatOption)) {
                    $formatKey = $label;
                    break;
                }
            }
            if ($formatKey === null) {
                $output->writeln('<error>Format not available</error>');
                return self::FAILURE;
            }
        }

        if ($formatKey === null) {
            $helper = new QuestionHelper();

            $question = new ChoiceQuestion(
                'Please select download format (defaults to FilesAndJATS)',
                array_keys($formats),
                0
            );

            $formatKey = $helper->ask($input, $output, $question);
        }

        $format = $formats[$formatKey];

        $output->writeln(sprintf('<info>Download format %s</info>', $formatKey), OutputInterface::VERBOSITY_VERBOSE);

        $outputFile = $input->getOption(self::OPTION_OUTPUT);

        if ($outputFile === null) {
            // TODO include format in name?
            // TODO generate name from metadata?
            $outputFile = "download-{$notificationId}.zip";
        }

        $progress = new ProgressBar($output, 100);

        $client->fetchDocument(
            $notification,
            $outputFile,
            $format,
            function (int $dlNow, int $dlSize, array $info) use ($progress) {
                if ($dlSize > 0) {
                    $progress->setMaxSteps($dlSize); // TODO do only once?
                    $progress->setProgress($dlNow);
                }
            },
        );

        $progress->finish();

        return Command::SUCCESS;
    }
}
