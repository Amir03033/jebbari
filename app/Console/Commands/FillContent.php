<?php

namespace App\Console\Commands;

use Database\Seeders\SiteFillSeeder;
use Illuminate\Console\Command;

class FillContent extends Command
{
    protected $signature = 'content:fill';

    protected $description = 'Fill the gaps on the live site (skills, stack, services in the first person, project results and timeline) as unpublished changes';

    public function handle(): int
    {
        $this->callSilent('db:seed', ['--class' => SiteFillSeeder::class, '--force' => true]);

        foreach (SiteFillSeeder::$report as $line) {
            $this->line('  '.$line);
        }

        if (SiteFillSeeder::$todo) {
            $this->newLine();
            $this->warn('Alleen jij kunt dit aanvullen:');
            foreach (SiteFillSeeder::$todo as $line) {
                $this->line('  - '.$line);
            }
        }

        $this->newLine();
        $this->info('Niets is gepubliceerd. Bekijk de wijzigingen in /admin (Pagina\'s, Diensten, Projecten) en publiceer wat klopt.');

        return self::SUCCESS;
    }
}
