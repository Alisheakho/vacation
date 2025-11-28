<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up'
    )->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
 $middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    'check.token' =>  \App\Http\Middleware\CheckUserToken::class
]);



    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
/*     return response()->json([
        'status' => 403,
        'message' => 'ليس لديك الصلاحية للوصول.',
        'required_roles' => $e->getRequiredRoles(),
    ], 403); */
});

    })
    ->create();
