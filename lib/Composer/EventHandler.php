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

use Composer\Composer;
use Composer\Package\CompletePackage;
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

        $rootDir = realpath($composer->getConfig()->get('vendor-dir') . '/../');
        $settings = $composer->getPackage()->getExtra()['application'] ?? [];
        $appInfo = new AppInfo($rootDir, $settings);

        $installMode = true;
        $installed = $enabled = [];

        if(!$appInfo->installMode()){
            $installMode = false;
            $collector = new DefaultCollector($appInfo);
            /** @var \Opis\Colibri\BootstrapInterface $bootstrap */
            $bootstrap = require $appInfo->bootstrapFile();
            $bootstrap->bootstrap($collector);
            $installed = $collector->getInstalledModules();
            $enabled = $collector->getEnabledModules();
        }

        static::preparePacks($composer, $installMode, $enabled, $installed);
    }

    public static function preparePacks(Composer $composer, bool $installMode, array $enabled, array $installed): array
    {
        /** @var CompletePackage[] $packages */
        $packages = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();

        foreach ($packages as $package){
            if($package->getType() !== Application::MODULE_TYPE){
                continue;
            }

            $module = $package->getName();

            if($installMode){
                $package->setAutoload([]);
                continue;
            }

            if(!in_array($module, $installed)){
                $package->setAutoload([]);
                continue;
            }

            if(in_array($module, $enabled)){
                continue;
            }

            $classmap = [];
            $extra = $package->getExtra();

            foreach (['collector', 'installer'] as $key) {
                if(!isset($extra[$key]) || !is_array($extra[$key])){
                    continue;
                }
                $item = $extra[$key];
                if(isset($item['file']) && isset($item['class'])){
                    $classmap[] = $item['file'];;
                }
            }

            $package->setAutoload(empty($classmap) ? [] : ['classmap' => $classmap]);
        }

        return $packages;
    }
}
