<?php declare(strict_types=1);

namespace MyApp\common;

use Google\Cloud\Firestore\FirestoreClient;

final class FirestoreAccessor
{
    /**
     * Returns an authorized API client.
     * @return FirestoreClient the authorized client object
     */
    public static function getClient()
    {
        $firestore = new FirestoreClient([
            "keyFilePath" => __DIR__ . '/configsebase.json'
        ]);

        return $firestore;
    }
}