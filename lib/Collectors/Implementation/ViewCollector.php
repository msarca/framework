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

use Opis\View\Route;
use Opis\Colibri\Application;
use Opis\View\RouteCollection;
use Opis\Colibri\Collectors\AbstractCollector;
use Opis\Colibri\Collectors\ViewCollectorInterface;

class ViewCollector extends AbstractCollector implements ViewCollectorInterface
{

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
     * Defines a new view route
     *
     * @param   string $pattern View's pattern
     * @param   callable $resolver A callback that will resolve a view route into a path
     * @param   int $priority Route's priority
     *
     * @return  \Opis\View\Route
     */
    public function handle($pattern, $resolver, $priority = 0)
    {
        $route = new Route($pattern, $resolver, $priority);
        $this->dataObject[] = $route;
        return $route;
    }
}
