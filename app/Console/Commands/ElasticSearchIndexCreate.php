<?php

namespace App\Console\Commands;

use App\Services\ElasticSearchService;
use Illuminate\Console\Command;

class ElasticSearchIndexCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:create-index';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the ElasticSearch products index';

    /**
     * Execute the console command.
     */
    public function handle(ElasticSearchService $elasticSearch): int
    {
        $this->info('Creating ElasticSearch index...');

        try {
            $elasticSearch->createIndex();
            $this->info('ElasticSearch index created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create index: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
