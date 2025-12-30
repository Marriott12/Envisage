<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateApiDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:docs {--format=json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation in OpenAPI/Swagger format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating API documentation...');

        $format = $this->option('format');
        $outputPath = storage_path('api-docs/api-docs.' . $format);

        // Ensure directory exists
        if (!file_exists(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        // Generate documentation using annotations
        $openapi = \OpenApi\Generator::scan([
            app_path('Http/Controllers'),
            app_path('Models'),
        ]);

        // Save to file
        if ($format === 'json') {
            file_put_contents($outputPath, $openapi->toJson());
        } else {
            file_put_contents($outputPath, $openapi->toYaml());
        }

        $this->info("API documentation generated successfully!");
        $this->info("Location: {$outputPath}");
        $this->info("View at: /api/documentation");

        return 0;
    }
}
