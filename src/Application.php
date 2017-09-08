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

namespace Opis\Colibri;

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackage;
use Composer\Repository\InstalledFilesystemRepository;
use Opis\Cache\CacheInterface;
use Opis\Cache\Drivers\Memory as MemoryDriver;
use Opis\Colibri\Collector\Manager as CollectorManager;
use Opis\Colibri\Composer\CLI;
use Opis\Colibri\Composer\Plugin;
use Opis\Colibri\Routing\HttpRouter;
use Opis\Colibri\Util\CSRFToken;
use Opis\Colibri\Validation\Validator;
use Opis\Colibri\Validation\ValidatorCollection;
use Opis\Config\ConfigInterface;
use Opis\Config\Drivers\Ephemeral as EphemeralConfig;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\EntityManager;
use Opis\Database\Schema;
use Opis\Events\Event;
use Opis\Events\EventTarget;
use Opis\Http\Request as HttpRequest;
use Opis\Http\Response as HttpResponse;
use Opis\Http\ResponseHandler;
use Opis\HttpRouting\Context;
use Opis\Session\Session;
use Opis\Validation\Placeholder;
use Opis\View\ViewApp;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;

class Application implements ISettingsContainer
{
    const COMPOSER_TYPE = 'opis-colibri-module';

    /** @var    AppInfo */
    protected $info;

    /** @var    Composer */
    protected $composer;

    /** @var    CLI */
    protected $composerCLI;

    /** @var ClassLoader */
    protected $classLoader;

    /** @var    array|null */
    protected $packages;

    /** @var    array|null */
    protected $modules;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /** @var  CollectorManager */
    protected $collector;

    /** @var  Container */
    protected $containerInstance;

    /** @var  Translator */
    protected $translatorInstance;

    /** @var  CSRFToken */
    protected $csrfTokenInstance;

    /** @var  Placeholder */
    protected $placeholderInstance;

    /** @var  HttpRequest */
    protected $httpRequestInstance;

    /** @var  \Opis\Http\Response */
    protected $httpResponseInstance;

    /** @var  CacheInterface[] */
    protected $cache = array();

    /** @var  ConfigInterface[] */
    protected $config = array();

    /** @var  Connection[] */
    protected $connection = array();

    /** @var  Database[] */
    protected $database = array();

    /** @var  EntityManager[] */
    protected $entityManager = array();

    /** @var  Session */
    protected $session = array();

    /** @var  ConfigInterface[] */
    protected $translations;

    /** @var  HttpRouter */
    protected $httpRouter;

    /** @var  ViewApp */
    protected $viewApp;

    /** @var \Psr\Log\LoggerInterface[] */
    protected $loggers = array();

    /** @var  EventTarget */
    protected $eventTarget;

    /** @var  array */
    protected $variables;

    /** @var  Validator */
    protected $validator;

    /** @var array  */
    protected $implicit = [];

    /** @var  array */
    protected $specials;

    /** @var  array|null */
    protected $collectorList;

    /** @var  Filesystem */
    protected $fileSystem;

    /** @var  Application */
    protected static $instance;

    /**
     * Application constructor
     * @param string $rootDir
     * @param ClassLoader $loader
     * @param Composer|null $composer
     */
    public function __construct(string $rootDir, ClassLoader $loader, Composer $composer = null)
    {
        $json = json_decode(file_get_contents($rootDir . '/composer.json'), true);

        $this->composer = $composer;
        $this->classLoader = $loader;
        $this->info = new AppInfo($rootDir, $json['extra']['application'] ?? []);
        static::$instance = $this;
    }

    /**
     * @return Application
     */
    public static function getInstance(): Application
    {
        return static::$instance;
    }

    /**
     * Get a Composer instance
     *
     * @return  Composer
     */
    public function getComposer(): Composer
    {
        return $this->getComposerCLI()->getComposer();
    }

    /**
     * Get Composer CLI
     *
     * @return  CLI
     */
    public function getComposerCLI(): CLI
    {
        if ($this->composerCLI === null) {
            $this->composerCLI = new CLI();
        }

        return $this->composerCLI;
    }

    /**
     * @return  ClassLoader
     */
    public function getClassLoader(): ClassLoader
    {
        return $this->classLoader;
    }

    /**
     * @return Filesystem
     */
    public function getFileSystem(): Filesystem
    {
        if($this->fileSystem === null){
            $this->fileSystem = new Filesystem();
        }

        return $this->fileSystem;
    }

    /**
     * Get module packs
     *
     * @param   bool $clear (optional)
     *
     * @return  CompletePackage[]
     */
    public function getPackages(bool $clear = false): array
    {
        if (!$clear && $this->packages !== null) {
            return $this->packages;
        }

        $packages = array();
        $repository = new InstalledFilesystemRepository(new JsonFile($this->info->vendorDir() . '/composer/installed.json'));
        foreach ($repository->getCanonicalPackages() as $package) {
            if (!$package instanceof CompletePackage || $package->getType() !== static::COMPOSER_TYPE) {
                continue;
            }
            $packages[$package->getName()] = $package;
        }

        return $this->packages = $packages;
    }

    /**
     * Get a list with available modules
     *
     * @param   bool $clear (optional)
     *
     * @return  Module[]
     */
    public function getModules(bool $clear = false): array
    {
        if (!$clear && $this->modules !== null) {
            return $this->modules;
        }

        $modules = array();

        foreach ($this->getPackages($clear) as $module => $package) {
            $modules[$module] = new Module($module, $package);
        }

        return $this->modules = $modules;
    }

    /**
     * Get the HTTP router
     *
     * @return  HttpRouter
     */
    public function getHttpRouter(): HttpRouter
    {
        if ($this->httpRouter === null) {
            $this->httpRouter = new HttpRouter();
        }
        return $this->httpRouter;
    }

    /**
     * Get the View router
     *
     * @return  ViewApp
     */
    public function getViewApp(): ViewApp
    {
        if ($this->viewApp === null) {
            $collector = $this->getCollector();
            $routes = $collector->getViews();
            $resolver = $collector->getViewEngineResolver();
            $this->viewApp = new ViewApp($routes, $resolver, new ViewEngine());
        }
        return $this->viewApp;
    }

    /**
     * Return the dependency injection container
     *
     * @return  Container
     */
    public function getContainer(): Container
    {
        if ($this->containerInstance === null) {
            $container = $this->getCollector()->getContracts();
            $this->containerInstance = $container;
        }
        return $this->containerInstance;
    }

    /**
     * @return  Translator
     */
    public function getTranslator(): Translator
    {
        if ($this->translatorInstance === null) {
            $this->translatorInstance = new Translator();
        }
        return $this->translatorInstance;
    }

    /**
     *
     * @return  CSRFToken
     */
    public function getCSRFToken(): CSRFToken
    {
        if ($this->csrfTokenInstance === null) {
            $this->csrfTokenInstance = new CSRFToken();
        }

        return $this->csrfTokenInstance;
    }

    /**
     * Get a placeholder object
     *
     * @return  Placeholder
     */
    public function getPlaceholder(): Placeholder
    {
        if ($this->placeholderInstance === null) {
            $this->placeholderInstance = new Placeholder();
        }

        return $this->placeholderInstance;
    }

    /**
     * Returns validator instance
     *
     * @return  Validator
     */
    public function getValidator(): Validator
    {
        if ($this->validator === null){
            $this->validator = new Validator(new ValidatorCollection(), $this->getPlaceholder());
        }

        return $this->validator;
    }

    /**
     * Returns a caching storage
     *
     * @param   string $storage (optional) Storage name
     *
     * @return  CacheInterface
     */
    public function getCache(string $storage = 'default'): CacheInterface
    {
        if (!isset($this->cache[$storage])) {
            if($storage === 'default'){
                if(!isset($this->implicit['cache'])){
                    throw new \RuntimeException('The default cache storage was not set');
                }
                $this->cache[$storage] = $this->implicit['cache'];
            } else {
                $this->cache[$storage] = $this->getCollector()->getCacheDriver($storage);
            }
        }

        return $this->cache[$storage];
    }

    /**
     * Returns a session storage
     *
     * @param   string $storage (optional) Storage name
     *
     * @return  Session
     */
    public function getSession(string $storage = 'default'): Session
    {
        if (!isset($this->session[$storage])) {
            if($storage === 'default'){
                if(!isset($this->implicit['session'])){
                    throw new \RuntimeException('The default session storage was not set');
                }
                $this->session[$storage] = new Session($this->implicit['session']);
            } else {
                $this->session[$storage] = new Session($this->getCollector()->getSessionHandler($storage));
            }
        }

        return $this->session[$storage];
    }

    /**
     * Returns a config storage
     *
     * @param   string $driver (optional) Driver's name
     *
     * @return  ConfigInterface
     */
    public function getConfig(string $driver = 'default'): ConfigInterface
    {
        if (!isset($this->config[$driver])) {
            if($driver === 'default') {
                if(!isset($this->implicit['config'])){
                    throw new \RuntimeException('The default config storage was not set');
                }
                $this->config[$driver] = $this->implicit['config'];
            } else {
                $this->config[$driver] = $this->getCollector()->getConfigDriver($driver);
            }
        }

        return $this->config[$driver];
    }

    /**
     * Returns a translation storage
     *
     * @return  ConfigInterface
     */
    public function getTranslations(): ConfigInterface
    {
        if ($this->translations === null) {
            $this->translations = $this->getConfig();
        }

        return $this->translations;
    }

    /**
     *
     * @return  Console
     */
    public function getConsole(): Console
    {
        return new Console();
    }

    /**
     * @param string $name
     * @throws  \RuntimeException
     * @return  Connection
     */
    public function getConnection(string $name = 'default'): Connection
    {
        if(!isset($this->connection[$name])){
            if($name === 'default' && isset($this->implicit['connection'])){
                $this->connection[$name] = $this->implicit['connection'];
            } else {
                $this->connection[$name] = $this->getCollector()->getConnection($name);
            }
        }

        return $this->connection[$name];
    }

    /**
     * Returns a database abstraction layer
     *
     * @param   string $connection (optional) Connection name
     *
     * @return  Database
     */
    public function getDatabase(string $connection = 'default'): Database
    {
        if(!isset($this->database[$connection])){
            $this->database[$connection] = new Database($this->getConnection($connection));
        }

        return $this->database[$connection];
    }

    /**
     * Returns a database schema abstraction layer
     *
     * @param   string $connection (optional) Connection name
     *
     * @return  Schema
     */
    public function getSchema(string $connection = 'default'): Schema
    {
        return $this->getDatabase($connection)->schema();
    }

    /**
     * Returns an entity manager
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  EntityManager
     */
    public function getEntityManager(string $connection = 'default'): EntityManager
    {
        if(!isset($this->entityManager[$connection])){
            $this->entityManager[$connection] = new EntityManager($this->getConnection($connection));
        }
        return $this->entityManager[$connection];
    }

    /**
     * Returns a logger
     *
     * @param   string $logger Logger's name
     *
     * @return  LoggerInterface
     */
    public function getLog(string $logger = 'default'): LoggerInterface
    {
        if (!isset($this->loggers[$logger])) {
            if($logger === 'default'){
                if(!isset($this->implicit['logger'])){
                    throw new \RuntimeException('The default logger was not set');
                }
                $this->loggers[$logger] = $this->implicit['logger'];
            } else{
                $this->loggers[$logger] = $this->getCollector()->getLogger($logger);
            }
        }

        return $this->loggers[$logger];
    }

    /**
     * Return the underlying HTTP request object
     *
     * @return  HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        if ($this->httpRequestInstance === null) {
            $this->httpRequestInstance = HttpRequest::fromGlobals();
        }

        return $this->httpRequestInstance;
    }

    /**
     * Get variables list
     *
     * @return array
     */
    public function getVariables(): array
    {
        if($this->variables === null){
            $this->variables = $this->getCollector()->getVariables();
        }
        return $this->variables;
    }

    /**
     * @return EventTarget
     */
    public function getEventTarget(): EventTarget
    {
        if($this->eventTarget === null){
            $this->eventTarget = new EventTarget($this->getCollector()->getEventHandlers());
        }
        return $this->eventTarget;
    }

    /**
     * Get information about this application
     *
     * @return  AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->info;
    }

    /**
     * Get collector
     *
     * @return CollectorManager
     */
    public function getCollector(): CollectorManager
    {
        if ($this->collector === null) {
            $this->collector = new CollectorManager();
        }
        return $this->collector;
    }

    /**
     * @return array
     */
    public function getSpecials(): array
    {
        if($this->specials === null){
            $this->specials = [
                'app' => $this,
                'lang' => $this->getTranslator()->getLanguage(),
            ];
        }

        return $this->specials;
    }

    /**
     * @param bool $fresh
     * @return array
     */
    public function getCollectorList(bool $fresh = false): array
    {
        if($fresh){
            $this->collectorList = null;
        }

        if($this->collectorList === null){
            $default = require __DIR__ . '/../res/collectors.php';
            $this->collectorList = $this->getConfig()->read('collectors', array()) + $default;
        }

        return $this->collectorList;
    }

    /**
     * @param ConfigInterface $driver
     * @return ISettingsContainer
     */
    public function setConfigDriver(ConfigInterface $driver): ISettingsContainer
    {
        $this->implicit['config'] = $driver;
        return $this;
    }

    /**
     * @param CacheInterface $driver
     * @return ISettingsContainer
     */
    public function setCacheDriver(CacheInterface $driver): ISettingsContainer
    {
        $this->implicit['cache'] = $driver;
        return $this;
    }

    /**
     * @param ConfigInterface $driver
     * @return ISettingsContainer
     */
    public function setTranslationsDriver(ConfigInterface $driver): ISettingsContainer
    {
        $this->translations = $driver;
        return $this;
    }

    /**
     * @param Connection $connection
     * @return ISettingsContainer
     */
    public function setDatabaseConnection(Connection $connection): ISettingsContainer
    {
        $this->implicit['connection'] = $connection;
        return $this;
    }

    /**
     * @param SessionHandlerInterface $session
     * @return ISettingsContainer
     */
    public function setSessionHandler(SessionHandlerInterface $session): ISettingsContainer
    {
        $this->implicit['session'] = $session;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return ISettingsContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): ISettingsContainer
    {
        $this->implicit['logger'] = $logger;
        return $this;
    }

    /**
     * Bootstrap method
     * @return Application
     */
    public function bootstrap(): self
    {
        if (!$this->info->installMode()) {
            $this->getBootstrapInstance()->bootstrap($this);
            $this->emit('system.init');
            return $this;
        }

        $composer = $this->getComposerCLI()->getComposer();
        $generator = $composer->getAutoloadGenerator();
        $extra = $composer->getPackage()->getExtra();
        $enabled = array();
        $canonicalPacks = array();
        /** @var CompletePackage[] $modules */
        $modules = array();
        $installer = null;

        if(!isset($extra['application']['installer'])){
            throw new \RuntimeException('No installer defined');
        }

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {

            if ($package->getType() !== static::COMPOSER_TYPE) {
                $canonicalPacks[] = $package;
                continue;
            }

            $modules[$package->getName()] = $package;
        }

        if(!isset($modules[$extra['application']['installer']])){
            throw new \RuntimeException("The specified installer was not found");
        }

        $installer = $modules[$extra['application']['installer']];
        $canonicalPacks[] = $installer;
        $enabled[] = $installer->getName();

        foreach ($installer->getRequires() as $require){
            $target = $require->getTarget();
            if (isset($modules[$target])) {
                $canonicalPacks[] = $modules[$target];
                $enabled[] = $modules[$target]->getName();
            }
        }

        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $canonicalPacks);
        $autoload = $generator->parseAutoloads($packMap, $composer->getPackage());
        $loader = $generator->createLoader($autoload);

        $this->classLoader->unregister();
        $this->classLoader = $loader;
        $this->classLoader->register();

        $this->getBootstrapInstance()->bootstrap($this);
        $this->getConfig()->write('modules.installed', $enabled);
        $this->getConfig()->write('modules.enabled', $enabled);

        $this->emit('system.init');
        return $this;
    }

    /**
     * Execute
     *
     * @param   HttpRequest|null $request
     *
     * @return  mixed
     */
    public function run(HttpRequest $request = null)
    {
        if ($request === null) {
            $request = HttpRequest::fromGlobals();
        }

        $this->httpRequestInstance = $request;

        $context = new Context(
            $request->path(), $request->host(), $request->method(), $request->isSecure(), $request
        );

        $result = $this->getHttpRouter()->route($context);

        if($result instanceof HttpResponse){
            $response = $result;
        } else {
            $response = new HttpResponse();
            $response->setBody($result);
        }

        if(getenv('UNIT_TESTING') === false){
            $handler = new ResponseHandler($request);
            $handler->sendResponse($response);
        }

        return $response;
    }

    /**
     * Install a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function install(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeInstalled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.installed', array());
        $modules[] = $module->name();
        $config->write('modules.installed', $modules);

        $this->getComposerCLI()->dumpAutoload();
        $this->reloadClassLoader();
        if(false !== $installer = $module->installer()){
            $this->getContainer()->make($installer)->install();
        }

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.installed.' . $module->name());

        return true;
    }

    /**
     * Uninstall a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function uninstall(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeUninstalled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.installed', array());
        $config->write('modules.installed', array_diff($modules, array($module->name())));

        if(false !== $installer = $module->installer()){
            $this->getContainer()->make($installer)->uninstall();
        }
        $this->getComposerCLI()->dumpAutoload();
        $this->reloadClassLoader();

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.uninstalled.' . $module->name());

        return true;
    }

    /**
     * Enable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function enable(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeEnabled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.enabled', array());
        $modules[] = $module->name();
        $config->write('modules.enabled', $modules);

        $this->getComposerCLI()->dumpAutoload();
        $this->reloadClassLoader();

        if(false !== $installer = $module->installer()){
            $this->getContainer()->make($installer)->enable();
        }

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.enabled.' . $module->name());

        return true;
    }

    /**
     * Disable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function disable(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeDisabled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.enabled', array());
        $config->write('modules.enabled', array_diff($modules, array($module->name())));

        if(false !== $installer = $module->installer()){
            $this->getContainer()->make($installer)->disable();
        }

        $this->getComposerCLI()->dumpAutoload();
        $this->reloadClassLoader();

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.disabled.' . $module->name());

        return true;
    }

    /**
     * @return IBootstrap
     */
    protected function getBootstrapInstance(): IBootstrap
    {
        if(!$this->info->installMode()){
            return require $this->info->bootstrapFile();
        }

        return new class implements IBootstrap
        {
            public function bootstrap(ISettingsContainer $app)
            {
                $app->setCacheDriver(new MemoryDriver())
                    ->setConfigDriver(new EphemeralConfig())
                    ->setDefaultLogger(new NullLogger())
                    ->setSessionHandler(new \SessionHandler());
            }
        };
    }

    /**
     * Reload class loader
     */
    protected function reloadClassLoader()
    {
        $this->classLoader->unregister();
        $this->classLoader = $this->generateClassLoader($this->getComposer());
        $this->classLoader->register();
    }

    /**
     * @param Composer $composer
     * @return ClassLoader
     */
    protected function generateClassLoader(Composer $composer): ClassLoader
    {
        $installMode = $this->info->installMode();
        $config = $this->getConfig();
        $installed = $config->read('modules.installed', []);
        $enabled = $config->read('modules.enabled', []);

        $plugin = new Plugin();
        $plugin->activate($composer, new NullIO());
        $packages = $plugin->preparePacks($installMode, $enabled, $installed);

        $generator = $composer->getAutoloadGenerator();
        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $packages);
        $autoload = $generator->parseAutoloads($packMap, $composer->getPackage());
        return $generator->createLoader($autoload);
    }

    /**
     * @param string $name
     * @param bool $cancelable
     * @return Event
     */
    protected function emit(string $name, bool $cancelable = false): Event
    {
        return $this->getEventTarget()->dispatch(new Event($name, $cancelable));
    }

}
