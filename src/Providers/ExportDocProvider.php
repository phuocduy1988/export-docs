<?php

/*
 * This file is part of AWS Cognito Auth solution.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Onetech\ExportDocs\Providers;

use Illuminate\Support\ServiceProvider;
use Onetech\ExportDocs\Commands\APISpecGeneratorCommand;
use Onetech\ExportDocs\Commands\DatabaseGeneratorCommand;
use Onetech\ExportDocs\Commands\DBDiagramCommand;
use Onetech\ExportDocs\Commands\SequenceGeneratorCommand;

use OpenAI;
use OpenAI\Client;
use OpenAI\Contracts\ClientContract;
use Onetech\ExportDocs\Exceptions\ApiKeyIsMissing;

/**
 * Class AwsCognitoServiceProvider.
 */
class ExportDocProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //Register Alias
        // Load the helper functions
        require_once realpath(__DIR__ . '/../Helpers/Helper.php');

        $this->app->bind('command.docs:diagram', DBDiagramCommand::class);
        $this->app->bind('command.docs:api-spec', APISpecGeneratorCommand::class);
        $this->app->bind('command.docs:database', DatabaseGeneratorCommand::class);
        $this->app->bind('command.docs:sequence', SequenceGeneratorCommand::class);

        $this->commands([
            'command.docs:diagram',
            'command.docs:api-spec',
            'command.docs:database',
            'command.docs:sequence',
        ]);

        $this->app->singleton(ClientContract::class, static function (): Client {
            $apiKey = config('export-docs.api_key');
            $organization = config('export-docs.organization');

            if (! is_string($apiKey) || ($organization !== null && ! is_string($organization))) {
                throw ApiKeyIsMissing::create();
            }

            return OpenAI::factory()
                ->withApiKey($apiKey)
                ->withOrganization($organization)
                ->withHttpClient(new \GuzzleHttp\Client(['timeout' => config('export-docs.request_timeout', 30)]))
                ->make();
        });

        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, Client::class);
    }

    public function boot()
    {
        //Configuration path
        $path = realpath(__DIR__ . '/../../config/export-docs.php');

        //Publish config
        $this->publishes(
            [
                $path => config_path('export-docs.php'),
            ],
            'onetech-export-docs-config'
        );

        //Register configuration
        $this->mergeConfigFrom($path, 'export-docs');

        $this->loadViewsFrom(__DIR__.'/../views', 'docs');
    } //Function ends

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            Client::class,
            ClientContract::class,
            'openai',
        ];
    }

}
