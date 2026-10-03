<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // // Force all JSON responses in Laravel to preserve Unicode (Khmer characters)
        // JsonResponse::mixin(new class {
        //     public function __construct()
        //     {
        //         if (request()->expectsJson() || request()->is('api/*')) {
        //             // This forces the framework's JSON responses to use UNESCAPED_UNICODE globally
        //             config(['app.json_options' => JSON_UNESCAPED_UNICODE]);
        //         }
        //     }
        // });

        // // The 100% foolproof backup macro for standard response()->json()
        // response()->macro('jsonUnicode', function ($data = [], $status = 200, $headers = [], $options = 0) {
        //     return response()->json($data, $status, $headers, $options | JSON_UNESCAPED_UNICODE);
        // });
        
        // // Ensure the underlying JsonResource uses unescaped unicode
        // \Illuminate\Http\Resources\Json\JsonResource::withoutWrapping();
    }
}
