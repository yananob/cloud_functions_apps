<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Google\CloudFunctions\FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEvents\V1\CloudEventInterface $event): void
{
    $logger = new yananob\mytools\Logger("firestore-backup");

    $db_accessor = Google\Cloud\Firestore\FirestoreClient([
        "keyFilePath" => __DIR__ . '/configs/firebase.json'
    ]);
    $storage = new Google\Cloud\Storage\StorageClient([
        'keyFile' => json_decode(file_get_contents(__DIR__ . '/configs_serviceaccount.json'), true)
    ]);


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