<?php

namespace Opis\Colibri;

use Dotenv\Dotenv;

/**
 * Description of Env
 *
 * @author mari
 */
class Env
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        if (file_exists($app->info()->rootDir() . '/.env')) {
            (new Dotenv($app->info()->rootDir()))->load();
        } elseif(file_exists($app->info()->vendorDir() . '/.env')){
            (new Dotenv($app->info()->vendorDir()))->load();
        }
    }

    /**
     * APP_INSTALLED
     *
     * @return boolean
     */
    public function appInstalled()
    {
        if (false === $value = getenv('APP_INSTALLED')) {
            return false;
        }

        return $value === 'true';
    }

    /**
     * APP_DEBUG
     *
     * @return boolean
     */
    public function appDebug()
    {
        if (false === $value = getenv('APP_DEBUG')) {
            return false;
        }

        return $value === 'true';
    }

    /**
     * APP_ENV
     *
     * @return string
     */
    public function appEnv()
    {
        if (false === $value = getenv('APP_ENV')) {
            return 'local';
        }

        return $value;
    }


    /**
     * DB_STORAGE
     *
     * @return string
     */
    public function databaseStorage()
    {
        if (false === $value = getenv('DB_STORAGE')) {
            return false;
        }

        return $value;
    }

    /**
     * CACHE_STORAGE
     * @return bool|string
     */
    public function cacheStorage()
    {
        if (false === $value = getenv('CACHE_STORAGE')) {
            return false;
        }

        return $value;
    }

    /**
     * CONFIG_STORAGE
     * @return bool|string
     */
    public function configStorage()
    {
        if (false === $value = getenv('CONFIG_STORAGE')) {
            return false;
        }

        return $value;
    }

}
