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

use Opus\DeepGreen\DeepGreenException;
use Opus\Import\AdditionalEnrichments;
use Opus\Import\SwordImporter;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function basename;
use function sprintf;

use const PHP_EOL;

/**
 * Imports a FilesAndJats package.
 *
 * TODO Where should the DOI check go?
 * TODO refactor class and use parent class to avoid duplicating basic import workflow for OPUS 4
 * TODO should this extend the SwordImporter? Probably not because it creates undesired dependencies
 */
class FilesAndJatsImporter
{
    /** @var OutputInterface */
    private $output;

    /**
     * @return void
     * @throws DeepGreenException
     *
     * TODO return Document or docId?
     */
    public function import(string $path, ?string $notificationId = null)
    {
        $output = $this->getOutput();

        $package       = new FilesAndJatsPackage($path);
        $extractedPath = $package->unpack();
        $metadataFile  = basename($package->getMetadataFile());

        $output->writeln(sprintf('Load metadata file <info>%s</info>', $metadataFile), OutputInterface::VERBOSITY_DEBUG);

        $metadataXml = $package->getMetadataXml();

        $converter = new JatsToOpusConverter(); // TODO get from factory method (and support injection)

        $opusXml = $converter->convert($metadataXml);

        $importer = new SwordImporter($opusXml);
        $importer->setOutput($output);
        $importer->setIgnoreFiles($metadataFile);

        $enrichments = new AdditionalEnrichments(); // TODO better way without class dependency (maybe getting object from Importer)
        if ($notificationId !== null) {
            $enrichments->addEnrichment('deepgreen.notificationId', $notificationId);
        }
        $enrichments->setSource('deepgreen');
        // TODO set checksum on $enrichments?
        $importer->setAdditionalEnrichments($enrichments);

        $importer->setImportDir($extractedPath);

        // TODO do not add metadata file to Document
        try {
            $importer->run();
        } catch (DeepGreenException $ex) {
        } finally {
            $package->cleanup();
        }

        $docId = $importer->getDocumentIds();

        if (! empty($docId)) {
            if ($notificationId !== null) {
                $output->writeln(sprintf('Notification <info>%s</info> : Created document <info>%d</info>', $notificationId, $docId));
            } else {
                $output->writeln(sprintf('Created document <info>%d</info>' . PHP_EOL, $docId));
            }
        } else {
            if ($notificationId !== null) {
                throw new DeepGreenException(sprintf('Notification %s : Import failed', $notificationId));
            } else {
                throw new DeepGreenException('Import failed');
            }
        }

        // TODO do not store document if DOI is already present in database
        //      Optionally query user?

        // TODO import works, but are all files added?

        // TODO check for duplicate DOI (can this be done earlier?)
        //      delegate check to external, configurable class (should not be responsibility of this class)

        // TODO Post processing (import rules)
    }

    public function getOutput(): OutputInterface
    {
        if ($this->output === null) {
            $this->output = new NullOutput();
        }

        return $this->output;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;
        return $this;
    }
}
