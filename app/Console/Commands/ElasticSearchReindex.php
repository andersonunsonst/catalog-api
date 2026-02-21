<?php

namespace App\Console\Commands;

use App\Services\ElasticSearchService;
use Illuminate\Console\Command;

class ElasticSearchReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all products in ElasticSearch';

    /**
     * Execute the console command.
     */
    public function handle(ElasticSearchService $elasticSearch): int
    {
        $this->info('Reindexing all products...');

        try {
            $elasticSearch->bulkIndexProducts();
            $this->info('All products reindexed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to reindex: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
