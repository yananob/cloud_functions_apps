<?php declare(strict_types=1);

require_once __DIR__ . '../vendor/autoload.php';

/*
    DBデータ移動
        collectionの場所を変える際に使用
 */

// const OLD_COLLECTION_NAME = "quote-test";     // for debug
// const NEW_COLLECTION_NAME = "daily-quotes-test";     // for debug
const OLD_COLLECTION_NAME = "quote";     // for production
const NEW_COLLECTION_NAME = "daily-quotes";     // for production

function getFirestoreClient(): Google\Cloud\Firestore\FirestoreClient
{
    return new Google\Cloud\Firestore\FirestoreClient([
        "keyFilePath" => __DIR__ . '/configs/firebase.json'
    ]);
}


function run($argv) {
    date_default_timezone_set("Asia/Tokyo");
    $logger = new yananob\mytools\Logger("daily-quote");

    $old_quote_col = getFirestoreClient()->collection(OLD_COLLECTION_NAME);
    $new_quote_col = getFirestoreClient()->collection(NEW_COLLECTION_NAME);

    foreach ($old_quote_col->orderBy("no")->documents() as $old_doc) {
        $no = $old_doc['no'];
        $logger->log("processing {$no}");

        $new_doc = $new_quote_col->document("quotes")->collection("quotes")->document(strval($no));
        $new_doc->set($old_doc->data());
    }
}

run($argv);
