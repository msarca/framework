<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

namespace Opis\Colibri\Serializable;

use Closure;
use Serializable;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\Schema;

class ConnectionList implements Serializable
{
    
    protected $connections = array();
    
    protected $databases = array();
    
    protected $schemas = array();
    
    protected $defaultConnection;
    
    public function set($name, Connection $connection, $default = false)
    {
        if($this->defaultConnection === null)
        {
            $default = true;
        }
        
        if($default === true)
        {
            $this->defaultConnection = $name;
        }
        
        $this->connections[$name] = $connection;
    }
    
    public function get($connection = null)
    {
        if($connection === null)
        {
            $connection = $this->defaultConnection;
        }
        
        return $this->connections[$connection];
    }
    
    public function database($connection = null)
    {
        if($connection === null)
        {
            $connection = $this->defaultConnection;
        }
        
        if(!isset($this->databases[$connection]))
        {
            $this->databases[$connection] = new Database($this->get($connection));
        }
        
        return $this->databases[$connection];
    }
    
    public function schema($connection = null)
    {
        if($connection === null)
        {
            $connection = $this->defaultConnection;
        }
        
        if(!isset($this->schemas[$connection]))
        {
            $this->schemas[$connection] = new Schema($this->get($connection));
        }
        
        return $this->schemas[$connection];
    }
    
    public function serialize()
    {
        return serialize(array(
            'connections' => $this->connections,
            'defaultConnection' => $this->defaultConnection,
        ));
    }
    
    public function unserialize($data)
    {
        $object = unserialize($data);
        $this->connections = $object['connections'];
        $this->defaultConnection = $object['defaultConnection'];
    }
    
}
