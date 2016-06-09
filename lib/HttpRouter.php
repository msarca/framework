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

namespace Opis\Colibri;

use Opis\Http\Error\AccessDenied;
use Opis\Http\Error\NotFound;
use Opis\HttpRouting\Path;
use Opis\HttpRouting\Router;
use Opis\Routing\Path as AliasPath;
use Opis\Routing\Path as BasePath;
use Opis\Routing\Router as AliasRouter;

class HttpRouter extends Router
{
    /** @var    Application */
    protected $app;

    /** @var    \Opis\HttpRouting\Path */
    protected $path;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $specials = array(
            'app' => $app,
            'request' => $app->request(),
            'response' => $app->response(),
            't' => $app->getTranslator(),
            'lang' => $app->getTranslator()->getLanguage(),
            'view' => $app->getViewRouter(),
        );

        parent::__construct($app->collector()->getRoutes(), $app->collector()->getDispatcherResolver(), null, $specials);

        $this->getRouteCollection()
            ->notFound(function ($path) use ($app) {
                return new NotFound($app->view('error.404', array('path' => $path)));
            })
            ->accessDenied(function ($path) use ($app) {
                return new AccessDenied($app->view('error.403', array('path' => $path)));
            });

        $this->getRouteCollection()->setRouter($this);
    }

    /**
     * Get the application
     *
     * @return  Application
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * Get current path
     *
     * @return  BasePath
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Route path
     *
     * @param Path|BasePath $path
     * @return mixed
     */
    public function route(BasePath $path)
    {

        $router = new AliasRouter($this->app->collector()->getRouteAliases());
        $alias = $router->route(new AliasPath($path->path()));

        if ($alias !== null) {
            $path = new Path(
                (string)$alias, $path->domain(), $path->method(), $path->isSecure(), $path->request()
            );
        }

        $this->path = $path;
        $result = parent::route($path);

        $response = $path->request()->response();
        $response->body($result);
        $response->send();

        return $result;
    }
}
