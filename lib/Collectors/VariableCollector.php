<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Application;
use Opis\Colibri\Collector;
use Opis\Colibri\Serializable\VariablesList;

/**
 * Class VariableCollector
 *
 * @package Opis\Colibri\Collectors
 *
 * @method VariablesList    data()
 */
class VariableCollector extends Collector
{

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new VariablesList());
    }

    /**
     * Register a new variable
     *
     * @param   string $name Variable's name
     * @param   mixed $value Variable's value
     *
     * @return  self
     */
    public function register($name, $value)
    {
        $this->dataObject->add($name, $value);
        return $this;
    }

    /**
     * Register multiple variable at once
     *
     * @param   array $variables An array of variables that will be registered
     *
     * @return  self
     */
    public function bulkRegister(array $variables)
    {
        foreach ($variables as $name => &$value) {
            $this->dataObject->add($name, $value);
        }

        return $this;
    }
}
