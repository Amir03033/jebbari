<?php

namespace App\Console\Commands;

use Database\Seeders\PersonalBrandSeeder;
use Illuminate\Console\Command;

class ProposeContent extends Command
{
    protected $signature = 'content:propose {--replace : Propose every page anew instead of only adding missing sections} {--publish : Also publish the pages that have nothing left to fill in}';

    protected $description = 'Fill the pages with proposed texts (NL and EN) as drafts; nothing is published';

    public function handle(): int
    {
        PersonalBrandSeeder::$replace = (bool) $this->option('replace');
        PersonalBrandSeeder::$publish = (bool) $this->option('publish');
        $this->callSilent('db:seed', ['--class' => PersonalBrandSeeder::class, '--force' => true]);

        foreach (PersonalBrandSeeder::$report as $line) {
            $this->line('  '.$line);
        }

        $this->newLine();
        $this->info($this->option('publish')
            ? 'Pagina\'s zonder open punten zijn gepubliceerd; de rest staat als concept in /admin onder Pagina\'s.'
            : 'Niets is gepubliceerd. Bekijk de voorstellen in /admin onder Pagina\'s en publiceer wat klopt.');
        $this->line('Velden met [[ OWNER: ... ]] vul je zelf in; zolang ze er staan kan de pagina niet live.');

        return self::SUCCESS;
    }
}
