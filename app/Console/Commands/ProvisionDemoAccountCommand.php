<?php

namespace App\Console\Commands;

use App\Security\RoleCatalogue;
use App\Services\DemoAccountService;
use Illuminate\Console\Command;

class ProvisionDemoAccountCommand extends Command
{
    protected $signature = 'schoolcore:demo-account {--reseed : Seed sample data even if it already exists}';

    protected $description = 'Create or repair the shared school demo account and its role personas';

    public function handle(DemoAccountService $demo): int
    {
        $user = $demo->ensureAccount($this->option('reseed'));

        $this->info('Demo account ready.');
        $this->line('  School: '.($user->school?->name ?? 'Unknown'));
        $this->line('  URL: '.($user->school?->subdomain ?? 'demo').'.'.parse_url((string) config('platform.base_url'), PHP_URL_HOST).'/workspace');
        $this->line('  Email: '.$user->email);
        $this->line('  Roles: '.count(RoleCatalogue::roles()).' switchable personas');

        return Command::SUCCESS;
    }
}
