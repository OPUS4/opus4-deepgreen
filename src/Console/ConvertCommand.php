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

use DOMDocument;
use Opus\DeepGreen\Import\JatsToOpusConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function file_exists;
use function file_get_contents;

/**
 * Converts DeepGreen JATS to OPUS 4 XML.
 */
class ConvertCommand extends Command
{
    const ARGUMENT_JATS = 'jats';

    protected function configure()
    {
        parent::configure();

        $help = <<<HELP
The <info>deepgreen:convert</info> command allows you to convert JATS-XML files
to OPUS-XML. This can be used for testing during development or debugging.
HELP;

        $this->setName('deepgreen:convert')
            ->setDescription('Convert JATS-XML files to OPUS-XML')
            ->setHelp($help)
            ->addArgument(
                self::ARGUMENT_JATS,
                InputArgument::REQUIRED,
                'JATS-XML file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jatsFile = $input->getArgument(self::ARGUMENT_JATS);

        if (! file_exists($jatsFile)) {
            $output->writeln('<error>JATS-XML file not found</error>');
            return Command::FAILURE;
        }

        $jatsXml = file_get_contents($jatsFile);

        $jats = new DOMDocument();
        $jats->loadXml($jatsXml);

        $converter = new JatsToOpusConverter();
        $opus      = $converter->convert($jats);

        $opus->formatOutput       = true;
        $opus->preserveWhiteSpace = true;

        $output->writeln($opus->saveXML());

        return Command::SUCCESS;
    }
}
