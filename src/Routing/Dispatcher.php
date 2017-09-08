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

namespace Opis\Colibri\Routing;

use function Opis\Colibri\Functions\view;
use Opis\Http\Response;
use Opis\HttpRouting\Context;
use Opis\HttpRouting\Dispatcher as BaseDispatcher;
use Opis\Routing\Context as BaseContext;
use Opis\Routing\Router as BaseRouter;

class Dispatcher extends BaseDispatcher
{
    public function dispatch(BaseRouter $router, BaseContext $context)
    {
        $content = parent::dispatch($router, $context);

        if($this->route === null){
            return $content;
        }

        if(null !== $handler = (string) $this->route->get('responseHandler')){
            $cb = $this->route->getCallbacks();
            if(isset($cb[$handler])){
                $content = $cb[$handler]($content, $this->route);
            }
        }

        return $content;
    }

    /**
     * Get a 403 response
     * @param Context $context
     * @return mixed
     */
    protected function getNotFoundResponse(Context $context)
    {
        return (new Response(view('error.404', ['path' => $context->path()])))->setStatusCode(404);
    }

    /**
     * Get a 403 response
     * @param Context $context
     * @return mixed
     */
    protected function getAccessDeniedResponse(Context $context)
    {
        return (new Response(view('error.403', ['path' => $context->path()])))->setStatusCode(403);
    }
}