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

use DOMDocument;
use XSLTProcessor;

use const DIRECTORY_SEPARATOR;

/**
 * Converts JATS to OPUS-XML.
 *
 * TODO should this class be able to load JATS from a file?
 */
class JatsToOpusConverter
{
    /**
     * @param DOMDocument $jats
     * @return DOMDocument
     */
    public function convert($jats)
    {
        $proc = $this->getProcessor();

        return $proc->transformToDoc($jats);
    }

    public function getProcessor(): XSLTProcessor
    {
        $proc = new XSLTProcessor();
        $proc->importStylesheet($this->getStylesheet());
        $proc->registerPHPFunctions([
            'Opus\I18n\Languages::getPart2b', // TODO find way to keep implementing class out of XSLT
        ]);
        return $proc;
    }

    public function getStylesheet(): DOMDocument
    {
        $stylesheet = __DIR__ . DIRECTORY_SEPARATOR . 'jats2opus_xml.xslt';

        $xslt = new DOMDocument();
        $xslt->load($stylesheet);

        return $xslt;
    }
}
