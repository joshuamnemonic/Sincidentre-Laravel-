<?php

namespace App\Providers;

use App\Models\Report;
use App\Policies\ReportPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Report::class, ReportPolicy::class);

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $appName = config('app.name', 'Sincidentre');
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $expire = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');
            $supportEmail = (string) config('mail.from.address');
            $supportLine = $supportEmail !== ''
                ? "If you need help, contact our support team at {$supportEmail}."
                : 'If you need help, please contact your campus support team.';

            $firstName = trim((string) ($notifiable->first_name ?? ''));
            $greeting = $firstName !== '' ? "Hello {$firstName}," : 'Hello,';

            return (new MailMessage)
                ->subject("{$appName} Password Reset Request")
                ->greeting($greeting)
                ->line("We received a request to reset the password for your {$appName} account.")
                ->action('Reset Password', $resetUrl)
                ->line("This link will expire in {$expire} minutes.")
                ->line('If you did not request a password reset, no further action is required.')
                ->line($supportLine)
                ->salutation("Regards,\n{$appName} Team");
        });
    }
}
