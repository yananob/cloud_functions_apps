<?php declare(strict_types=1);

namespace MyApp;

// https://cloud.google.com/firestore/docs/samples/firestore-data-set-field?hl=ja

final class UnauthorizedException extends \Exception {}

final class Quotes
{
    private \Google\Cloud\Firestore\FirestoreClient $db_accessor;
    private \Google\Cloud\Firestore\CollectionReference $quote_collection;
    private \Google\Cloud\Firestore\DocumentReference $admin_document;
    // private TopicLogger $logger;
    private int $count_per_page;

    private array $input_fields = ["message", "author", "source", "source_link"];

    public function __construct($collection_name="daily-quotes", $count_per_page=20) {
        date_default_timezone_set("Asia/Tokyo");

        $this->db_accessor = new \Google\Cloud\Firestore\FirestoreClient([
            "keyFilePath" => __DIR__ . '/../configs/firebase.json'
        ]);
        $this->quote_collection = $this->db_accessor->collection($collection_name)->document("quotes")->collection("quotes");
        $this->admin_document = $this->db_accessor->collection($collection_name)->document("admin");
        // $this->logger = new TopicLogger();
        $this->count_per_page = $count_per_page;
    }

    public function getPassword(): string
    {
        return $this->admin_document->snapshot()->data()["password"];
    }

    public function get(int $index): array
    {
        $quote = $this->quote_collection->document(strval($index))->snapshot();
        if (!$quote->exists()) {
            throw new \Exception("Quote no. {$index} doesn't exist.");
        }

        return $this->__formatData($quote->data());
    }

    private function __formatData($data): array
    {
        // "no"はintにして戻す
        $data = array_merge($data, ["no" => IntVal($data["no"])]);

        return $data;
    }

    public function getRandom(): array
    {
        $count = $this->count();
        // $this->logger->log("  Total: {$count} quotes");

        $index = rand(0, $count - 1);
        // $this->logger->log("  Random index: {$index}");
        foreach ($this->quote_collection->orderBy("no")->offset($index)->limit(1)->documents() as $quote) {
            return $this->__formatData($quote->data());
        }
        return [];  // dummy
    }

    public function blank(): array
    {
        $result = ["no" => ""];
        foreach ($this->input_fields as $key) {
            $result[$key] = "";
        }
        return $result;
    }

    public function list(int $page = 1)
    {
        return $this->quote_collection
            ->orderBy("no")
            ->offset(($page - 1) * $this->count_per_page)
            ->limit($this->count_per_page)
            ->documents();
    }

    public function count(): int {
        return iterator_count($this->quote_collection->listDocuments());
    }

    public function maxPage(): int {
        return IntVal(ceil($this->count() / $this->count_per_page));
    }

    public function maxNo(): int {
        foreach ($this->quote_collection->orderBy("no", "DESC")->limit(1)->documents() as $quote) {
            $result = IntVal($quote["no"]);
            if ($result === 0) {
                throw new \Exception("Result was zero; anything is wrong!");
            }
            return $result;
        }
        return 9999999; // dummy
    }

    // memo: $indexは、、convert時のためなどにおいている
    public function add(array $data, int $index = null): void
    {
        $fields = ["no", "created_at", "updated_at"];
        array_push($fields, ...$this->input_fields);
        $doc = [];
        if (is_null($index)) {
            $data["no"] = $index = $this->maxNo() + 1;
        }
        foreach ($fields as $key) {
            $doc[$key] = isset($data[$key]) ? $data[$key] : "";
        }
        # convert用に、元の値があれば引き継ぎ
        if (empty($doc["created_at"])) {
            $doc["created_at"] = $this->__now();
        }
        if (empty($doc["updated_at"])) {
            $doc["updated_at"] = $doc["created_at"];
        }
        $this->quote_collection->document(strval($index))->set($doc);
    }

    public function update(int $index, array $data): void
    {
        $doc = [];
        // 一部フィールドの更新の場合、update()を使う＋渡すデータ形式も違う
        foreach ($this->input_fields as $key) {
            $doc[] = ["path" => $key, "value" => $data[$key]];
        }
        $doc[] = ["path" => "updated_at", "value" => $this->__now()];
        $this->quote_collection->document(strval($index))->update($doc);
    }

    public function remove(int $index): void
    {
        $this->quote_collection->document(strval($index))->delete();
    }

    private function __now(): string
    {
        return date('Y-m-d H:i:s');
    }

}
