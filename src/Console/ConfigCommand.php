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

use Exception;
use Opus\DeepGreen\DeepGreenClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

use function date;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function is_writable;

use const PHP_EOL;

class ConfigCommand extends Command
{
    /** @var QuestionHelper */
    private $questionHelper;

    protected function configure()
    {
        parent::configure();

        $help = <<<EOT
The <info>deepgreen:config</info> queries the user for the necessary information
to create the configuration file for the DeepGreen connection.
EOT;

        $this->setName('deepgreen:config')
            ->setDescription('Configure deepgreen configuration')
            ->setHelp($help);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = new DeepGreenClient();

        $defaultServiceUrl = $client->getDefaultServiceUrl();

        $output->writeln('DeepGreen connection configuration' . PHP_EOL);

        $serviceUrl   = $this->askForServiceUrl($input, $output, $defaultServiceUrl);
        $repositoryId = $this->askForRepositoryId($input, $output);
        $apiKey       = $this->askForApiKey($input, $output);

        try {
            $overwriteDefault = $this->checkConnection($input, $output, $serviceUrl, $repositoryId, $apiKey);
        } catch (Exception $ex) {
            $output->writeln(PHP_EOL . '<error>' . $ex->getMessage() . '</error>' . PHP_EOL);
            return self::FAILURE;
        }

        $configFilePath = $this->getConfigFilePath($input, $output, $overwriteDefault);

        if ($configFilePath === null) {
            return self::FAILURE;
        }

        $output->writeln(PHP_EOL . 'Writing configuration file...');
        $output->writeln($configFilePath);
        $this->writeConfigFile($configFilePath, $serviceUrl, $repositoryId, $apiKey);

        return self::SUCCESS;
    }

    protected function askForServiceUrl(InputInterface $input, OutputInterface $output, string $default): string
    {
        $ask = $this->getQuestionHelper();

        $question = new Question(
            "Please enter API URL (<info>{$default}</info>): ",
            $default
        );
        return $ask->ask($input, $output, $question);
    }

    protected function askForRepositoryId(InputInterface $input, OutputInterface $output): string
    {
        $ask = $this->getQuestionHelper();

        $question = new Question('Please enter account ID: ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new Exception('The account ID is required.');
            }

            return $answer;
        });
        $question->setMaxAttempts(3);
        return $ask->ask($input, $output, $question);
    }

    protected function askForApiKey(InputInterface $input, OutputInterface $output): string
    {
        $ask = $this->getQuestionHelper();

        $question = new Question('Please enter API key: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new Exception('The API key is required.');
            }

            return $answer;
        });
        $question->setMaxAttempts(3);
        return $ask->ask($input, $output, $question);
    }

    protected function checkConnection(
        InputInterface $input,
        OutputInterface $output,
        string $serviceUrl,
        string $repositoryId,
        string $apiKey
    ): bool {
        $ask = $this->getQuestionHelper();

        $question = new ConfirmationQuestion(PHP_EOL . 'Check connection (Y/n)? ', true);

        if ($ask->ask($input, $output, $question)) {
            $output->writeln('Checking connection to DeepGreen web API...');

            $client = new DeepGreenClient();
            $client->setServiceUrl($serviceUrl);
            $client->setRepositoryId($repositoryId);
            $client->setApiKey($apiKey);

            $since = date('Y-m-d');

            $client->fetchNotifications($since);

            // TODO check response?
            $output->writeln('<info>Connection OK</info>');

            return true;
        }

        return false;
    }

    protected function getConfigFilePath(
        InputInterface $input,
        OutputInterface $output,
        bool $overwriteDefault
    ): string|null {
        $client         = new DeepGreenClient();
        $configFilePath = $client->getConfigFilePath();
        $defaultAnswer  = $overwriteDefault ? '(Y/n)' : '(y/N)';
        $question       = new ConfirmationQuestion(
            PHP_EOL . "Overwrite existing file {$defaultAnswer}? ",
            $overwriteDefault
        );

        if (file_exists($configFilePath)) {
            if (is_writable($configFilePath)) {
                $ask = $this->getQuestionHelper();
                if (! $ask->ask($input, $output, $question)) {
                    $output->writeln('Not overwriting existing file.');
                    return null;
                }
            } else {
                $output->writeln('<error>Cannot overwrite existing config file. Check file permissions!</error>');
                return null;
            }
        }

        return $configFilePath;
    }

    protected function writeConfigFile(
        string $configFilePath,
        string $serviceUrl,
        string $repositoryId,
        string $apiKey
    ): void {
        $configFile = fopen($configFilePath, 'w');
        fwrite($configFile, "deepgreen.serviceUrl={$serviceUrl}\n");
        fwrite($configFile, "deepgreen.repositoryId={$repositoryId}\n");
        fwrite($configFile, "deepgreen.apiKey={$apiKey}\n");
        fclose($configFile);

        // Set file permissions
        $filesystem = new Filesystem();
        $filesystem->chmod($configFilePath, 0640);
    }

    protected function getQuestionHelper(): QuestionHelper
    {
        if ($this->questionHelper === null) {
            $this->questionHelper = new QuestionHelper();
        }
        return $this->questionHelper;
    }
}
