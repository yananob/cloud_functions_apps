<?php declare(strict_types=1);

namespace MyApp;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\CollectionReference;
// use Google\Cloud\Firestore\DocumentReference;
// use MyApp\common\FirestoreAccessor;
use yananob\MyTools\CacheStore;
use MyApp\CacheItems;

class Accounts
{
    private FirestoreClient $dbAccessor;
    private CollectionReference $accountsCollection;

    public function __construct(bool $is_test = true)
    {
        $this->dbAccessor = new \Google\Cloud\Firestore\FirestoreClient(["keyFilePath" => __DIR__ . '/../configs/firebase.json']);
        $collection_name = "oml";
        if ($is_test) {
            $collection_name .= "-test";
        }
        $this->accountsCollection = $this->dbAccessor->collection($collection_name)->document("accounts")->collection("accounts");
    }

    public function list(): array
    {
        $cache = CacheStore::get(CacheItems::Accounts->value);
        if (!empty($cache)) {
            return $cache;
        }

        $accounts = [];
        foreach ($this->accountsCollection->listDocuments() as $doc) {
            $data = $doc->snapshot()->data();
            $accounts[$data["userid"]] = $data;
        }
        CacheStore::put(CacheItems::Accounts->value, $accounts);
        return $accounts;
    }
}
