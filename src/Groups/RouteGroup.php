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

namespace Opis\Colibri\Groups;


use Opis\Colibri\Routing\HttpRoute;

/**
 * Class RouteGroup
 * @package Opis\Colibri\Groups
 *
 * @method RouteGroup bind(string $name, callable $callback)
 * @method RouteGroup filter(string $name, callable $callback)
 * @method RouteGroup implicit(string $name, $value)
 * @method RouteGroup where(string $name, $value)
 * @method RouteGroup before(string|array $filters)
 * @method RouteGroup after(string|array $filters)
 * @method RouteGroup access(string|array $filters)
 * @method RouteGroup domain(string $value)
 * @method RouteGroup method(string $value)
 * @method RouteGroup secure(bool $value)
 * @method RouteGroup dispatcher(string $name)
 * @method RouteGroup notFound(callable $callback)
 * @method RouteGroup accessDenied(callable $callback)
 */
class RouteGroup
{
    /** @var  HttpRoute[] */
    protected $routes;

    /**
     * RouteGroup constructor.
     * @param HttpRoute[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|RouteGroup
     */
    public function __call($name, $arguments)
    {
        foreach ($this->routes as $route){
            $route->{$name}(...$arguments);
        }
        return $this;
    }
}