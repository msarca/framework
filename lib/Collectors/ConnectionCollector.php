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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Collector;
use Opis\Colibri\Serializable\ConnectionList;

/**
 * Class ConnectionCollector
 * @package Opis\Colibri\Collectors
 * @method ConnectionList   data()
 */
class ConnectionCollector extends Collector
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new ConnectionList());
    }


    /**
     * @param string $name
     * @param callable $callback
     * @return ConnectionCollector
     */
    public function create(string $name, callable $callback): self
    {
        $this->dataObject->set($name, call_user_func($callback, $this->app));
        return $this;
    }
}
