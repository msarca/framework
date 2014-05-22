<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

use Closure;
use Opis\Session\Session;
use Opis\Colibri\Serializable\StorageCollection;
use Opis\Colibri\SessionCollectorInterface;

class SessionCollector extends AbstractCollector implements SessionCollectorInterface
{
    
    public function __construct()
    {
        $collection = new StorageCollection(function($storage, Closure $constructor){
            return new Session($constructor(), $storage);
        });
        
        parent::__construct($collection);
    }
    
    public function register($storage, Closure $constructor, $default = false)
    {
        $this->dataObject->add($storage, $constructor, $default);
        return $this;
    }
    
}
