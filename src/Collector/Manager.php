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

namespace Opis\Colibri\Collector;

use Opis\Cache\CacheInterface;
use Opis\Colibri\Application;
use Opis\Colibri\Container;
use Opis\Colibri\Collector;
use Opis\Colibri\ItemCollector;
use Opis\Colibri\Module;
use Opis\Colibri\Serializable\ClassList;
use Opis\Config\ConfigInterface;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Events\Event;
use Opis\Events\RouteCollection as EventsRouteCollection;
use Opis\HttpRouting\RouteCollection as HttpRouteCollection;
use Opis\Routing\RouteCollection as AliasRouteCollection;
use Opis\View\RouteCollection as ViewRouteCollection;
use Opis\View\EngineResolver;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Description of CollectorManager
 *
 * @author mari
 *
 */
class Manager
{
    /** @var array */
    protected $cache = array();

    /** @var   Container */
    protected $container;

    /** @var Router */
    protected $router;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /** @var  Application */
    protected $app;

    /** @var ItemCollector */
    protected $proxy;

    /**
     * Manager constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->container = $container = new Container();
        $this->router = new Router();
        $this->app = $app;
        $this->proxy = new class(null) extends ItemCollector
        {
            public function update(ItemCollector $collector, Module $module, string $name, int $priority)
            {
                $collector->crtModule = $module;
                $collector->crtCollectorName = $name;
                $collector->crtPriority = $priority;
            }

            public function getData(ItemCollector $collector)
            {
                return $collector->data;
            }
        };

        foreach ($app->getCollectorList() as $name => $collector) {
            $container->alias($collector['class'], $name);
            $container->singleton($collector['class']);
        }
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return CacheInterface
     */
    public function getCacheDriver(string $name, bool $fresh = false): CacheInterface
    {
        return $this->collect('CacheDrivers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return Container
     */
    public function getContracts(bool $fresh = false): Container
    {
        return $this->collect('Contracts', $fresh);
    }


    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCommands(bool $fresh = false): array
    {
        return $this->collect('Commands', $fresh)->getList();
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return ConfigInterface
     */
    public function getConfigDriver(string $name, bool $fresh = false): ConfigInterface
    {
        return $this->collect('ConfigDrivers', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Connection
     */
    public function getConnection(string $name, bool $fresh = false): Connection
    {
        return $this->collect('Connections', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Database
     */
    public function getDatabase(string $name, bool $fresh = false): Database
    {
        return $this->collect('Connections', $fresh)->database($name);
    }

    /**
     * @param bool $fresh
     * @return EventsRouteCollection
     */
    public function getEventHandlers(bool $fresh = false): EventsRouteCollection
    {
        return $this->collect('EventHandlers', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return LoggerInterface
     */
    public function getLogger(string $name, bool $fresh = false): LoggerInterface
    {
        return $this->collect('Loggers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return AliasRouteCollection
     */
    public function getRouteAliases(bool $fresh = false): AliasRouteCollection
    {
        return $this->collect('RouteAliases', $fresh);
    }

    /**
     * @param bool $fresh
     * @return HttpRouteCollection
     */
    public function getRoutes(bool $fresh = false): HttpRouteCollection
    {
        return $this->collect('Routes', $fresh);
    }

    /**
     * @param bool $fresh
     * @return ClassList
     */
    public function getResponseInterceptors(bool $fresh = false): ClassList
    {
        return $this->collect('ResponseInterceptors', $fresh);
    }

    /**
     * @param bool $fresh
     * @return ClassList
     */
    public function getMiddleware(bool $fresh = false): ClassList
    {
        return $this->collect('Middleware', $fresh);
    }


    /**
     * @param string $name
     * @param bool $fresh
     * @return \SessionHandlerInterface
     */
    public function getSessionHandler(string $name, bool $fresh = false): \SessionHandlerInterface
    {
        return $this->collect('SessionHandlers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return string[]
     */
    public function getValidators(bool $fresh = false): array
    {
        return $this->collect('Validators', $fresh);
    }

    /**
     * @param bool $fresh
     * @return array
     */
    public function getVariables(bool $fresh = false): array
    {
        return $this->collect('Variables', $fresh)->getList();
    }

    /**
     * @param bool $fresh
     * @return ViewRouteCollection
     */
    public function getViews(bool $fresh = false): ViewRouteCollection
    {
        return $this->collect('Views', $fresh);
    }

    /**
     * @param bool $fresh
     * @return EngineResolver
     */
    public function getViewEngineResolver(bool $fresh = false): EngineResolver
    {
        return $this->collect('ViewEngines', $fresh);
    }

    /**
     * @param bool $fresh
     * @return mixed
     */
    public function getTranslations(bool $fresh = true)
    {
        return $this->collect('Translations', $fresh);
    }

    /**
     * @param string $type
     * @param bool $fresh
     * @return mixed
     */
    public function collect(string $type, bool $fresh = false)
    {
        $entry = strtolower($type);

        if ($fresh) {
            unset($this->cache[$entry]);
        }

        if (!isset($this->cache[$entry])) {

            $collectors = $this->app->getCollectorList($fresh);

            if (!isset($collectors[$entry])) {
                throw new RuntimeException("Unknown collector type '$entry'");
            }

            $hit = false;
            $this->cache[$entry] = $this->app->getCache()->load($entry, function ($entry) use (&$hit) {
                $hit = true;
                $this->includeCollectors();
                $instance = $this->container->make($entry);
                $result = $this->router->route(new Entry($entry, $instance));
                return $this->proxy->getData($result);
            });

            if ($hit) {
                $this->app->getEventTarget()->dispatch(new Event('system.collect.' . $entry));
            }
        }

        return $this->cache[$entry];
    }

    /**
     * Recollect all items
     *
     * @param bool $fresh (optional)
     *
     * @return bool
     */
    public function recollect(bool $fresh = true): bool
    {
        if (!$this->app->getCache()->clear()) {
            return false;
        }

        $this->collectorsIncluded = false;

        foreach (array_keys($this->app->getCollectorList($fresh)) as $entry) {
            $this->collect($entry, $fresh);
        }

        $this->app->getEventTarget()->dispatch(new Event('system.collect'));

        return true;
    }

    /**
     * Register a new collector
     *
     * @param string $name
     * @param string $class
     * @param string $description
     * @param array $options
     */
    public function register(string $name, string $class, string $description, array $options = [])
    {
        $name = strtolower($name);

        $this->app->getConfig()->write('collectors.' . $name, array(
            'class' => $class,
            'description' => $description,
            'options' => $options,
        ));
        $this->container->singleton($class);
        $this->container->alias($class, $name);
    }

    /**
     * Unregister an existing collector
     *
     * @param string $name
     */
    public function unregister(string $name)
    {
        $this->app->getConfig()->delete('collectors.' . strtolower($name));
    }

    /**
     * Include modules
     * @throws \Exception
     */
    protected function includeCollectors()
    {
        if ($this->collectorsIncluded) {
            return;
        }

        $this->collectorsIncluded = true;
        $collectorList = $this->app->getCollectorList();

        foreach ($this->app->getModules() as $module) {

            if (!$module->isEnabled() || $module->collector() === false) {
                continue;
            }

            $instance = $this->container->make($module->collector());

            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf(Collector::class)) {
                continue;
            }

            $map = [];

            foreach ($instance() as $key => $value) {
                if (is_array($value)) {
                    list($name, $priority) = $value;
                } elseif (is_int($value)) {
                    $name = $key;
                    $priority = (int)$value;
                } else {
                    $name = (string)$value;
                    $priority = 0;
                }

                $map[$key] = [$name, $priority];
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

                $methodName = $method->getShortName();

                if (substr($methodName, 0, 2) === '__') {
                    continue;
                }

                if (isset($map[$methodName])) {
                    list($name, $priority) = $map[$methodName];
                } else {
                    $name = $methodName;
                    $priority = 0;
                }

                if (isset($collectorList[$name])) {
                    $options = $collectorList[$name]['options'] ?? [];
                    $options += [
                        'invertedPriority' => true,
                    ];
                    if ($options['invertedPriority']) {
                        $priority *= -1;
                    }
                }

                $callback = function (ItemCollector $collector) use ($instance, $methodName, $module, $name, $priority) {
                    $this->proxy->update($collector, $module, $name, $priority);
                    $instance->{$methodName}($collector);
                };

                $this->router->handle($name, $callback, $priority);
            }
        }
    }

}
