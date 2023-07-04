<?php

namespace Onetech\ExportDocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Onetech\ExportDocs\Exports\ApiExport;

class APISpecGeneratorCommand extends Command
{
    protected $signature = 'docs:api-spec {collection} {--environment=}';

    /**
     * @param
     * @return void
     *
     * @Description
     *
     * @Author minhluc
     *
     * @Date 2023/06/26
     */
    public function handle()
    {
        $collectionContent = $this->getCollectionContent();
        $validator = Validator::make($collectionContent, [
            'collection' => 'required',
            'collection.*.name' => 'required',
            'collection.*.item' => 'required',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors() as $error) {
                $this->error($error);
            }
        }

        $this->info('Generating api specs');
        foreach ($collectionContent['collection'] as $items) {
            foreach ($items as $index => $item) {
                $filename = data_get($item, 'name');
                $item = data_get($item, 'item');
                if (is_array($item)) {
                    $this->info("Generating collection $filename");
                    $apiExport = new ApiExport($item, $this->getEnvContent());
                    $apiExport->setFileName($filename);
                    $apiExport->save();
                }
            }
        }
    }

    /**
     * @param
     * @return array
     *
     * @Description
     *
     * @Author minhluc
     *
     * @Date 2023/06/26
     */
    public function getCollectionContent(): array
    {
        $fileContent = file_get_contents($this->argument('collection'));

        return json_decode($fileContent, true) ?? [];
    }

    /**
     * @param
     * @return array|mixed
     *
     * @Description
     *
     * @Author minhluc
     *
     * @Date 2023/06/26
     */
    public function getEnvContent(): mixed
    {
        $content = file_get_contents($this->option('environment'));
        $envContent = json_decode($content, true);

        return data_get($envContent, 'values');
    }
}
