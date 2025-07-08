<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;
use Illuminate\Database\QueryException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            // Load all system settings into config
            $settings = SystemSetting::all()->pluck('value', 'key');

            foreach ($settings as $key => $value) {
                Config::set($key, $value);
                $_ENV[$key] = $value; // Optional for global environment override
            }

            // Explicitly configure email settings
            $this->configureMailSettings($settings);

        } catch (QueryException $e) {
            // Log the error but continue running the application
            \Log::error('Error loading system settings: ' . $e->getMessage());
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register any application services.
    }

    /**
     * Configure mail settings dynamically.
     *
     * @param \Illuminate\Support\Collection $settings
     * @return void
     */
    protected function configureMailSettings($settings)
    {
        Config::set('mail.mailer', $settings->get('MAIL_MAILER', 'smtp'));
        Config::set('mail.host', $settings->get('MAIL_HOST'));
        Config::set('mail.port', $settings->get('MAIL_PORT'));
        Config::set('mail.username', $settings->get('MAIL_USERNAME'));
        Config::set('mail.password', $settings->get('MAIL_PASSWORD'));
        Config::set('mail.encryption', $settings->get('MAIL_ENCRYPTION'));
        Config::set('mail.from.address', $settings->get('MAIL_FROM_ADDRESS'));
        Config::set('mail.from.name', $settings->get('MAIL_FROM_NAME'));
    }
}
