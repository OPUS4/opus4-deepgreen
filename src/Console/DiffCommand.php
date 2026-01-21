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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TODO diff FilesAndJats with OPUS 4 document with same DOI
 * TODO diff Notification-ID with OPUS 4 document
 */
class DiffCommand extends Command
{
    const OPTION_OPUS_ID = 'opusid';

    const ARGUMENT_INPUT = 'deepgreen';

    public function configure()
    {
        parent::configure();

        $help = <<<EOT
The <info>deepgreen:diff</info> command compares a DeepGreen document with a 
document stored in OPUS 4. 

This command can be used to analyse updates for documents coming from DeepGreen.
EOT;

        $this->setName('deepgreen:diff')
            ->setDescription('Compares DeepGreen data with existing document <error>TODO</error>')
            ->setHelp($help)
            ->addOption(
                self::OPTION_OPUS_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'OPUS 4 document ID'
            )
            ->addArgument(
                self::ARGUMENT_INPUT,
                InputArgument::REQUIRED,
                'Notification ID | FilesAndJats file'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // TODO if argument is notification ID retrieve notification

        // TODO check if DOI ist present in database, if not stop here

        // TODO retrieve and unpack package
        // TODO parse and create Document (without storing it)

        // TODO compare documents and print differences

        return self::SUCCESS;
    }
}
