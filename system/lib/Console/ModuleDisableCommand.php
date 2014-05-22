<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Opis\Colibri\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;

use Opis\Colibri\App;
use Opis\Colibri\Module;


class ModuleDisableCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('disable')
            ->setDescription('Disable a module')
            ->addArgument('module', InputArgument::IS_ARRAY, 'A list of modules separated by space')
            ->addOption('uninstall', 'u', InputOption::VALUE_NONE, 'Uninstall modules');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('b-error', new OutputFormatterStyle('white', 'red', array('bold')));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('b-warning', new OutputFormatterStyle('yellow', null, array('bold')));
        $output->getFormatter()->setStyle('b-info', new OutputFormatterStyle('green', null, array('bold')));
        
        $modules = $input->getArgument('module');
        $uninstall = $input->getOption('uninstall');
        
        foreach($modules as $module)
        {
            if(!Module::exists($module))
            {
                $output->writeln('<error>Module <b-error>' . $module . '</b-error> doesn\'t exist.</error>');
                continue;
            }
            
            if(!Module::isEnabled($module))
            {
                $output->writeln('<warning>Module <b-warning>' . $module . '</b-warning> is already disabled.</warning>');
                continue;
            }
            
            if(Module::disable($module))
            {
                $output->writeln('<info>Module <b-info>' . $module . '</b-info> was disabled.</info>');
                
                if($uninstall)
                {
                    $command = $this->getApplication()->find('uninstall');
                    $args = array(
                        'command' => 'uninstall',
                        'module' => array($module),
                    );
                    
                    $command->run(new ArrayInput($args), $output);
                }
            }
            else
            {
                $output->writeln('<error>Module <b-error>' . $module . '</b-error> could not be disbled.</error>');
            }
            
            
        }
    }
    
}
