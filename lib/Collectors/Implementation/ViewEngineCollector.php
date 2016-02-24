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

namespace Opis\Colibri\Collectors\Implementation;

use Closure;
use Opis\Colibri\Application;
use Opis\View\EngineResolver;
use Opis\Colibri\Collectors\AbstractCollector;
use Opis\Colibri\Collectors\ViewEngineCollectorInterface;

class ViewEngineCollector extends AbstractCollector implements ViewEngineCollectorInterface
{

    /**
     * Constructor
     * 
     * @param   Opis\Colibri\Application    $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new EngineResolver());
    }

    /**
     * Defines a new view engine
     *
     * @param   \Closure    $constructor    A callback that will return an instance of \Opis\View\EngineInterface
     * @param   int         $priority       Engine's priority
     *
     * @return  \Opis\View\EngineEntry
     */
    public function register(Closure $constructor, $priority = 0)
    {
        return $this->dataObject->register($constructor, $priority);
    }
}
