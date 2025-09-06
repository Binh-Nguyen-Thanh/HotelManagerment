<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Models\Information;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;

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
        $info = Information::first();

        if ($info) {
            Config::set('mail.mailers.smtp.username', $info->email); // MAIL_USERNAME
            Config::set('mail.mailers.smtp.password', $info->email_password); // MAIL_PASSWORD
            Config::set('mail.from.address', $info->email); // MAIL_FROM_ADDRESS
            Config::set('mail.from.name', $info->name); // MAIL_FROM_NAME
        }

        if (App::runningInConsole() === false && request()->getHost()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());

            if (request()->isSecure()) {
                URL::forceScheme('https');
            }
        }
    }
}
