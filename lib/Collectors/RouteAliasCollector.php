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
use Opis\Routing\Collections\RouteCollection;
use Opis\Routing\Pattern;
use Opis\Routing\Route;

/**
 * Class RouteAliasCollector
 * @package Opis\Colibri\Collectors
 * @method RouteCollection data()
 */
class RouteAliasCollector extends Collector
{
    protected $compiler;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new RouteCollection());
    }

    /**
     * Defines an alias for a route or a group of routes
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     *
     * @return  \Opis\Routing\Route
     */
    public function alias($path, $action)
    {
        $route = new Route(new Pattern($path), $action);
        $this->dataObject[] = $route;
        return $route;
    }
}
