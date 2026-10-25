<?php

namespace App\Providers;

use App\Domain\Settings\Settings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // نسخة واحدة لكل request: القيم المحفوظة تُقرأ باستعلام واحد مهما تكرر استخدامها
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // $settings متاح في كل الواجهات (اسم الشركة، نصوص الطباعة...) — لا يستعلم إلا عند أول استخدام
        View::share('settings', $this->app->make(Settings::class));
    }
}
