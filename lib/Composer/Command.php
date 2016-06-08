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

namespace Opis\Colibri\Composer;

use Composer\Script\Event;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;

class Command
{
    /**
     * @param Event $event
     * @throws \Exception
     */
    public static function handleDumpAutoload(Event $event)
    {
        $composer = $event->getComposer();

        $appInfo = new AppInfo(array(
            AppInfo::ROOT_DIR => $composer->getInstallationManager()
                ->getInstallPath($composer->getPackage())
        ));
        
        $loader = require $appInfo->vendorDir() . '/autoload.php';

        $app = new Application($appInfo, $loader, $composer);

        foreach ($app->getModules() as $module) {

            if ($module->isEnabled()) {
                continue;
            }

            if (!$module->isInstalled()) {
                $module->getPackage()->setAutoload(array());
                continue;
            }
        }
    }
}
