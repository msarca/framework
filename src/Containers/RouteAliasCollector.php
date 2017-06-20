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

namespace Opis\Colibri\Containers;

use Opis\Colibri\CollectingContainer;
use Opis\Routing\RouteCollection;
use Opis\Routing\Route;

/**
 * Class RouteAliasCollector
 * @package Opis\Colibri\Containers
 * @method RouteCollection data()
 * @property \Opis\Routing\RouteCollection $dataObject
 */
class RouteAliasCollector extends CollectingContainer
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new RouteCollection());
    }

    /**
     * Defines an alias for a route or a group of routes
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     *
     * @return  Route
     */
    public function alias(string $path, callable $action): Route
    {
        $route = new Route($path, $action);
        $this->dataObject->addRoute($route);
        return $route;
    }
}
