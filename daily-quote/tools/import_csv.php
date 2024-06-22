<?php declare(strict_types=1);

require __DIR__ . '../vendor/autoload.php';

/*
    CSVインポーター
        CSVからの取り込み用
 */

const COLLECTION_NAME = "quote-test";     // for debug
// const COLLECTION_NAME = "quote";     // for production

function run($argv) {
    $logger = new \yananob\mytools\Logger("daily-quote_import-csv");

    if (count($argv) <= 1) {
        echo "argv[1]: csv file path \n";
        return;
    }

    $file = fopen($argv[1], 'r');
    if ($file == false) {
        echo "couldn't open file: {$argv[1]}";
        return;
    }

    $firestore = new Google\Cloud\Firestore\FirestoreClient([
        "keyFilePath" => __DIR__ . '/configs/firebase.json'
    ]);

    $quote_col = $firestore->collection(COLLECTION_NAME);

    $line = 0;
    while (($data = fgetcsv($file)) !== false) {

        // for debug
        if ($line > 10) {
            break;
        }

        $line++;
        if ($line == 1) {
            $logger->log("skipping header");
            continue;
        }

        print_r($data);
        // $doc = $quote_col->newDocument();
        $doc = $quote_col->document(strval(intval($data[0]) - 1));
        $doc->set([
            "no" => IntVal($doc->id()),
            "message" => $data[1],
            "author" => $data[2],
            "source" => $data[3],
            "source_link" => $data[4],
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s'),
        ]);
    }

    fclose($file);

    $logger->log("Succeeded.");
}

run($argv);
