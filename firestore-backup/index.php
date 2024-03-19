<?php declare(strict_types=1);

use CloudEvents\V1\CloudEventInterface;
use Google\CloudFunctions\FunctionsFramework;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\QuerySnapshot;
use MyApp\common\Logger;
use MyApp\common\Utils;
use MyApp\common\FirestoreAccessor;
use MyApp\common\CloudStorageAccessor;

FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEventInterface $event): void
{
    $logger = new Logger("firestore-backup");

    $db_accessor = FirestoreAccessor::getClient();
    $storage = CloudStorageAccessor::getClient();

    $config = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.json"), true);

    foreach ($config["firestore"] as $target) {
        $logger->log("Processing [" . $target["path"] . "]");

        $tmp_filepath = null;
        if ($target["type"] === "collection") {
            $tmp_filepath = __save_csv($target["columns"], $db_accessor->collection($target["path"])->documents());
        }
        // document:
        // $backup_doc = $db_accessor->document("daily-quotes-test/admin")->snapshot()->data();

        $bucket = $storage->bucket($config["storage"]["bucket"]);
        $bucket->upload(
            fopen($tmp_filepath, 'r'),
            [
                "name" => date('Y-m-d') . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, "_", $target["path"]) . ".csv",
            ]
        );
    }


    $logger->log("Succeeded.");
}

function __save_csv(array $columns, QuerySnapshot $documents): string
{
    $tmpfname = tempnam(__DIR__ . DIRECTORY_SEPARATOR . "tmp", "temp.csv");
    $fp = fopen($tmpfname, "w");
    try {
        fputcsv($fp, $columns);
        foreach ($documents as $doc) {
            fputcsv($fp, $doc->data());
        }
    } finally {
        fclose($fp);
    }
    return $tmpfname;
}