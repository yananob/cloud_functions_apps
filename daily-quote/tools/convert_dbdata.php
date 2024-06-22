<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/*
    DBデータコンバータ
        項目追加等で一括更新が必要な際に使用
 */

use MyApp\Quotes;

const COLLECTION_NAME = "quote-test";     // for debug
// const COLLECTION_NAME = "quote";     // for production

function run($argv) {
    date_default_timezone_set("Asia/Tokyo");
    $logger = new yananob\mytools\Logger("daily-quote");

    $quote_collection = (new Google\Cloud\Firestore\FirestoreClient([
        "keyFilePath" => __DIR__ . '/configs/firebase.json'
    ]))->collection(COLLECTION_NAME);
    $quotes_accessor = new Quotes(COLLECTION_NAME);
    $quotes = $quote_collection
        ->orderBy("created_at")
        ->documents();
    foreach ($quotes as $quote) {
        $id = IntVal($quote->id());
        $logger->log("processing {$id}");

        $quote = $quotes_accessor->get($id);
        if (!isset($quote["no"])) {
            # データ内容変換ロジック
            $quote["no"] = $id;

            # 一度削除してから再登録（update()だと、触られない項目もあるため）
            $logger->log("  saving {$id}");
            $quotes_accessor->remove($id);
            $quotes_accessor->add($quote, $id);
        }
    }
}

run($argv);
