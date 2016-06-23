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

use Opis\Colibri\Components\ApplicationTrait;
use Opis\Database\Connection;
use Opis\Database\Model as BaseModel;

class Model extends BaseModel
{
    use ApplicationTrait;

    /** @var   Application */
    protected static $app;

    /** @var    string  Connection name */
    protected static $connection = null;

    /**
     *  Set application
     *
     * @param   \Opis\Colibri\Application $app
     */
    public static function setApplication(Application $app)
    {
        static::$app = $app;
    }

    /**
     * Returns the active database connection
     *
     * @return  Connection
     */
    public static function getConnection(): Connection
    {
        return static::getApplication()->getConnection(static::$connection);
    }

    /**
     *  Get application
     *
     * @return  Application
     */
    public static function getApplication(): Application
    {
        return static::$app;
    }

    /**
     * @return Application
     */
    protected function getApp(): Application
    {
        return static::$app;
    }
}
