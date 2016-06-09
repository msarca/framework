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

namespace Opis\Colibri\Serializable;

use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\Schema;
use RuntimeException;
use Serializable;

class ConnectionList implements Serializable
{
    protected $connections = array();
    protected $databases = array();
    protected $schemas = array();
    protected $defaultConnection;

    public function set($name, Connection $connection)
    {
        $this->connections[$name] = $connection;
    }

    public function get($name)
    {
        if (!isset($this->connections[$name])){
            throw new RuntimeException("Invalid connection name `$name`");
        }

        return $this->connections[$name];
    }

    public function database($name)
    {

        if (!isset($this->databases[$name])) {
            $this->databases[$name] = new Database($this->get($name));
        }

        return $this->databases[$name];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->connections);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $this->connections = $this->unserialize($data);
    }
}
