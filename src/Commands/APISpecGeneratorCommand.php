<?php

namespace Onetech\ExportDocs\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Onetech\ExportDocs\Exports\ApiExport;

class APISpecGeneratorCommand extends Command
{
    protected $signature = 'docs:api-spec {--collection=} {--environment=}';

    /**
     * @param
     * @return void
     * @Description
     * @Author minhluc
     * @Date   2023/06/26
     */
    public function handle()
    {
        $collectionContent = $this->getCollectionContent();
        $validator = Validator::make($collectionContent, [
            'item' => 'required',
            'item.*.name' => 'required',
            'item.*.item' => 'required',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors() as $error) {
                $this->error($error);
            }
        }

        $this->info('Generating api specs');
        $items = [];
        $this->parseAPIRequest($items, $collectionContent['item']);
        $bar = $this->output->createProgressBar(count($items));
        $filename = config('app.name').'-api-document'.moduleDateNow('Y-m-dHis');
        if (is_array($items)) {
            $this->info("Generating collection $filename");
            $apiExport = new ApiExport($items, $this->getEnvContent(), $bar);
            $apiExport->setFileName($filename);
            $apiExport->save();
        }
    }

    private function parseAPIRequest(&$items, $collectionContentItems, $sheetNameParent = ''): void
    {
        foreach ($collectionContentItems as $index => $item) {
            $sheetName = $sheetNameParent ? $sheetNameParent .'_'. $item['name'] : $item['name'];
            if(data_get($item, 'item')){
                $this->parseAPIRequest($items, $item['item'], $sheetName);
            } else {
                $item['name'] = $sheetName;
                $items[] = $item;
            }
        }
    }

    /**
     * @param
     * @return array
     * @Description
     * @Author minhluc
     * @Date   2023/06/26
     */
    public function getCollectionContent(): array
    {
        $fileContent = file_get_contents($this->option('collection'));

        return json_decode($fileContent, true) ?? [];
    }

    /**
     * @param
     * @return array|mixed
     * @Description
     * @Author minhluc
     * @Date   2023/06/26
     */
    public function getEnvContent(): mixed
    {
        $content = file_get_contents($this->option('environment'));
        $envContent = json_decode($content, true);

        return data_get($envContent, 'values');
    }

    public function createBackupFile()
    {
        if (!is_dir(storage_path("backups"))) {
            mkdir(storage_path("backups"));
        }

        $filename = storage_path("backups/") . dateNow('YmdHis') . "_APISpecs.zip";
        $path = storage_path("app/export/api");

        $zip = new \ZipArchive();
        $zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $name => $file) {
            // We're skipping all sub folders
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                // extracting filename with substr/strlen
                $relativePath = 'export/api/' . substr($filePath, strlen($path) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }
}
