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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function date;
use function file_put_contents;
use function json_encode;
use function strtotime;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * TODO option for getting all notifications (multiple pages)
 */
class CheckCommand extends Command
{
    const OPTION_OUTPUT = 'output';

    const OPTION_SINCE = 'since';

    const OPTION_PAGE = 'page';

    const OPTION_PAGE_SIZE = 'size';

    const ARGUMENT_NOTIFICATION_ID = 'nid';

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
The <info>deepgreen:check</info> command retrieves available notifications from
DeepGreen and optionally stores them in a file.
EOT;

        $this->setName('deepgreen:check')
            ->setDescription('Retrieves notifications from DeepGreen')
            ->setHelp($help)
            ->addOption(
                self::OPTION_OUTPUT,
                null,
                InputOption::VALUE_REQUIRED,
                'Output file path'
            )
            ->addOption(
                self::OPTION_SINCE,
                null,
                InputOption::VALUE_REQUIRED,
                'Start date (YYYY-MM-DD)'
            )
            ->addOption(
                self::OPTION_PAGE,
                'p',
                InputOption::VALUE_REQUIRED,
                'Page number'
            )
            ->addOption(
                self::OPTION_PAGE_SIZE,
                's',
                InputOption::VALUE_REQUIRED,
                'Size of page'
            )
            ->addArgument(
                self::ARGUMENT_NOTIFICATION_ID,
                InputArgument::OPTIONAL,
                'Notification ID'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new DeepGreenClient();

        $notificationId = $input->getArgument(self::ARGUMENT_NOTIFICATION_ID);

        if ($notificationId === null) {
            $since = $input->getOption(self::OPTION_SINCE);

            if ($since === null) {
                $since = date('Y-m-d', strtotime('-1 month'));
            }

            $page = $input->getOption(self::OPTION_PAGE);
            $size = $input->getOption(self::OPTION_PAGE_SIZE);

            $response = $client->fetchNotifications($since, $page, $size);
        } else {
            $response = $client->fetchNotification($notificationId);
        }

        $outputFile = $input->getOption(self::OPTION_OUTPUT);

        if ($outputFile !== null) {
            // TODO check if file already exists?
            file_put_contents($outputFile, json_encode($response, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        } else {
            $output->writeln(json_encode($response, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
        }

        if ($notificationId === null) {
            $notifications = $response['notifications'];
            $output->writeln('Notifications found: ' . count($notifications));
        }

        return Command::SUCCESS;
    }
}
