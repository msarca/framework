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

class EventHandler
{
    /**
     * @param Event $event
     * @throws \Exception
     */
    public static function onDumpAutoload(Event $event)
    {
        $composer = $event->getComposer();
        $autoloadFile = $composer->getConfig()->get('vendor-dir') . '/autoload.php';
        $installManager = $composer->getInstallationManager();
        $rootDir = $installManager->getInstallPath($composer->getPackage());

        if(!file_exists($autoloadFile)){
            $loader = $composer->getAutoloadGenerator()->createLoader(array());
        } else {
            $loader = require $autoloadFile;
        }

        $appInfo = new AppInfo(array(
            AppInfo::ROOT_DIR => $rootDir
        ));

        $app = new Application($appInfo, $loader, $composer);
        $installMode = $app->getAppInfo()->installMode();

        foreach ($app->getModules() as $module) {

            if ($installMode || !$module->isInstalled()) {
                $module->getPackage()->setAutoload(array());
                continue;
            } elseif ($module->isEnabled()) {
                continue;
            }

            $autoload = $module->getPackage()->getAutoload();

            if (!isset($autoload['classmap'])) {
                $module->getPackage()->setAutoload(array());
                continue;
            }

            $result = array();

            foreach (array('collect.php', 'install.php') as $item) {
                if (in_array($item, $autoload['classmap'])) {
                    $result[] = $item;
                }
            }

            $result = empty($result) ? array() : array('classmap' => $result);
            $module->getPackage()->setAutoload($result);
        }
    }
}
