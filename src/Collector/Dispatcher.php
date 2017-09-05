<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri\Collector;

use Opis\Events\RouteCollection;
use Opis\Routing\Context;
use Opis\Routing\IDispatcher;
use Opis\Routing\Route;
use Opis\Routing\Router;

class Dispatcher implements IDispatcher
{
    /**
     * @param Router $router
     * @param Context|Entry $context
     * @return mixed
     */
    public function dispatch(Router $router, Context $context)
    {
        /** @var RouteCollection $collection */
        $collection = $router->getRouteCollection();
        $collection->sort();
        $collector = $context->getCollector();
        /** @var Route $route */
        foreach ($this->match($collection, (string) $context) as $route){
            $callback = $route->getAction();
            $callback($collector);
        }

        return $collector;
    }

    /**
     * @param RouteCollection $routes
     * @param string $path
     * @return \Generator
     */
    protected function match(RouteCollection $routes, string $path): \Generator
    {
        foreach ($routes->getRegexPatterns() as $routeID => $pattern){
            if(preg_match($pattern, $path)){
                yield $routes->getRoute($routeID);
            }
        }
    }
}