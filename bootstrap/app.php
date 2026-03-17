<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
         $middleware->alias([
        'is_department_student_discipline_officer' => \App\Http\Middleware\IsAdmin::class,
        'is_top_management' => \App\Http\Middleware\IsTopManagement::class,
        'pending_handling_response' => \App\Http\Middleware\EnsureHandlingResponseCompleted::class,
    ]);
    
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();


