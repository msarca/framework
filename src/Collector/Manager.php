<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use ReflectionClass, ReflectionMethod, RuntimeException;
use Opis\Colibri\{Application, Container, Collector, ItemCollector, Routing\HttpRouteCollection, Session};
use Opis\Colibri\Core\Module;
use Opis\DataStore\IDataStore;
use Opis\Colibri\Serializable\{
    AdvancedClassList,
    CallbackList,
    ClassList,
    RouterGlobals,
    Translations,
    ViewEngineResolver
};
use Opis\Database\{
    Connection, Database
};
use Opis\Events\RouteCollection as EventsRouteCollection;
use Opis\Routing\{
    Context
};
use Opis\View\{
    RouteCollection as ViewRouteCollection
};
use Opis\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use function Opis\Colibri\Functions\convertToCase;

class Manager
{
    /** @var array */
    protected $cache = [];

    /** @var Router */
    protected $router;

    /** @var Container */
    protected $container;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /** @var  Application */
    protected $app;

    /** @var ItemCollector */
    protected $proxy;

    /** @var string[] */
    protected $collectStack = [];

    /**
     * Manager constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->router = new Router();
        $this->container = $container = new Container();
        foreach ($this->app->getCollectorList() as $name => $collector) {
            $container->alias($name, $collector['class']);
            $container->singleton($collector['class']);
        }

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
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return CacheInterface
     */
    public function getCacheDriver(string $name, bool $fresh = false): CacheInterface
    {
        return $this->collect('cache-drivers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return Container
     */
    public function getContracts(bool $fresh = false): Container
    {
        return $this->collect('contracts', $fresh);
    }

    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCommands(bool $fresh = false): array
    {
        return $this->collect('commands', $fresh)->getList();
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return IDataStore
     */
    public function getConfigDriver(string $name, bool $fresh = false): IDataStore
    {
        return $this->collect('config-drivers', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Connection
     */
    public function getConnection(string $name, bool $fresh = false): Connection
    {
        return $this->collect('connections', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Database
     */
    public function getDatabase(string $name, bool $fresh = false): Database
    {
        return $this->collect('connections', $fresh)->database($name);
    }

    /**
     * @param bool $fresh
     * @return EventsRouteCollection
     */
    public function getEventHandlers(bool $fresh = false): EventsRouteCollection
    {
        return $this->collect('event-handlers', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return LoggerInterface
     */
    public function getLogger(string $name, bool $fresh = false): LoggerInterface
    {
        return $this->collect('loggers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return HttpRouteCollection
     */
    public function getRoutes(bool $fresh = false): HttpRouteCollection
    {
        return $this->collect('routes', $fresh);
    }

    /**
     * @param bool $fresh
     * @return ClassList
     */
    public function getMiddleware(bool $fresh = false): ClassList
    {
        return $this->collect('middleware', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Session
     */
    public function getSessionHandler(string $name, bool $fresh = false): Session
    {
        return $this->collect('session-handlers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return ClassList
     */
    public function getValidators(bool $fresh = false): ClassList
    {
        return $this->collect('validators', $fresh);
    }

    /**
     * @param bool $fresh
     * @return ViewRouteCollection
     */
    public function getViews(bool $fresh = false): ViewRouteCollection
    {
        return $this->collect('views', $fresh);
    }

    /**
     * @param bool $fresh
     * @return ViewEngineResolver
     */
    public function getViewEngineResolver(bool $fresh = false): ViewEngineResolver
    {
        return $this->collect('view-engines', $fresh);
    }

    /**
     * @param bool $fresh
     * @return Translations
     */
    public function getTranslations(bool $fresh = true)
    {
        return $this->collect('translations', $fresh);
    }

    /**
     * @param bool $fresh
     * @return RouterGlobals
     */
    public function getRouterGlobals(bool $fresh = true): RouterGlobals
    {
        return $this->collect('router-globals', $fresh);
    }

    /**
     * @param bool $fresh
     * @return CallbackList
     */
    public function getAssetHandlers(bool $fresh = true)
    {
        return $this->collect('asset-handlers', $fresh);
    }

    /**
     * @param bool $fresh
     * @return AdvancedClassList
     */
    public function getTemplateStreamHandlers(bool $fresh = true)
    {
        return $this->collect('template-stream-handlers', $fresh);
    }

    /**
     * @param string $type
     * @param bool $fresh
     * @return mixed
     */
    public function collect(string $type, bool $fresh = false)
    {
        $entry = strtolower($type);

        if (in_array($entry, $this->collectStack)) {
            return $this->proxy->getData($this->container->make($entry));
        }

        $this->collectStack[] = $entry;

        if ($fresh) {
            unset($this->cache[$entry]);
        }

        if (!isset($this->cache[$entry])) {
            $collectors = $this->app->getCollectorList($fresh);

            if (!isset($collectors[$entry])) {
                throw new RuntimeException("Unknown collector type '$entry'");
            }

            $hit = false;
            $this->cache[$entry] = $this->app->getCache()->load($entry, function ($entryName) use (&$hit) {
                $hit = true;
                $this->includeCollectors();
                $instance = $this->container->make($entryName);
                $entry = new Entry($entryName, $instance);
                $result = $this->router->route(new Context($entryName, $entry));
                return $this->proxy->getData($result);
            });

            if ($hit) {
                $this->app->getEventDispatcher()->emit("system.collect." . $entry);
            }
        }

        array_pop($this->collectStack);

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
        $list = $this->app->getCollectorList($fresh);

        if ($fresh) {
            $this->cache = [];
            $this->app->clearCachedObjects();
            $this->router = new Router();
            $this->container = $container = new Container();
            foreach ($list as $name => $collector) {
                $container->alias($name, $collector['class']);
                $container->singleton($collector['class']);
            }
        }

        foreach (array_keys($list) as $entry) {
            $this->collect($entry, $fresh);
        }

        $this->app->getEventDispatcher()->emit("system.collect");

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

        $this->app->getConfig()->write('collectors.' . $name, [
            'class' => $class,
            'description' => $description,
            'options' => $options,
        ]);
        $this->container->singleton($class);
        $this->container->alias($name, $class);
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

        foreach ($this->app->getModules() as $module) {

            if (!$module->isEnabled() || $module->collector() === null) {
                continue;
            }

            $instance = $this->container->make($module->collector());

            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf(Collector::class)) {
                continue;
            }

            $this->doCollect($module, $instance, $reflection, false);
        }
    }

    private function doCollect(Module $module, Collector $instance, ReflectionClass $reflection, bool $delegate)
    {
        $map = [];

        $mappings = $instance();
        $delegateCollectors = $delegate ? [] : ($mappings['@'] ?? []);
        $collectorList = $this->app->getCollectorList();

        unset($mappings['@']);

        foreach ($mappings as $key => $value) {
            if (is_array($value)) {
                list($name, $priority) = $value;
                $name = strtolower($name);
            } elseif (is_int($value)) {
                $name = convertToCase($key, 'kebab-case');
                $priority = (int)$value;
            } else {
                $name = convertToCase($value, 'kebab-case');
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
                $name = convertToCase($methodName, 'kebab-case');
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

            $callback = function (ItemCollector $collector) use (
                $instance,
                $methodName,
                $module,
                $name,
                $priority
            ) {
                $this->proxy->update($collector, $module, $name, $priority);
                $instance->{$methodName}($collector);
            };

            $this->router->handle($name, $callback, $priority);
        }

        if (!$delegate) {
            if (!is_array($delegateCollectors)) {
                $delegateCollectors = [$delegateCollectors];
            }
            foreach ($delegateCollectors as $collector){
                if (!is_string($collector)) {
                    continue;
                }
                try {
                    $reflection = new ReflectionClass($collector);
                } catch (\ReflectionException $exception) {
                    continue;
                }
                if (!$reflection->isSubclassOf(DelegateCollector::class)) {
                    continue;
                }
                $collector = $this->container->make($collector);
                $this->doCollect($module, $collector, $reflection, true);
            }
        }
    }
}
