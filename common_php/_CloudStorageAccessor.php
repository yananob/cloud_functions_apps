<?php declare(strict_types=1);

namespace MyApp\common;

use Google\Cloud\Storage\StorageClient;

final class CloudStorageAccessor
{
    /**
     * Returns an authorized API client.
     * @return StorageClient the authorized client object
     */
    public static function getClient()
    {
        $storage = new StorageClient([
            'keyFile' => json_decode(file_get_contents(__DIR__ . '/configs_serviceaccount.json'), true) // TODO: project_id
        ]);

        return $storage;
    }
}