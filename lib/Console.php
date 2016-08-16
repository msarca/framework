<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Colibri;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use function Opis\Colibri\Helpers\{app};

class Console
{
    /**
     * Run a command
     */
    public function run()
    {
        $application = new ConsoleApplication();

        foreach ($this->commands() as $command) {
            $application->add($command);
        }

        $application->run();
    }

    /**
     *  Get a list of commands
     *
     * @return  Command[]
     */
    public function commands(): array
    {
        $commands = [];

        foreach (app()->getCollector()->getCommands() as $name => $builder) {
            $commands[$name] = call_user_func($builder);
        }

        return $commands;
    }
}
