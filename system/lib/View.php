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

namespace Opis\Colibri;

use Opis\View\View as OpisView;

class View extends OpisView
{
    protected $renderedContent = null;
    
    public function __toString()
    {
        if($this->renderedContent === null)
        {
            $this->renderedContent = App::view()->render($this);
            
            if(!is_string($this->renderedContent))
            {
                $this->renderedContent = (string) $this->renderedContent;
            }
        }
        
        return $this->renderedContent;
    }
    
    public function set($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }
    
    public function has($name)
    {
        return isset($this->arguments[$name]);
    }
    
    public function get($name, $default = null)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }
}
