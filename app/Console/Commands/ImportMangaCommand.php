<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MangaDexService;

class ImportMangaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manga:import {id : The MangaDex UUID of the series} {--lang=id : The language code, e.g. "id" for Indonesian or "en" for English}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a full Manga series and all its chapters instantly from MangaDex into PostgreSQL and MongoDB';

    /**
     * Execute the console command.
     */
    public function handle(MangaDexService $service)
    {
        $mangaDexId = $this->argument('id');
        $language = $this->option('lang');

        $this->info("Connecting to MangaDex API...");
        $this->info("Importing MangaDex ID: {$mangaDexId} | Language: {$language}");

        try {
            $manga = $service->importManga($mangaDexId, $language);
            
            $this->newLine();
            $this->info("==========================================================");
            $this->info(" SUCCESS: IMPORT COMPLETED!");
            $this->info("==========================================================");
            $this->info(" Title       : {$manga->title}");
            $this->info(" Slug        : {$manga->slug}");
            $this->info(" Type        : " . strtoupper($manga->type));
            $this->info(" Status      : " . strtoupper($manga->status));
            $this->info(" Chapters    : " . $manga->chapters()->count());
            $this->info("==========================================================");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(" FAILED: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
