<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
|
| Set the environment path and file. Allow for environment file to be defined
| in another directory than the root of the project.
|
*/

$getenv = function ($key, $default = null) {
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    return getenv($key) ?: $default;
};

if ($environmentPath = $getenv('WHOSENAME_ENV_PATH')) {
    $app->useEnvironmentPath($environmentPath);
}

if ($environmentFile = $getenv('WHOSENAME_ENV_FILE')) {
    $app->loadEnvironmentFrom($environmentFile);
}

if ($storagePath = $getenv('WHOSENAME_STORAGE_PATH')) {
    $app->useStoragePath($storagePath);
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
