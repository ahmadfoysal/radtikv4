<?php

namespace App\Console\Commands;

use App\Models\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateRouterNasIdentifiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'routers:generate-nas-identifiers {--force : Force regeneration even for routers with existing identifiers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate unique NAS identifiers for routers that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        $query = Router::query();

        if (!$force) {
            $query->whereNull('nas_identifier');
        }

        $routers = $query->get();

        if ($routers->isEmpty()) {
            $this->info('No routers found that need NAS identifiers.');
            return self::SUCCESS;
        }

        $this->info("Found {$routers->count()} router(s) to process.");

        $progressBar = $this->output->createProgressBar($routers->count());
        $progressBar->start();

        $updated = 0;

        foreach ($routers as $router) {
            $nasIdentifier = $this->generateUniqueNasIdentifier($router->name);

            $router->update(['nas_identifier' => $nasIdentifier]);

            $updated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Successfully generated NAS identifiers for {$updated} router(s).");

        return self::SUCCESS;
    }

    /**
     * Generate a unique NAS identifier using router name in kebab-case with timestamp
     */
    private function generateUniqueNasIdentifier(string $routerName): string
    {
        // Convert router name to kebab-case
        $prefix = Str::slug($routerName);

        // Generate unique identifier with timestamp
        $timestamp = now()->format('YmdHis');
        $nasIdentifier = "{$prefix}-{$timestamp}";

        // Ensure uniqueness (in case of simultaneous creation)
        $counter = 1;
        while (Router::where('nas_identifier', $nasIdentifier)->exists()) {
            $nasIdentifier = "{$prefix}-{$timestamp}-{$counter}";
            $counter++;
        }

        return $nasIdentifier;
    }
}
