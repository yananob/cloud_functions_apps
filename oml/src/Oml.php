<?php declare(strict_types=1);

namespace MyApp;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\CollectionReference;
use yananob\MyTools\Utils;
use yananob\MyTools\Logger;
use yananob\MyTools\CacheStore;
use MyApp\CacheItems;
use MyApp\Accounts;

final class Oml
{
    private FirestoreClient $dbAccessor;
    private CollectionReference $rootCollection;
    private Accounts $accounts;
    private Logger $logger;

    private const RESERVED_BOOKS_COLLECTION_NAME = "reserved_books";
    private const LENDING_BOOKS_COLLECTION_NAME = "lending_books";

    public const DATETIME_FORMAT = "Y/m/d H:i:s";
    public const MAX_RESERVABLE_COUNT = 15;
    public const MAX_LENDABLE_COUNT = 15;

    public function __construct(bool $is_test = true)
    {
        date_default_timezone_set('Asia/Tokyo');

        $this->logger = new Logger("Oml");
        $this->dbAccessor = new \Google\Cloud\Firestore\FirestoreClient(["keyFilePath" => __DIR__ . '/../configs/firebase.json']);
        $collection_name = "oml";
        if ($is_test) {
            $collection_name .= "-test";
        }
        $this->rootCollection = $this->dbAccessor->collection($collection_name);

        $this->accounts = new Accounts($is_test);
    }

    public function getUserIds(): array
    {
        $result = [];
        foreach ($this->accounts->list() as $account) {
            $result[] = $account["userid"];
        }
        return $result;
    }

    public function getReservedBooks(string $userId): array
    {
        // Check whether cache is expired or not
        $reservedUpdatedDate = $this->getUpdatedDates(false)["reserved_books"];
        $cache = CacheStore::get(CacheItems::ReservedBooks->value);
        if (!empty($cache) && $cache["timestamp"] != $reservedUpdatedDate) {
            $this->logger->log("Clearing reserved_books cache");
            CacheStore::clear(CacheItems::ReservedBooks->value);
        }

        $cache = CacheStore::get(CacheItems::ReservedBooks->value);
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $this->logger->log("getting reservedBooks from FireStore: {$userId}");
        $result = [];
        foreach ($this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->collection($userId)->listDocuments() as $doc) {
            $result[] = ReservedBook::fromArray($doc->snapshot()->data());
        }
        $result = Utils::sortObjectArrayByProperty($result, "reservedBookId");

        // Store cache with timestamp
        $cache[$userId] = $result;
        $cache["timestamp"] = $reservedUpdatedDate;
        CacheStore::put(CacheItems::ReservedBooks->value, $cache);

        return $result;
    }

    public function getUserReservedBook(string $userId, string $bookId): ReservedBook|null
    {
        foreach ($this->getReservedBooks($userId) as $reservedBook) {
            if ($reservedBook->reservedBookId === $bookId) {
                return $reservedBook;
            }
        }
        return null;
    }

    public function getLendingBooks(string $userId): array
    {
        // Check whether cache is expired or not
        $lendingUpdatedDate = $this->getUpdatedDates(false)["lending_books"];
        $cache = CacheStore::get(CacheItems::LendingBooks->value);
        if (!empty($cache) && $cache["timestamp"] != $lendingUpdatedDate) {
            $this->logger->log("Clearing lending_books cache");
            CacheStore::clear(CacheItems::LendingBooks->value);
        }

        $cache = CacheStore::get(CacheItems::LendingBooks->value);
        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $this->logger->log("getting lendingBooks from FireStore: {$userId}");
        $result = [];
        foreach ($this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)->collection($userId)->listDocuments() as $doc) {
            $result[] = LendingBook::fromArray($doc->snapshot()->data());
        }
        $result = Utils::sortObjectArrayByProperty($result, "lendingBookId");

        // Store cache with timestamp
        $cache[$userId] = $result;
        $cache["timestamp"] = $lendingUpdatedDate;
        CacheStore::put(CacheItems::LendingBooks->value, $cache);
        return $result;
    }

    // private function __setUpdatedTimestampsExpired(): void
    // {
    //     CacheStore::clear(CacheItems::UpdatedTimestamps->value);
    // }

    public function updateReservedBooks(string $userId): void
    {
        $this->logger->log("updating reservedBooks of {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $books = $crawler->crawlReservedBooks();
        $this->__saveReservedBooks($userId, $books);
    }

    private function __saveReservedBooks(string $userId, array $books): void
    {
        $this->__removeDocumentsInCollection($this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->collection($userId));

        foreach ($books as $book) {
            $book = $book->toArray();
            $this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->collection($userId)->newDocument()->set($book);
        }
    }

    public function updateLendingBooks(string $userId): void
    {
        $this->logger->log("updating lendingBooks of {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $books = $crawler->crawlLendingBooks();
        $this->__saveLendingBooks($userId, $books);
    }

    private function __saveLendingBooks(string $userId, array $books)
    {
        $this->__removeDocumentsInCollection($this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)->collection($userId));

        foreach ($books as $book) {
            $book = $book->toArray();
            $this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)->collection($userId)->newDocument()->set($book);
        }
    }

    private function __removeDocumentsInCollection(CollectionReference $col): void
    {
        foreach ($col->listDocuments() as $doc) {
            $doc->delete();
        }
    }

    public function __addReservedBookInfo(string $userId, ReservedBook $book): void
    {
        $this->logger->log("adding reservedBookInfo of {$book->reservedBookId}");
        $book = $book->toArray();
        $this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->collection($userId)->newDocument()->set($book);
    }

    public function __updateLendingBookInfo(string $userId, LendingBook $book): void
    {
        $this->logger->log("updating lendingBookInfo of {$book->lendingBookId}");
        $query = $this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)
                    ->collection($userId)->where("lending_book_id", "==",  $book->lendingBookId);
        $documents = iterator_to_array($query->documents());
        if (count($documents) != 1) {
            throw new \Exception("Got more/less than " . count($documents) . " documents by bookId " . $book->lendingBookId);
        }
        $documents[0]->reference()->set(
            $book->toArray(),
            ["merge" => true],
        );
    }

    public function getUpdatedDates(bool $fromCache=true): array
    {
        if ($fromCache) {
            $nullValue = [
                "reserved_books" => null,
                "lending_books" => null,
            ];
            $cache = CacheStore::get(CacheItems::UpdatedTimestamps->value, $nullValue);
            if ($cache != $nullValue) {
                return $cache;
            }
        }

        $result = [
            "reserved_books" => new \DateTime($this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->snapshot()->data()["updated_timestamp"]),
            "lending_books" => new \DateTime($this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)->snapshot()->data()["updated_timestamp"]),
        ];
        CacheStore::put(CacheItems::UpdatedTimestamps->value, $result);
        return $result;
    }

    public function updateReservedBooksUpdatedDate(\DateTime $datetime = new \DateTime()): void
    {
        $this->logger->log("updating reservedBooksUpdatedDate");
        $this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)->set(
            ["updated_timestamp" => $datetime->format(self::DATETIME_FORMAT)],
            ["merge" => true],
        );
        // $this->__setUpdatedTimestampsExpired();
    }

    public function updateLendingBooksUpdatedDate(\DateTime $datetime = new \DateTime()): void
    {
        $this->logger->log("updating lendingBooksUpdatedDate");
        $this->rootCollection->document(self::LENDING_BOOKS_COLLECTION_NAME)->set(
            ["updated_timestamp" => $datetime->format(self::DATETIME_FORMAT)],
            ["merge" => true],
        );
        // $this->__setUpdatedTimestampsExpired();
    }

    public function search(string $keyword="", string $title="", string $author="", int $page=1): array
    {
        $crawler = new Crawler("", "");     // TODO: 検索時（ログインしないとき）のuseridの渡し方改善
        $this->logger->log("searching keyword={$keyword}, title={$title}, author={$author} for page {$page}");
        return $crawler->search($keyword, $title, $author, $page);
    }

    function getList(RssType $rssType, string $category): array
    {
        $rss = new Rss($rssType);
        $this->logger->log("getting rss list of {$rssType->value}.{$category}");
        return $rss->listBooks($category);
    }

    public function getBookReserveInfo(string $bookId): array
    {
        $crawler = new Crawler("", "");     // TODO: 検索時（ログインしないとき）のuseridの渡し方改善
        return $crawler->getBookReserveInfo($bookId);
    }

    public function reserve(string $bookId, string $userId=null): string
    {
        if (empty($userId)) {
            $userId = $this->__getReservableUserId();
            if (empty($userId)) {
                throw new \Exception("予約できるユーザーがなく、予約できませんでした");
            }
            $this->logger->log("using account {$userId} to reserve");
        }
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $reservedBook = $crawler->reserve($bookId);
        $this->__addReservedBookInfo($userId, $reservedBook);
        $this->updateReservedBooksUpdatedDate();
        // $this->__addReservedCount($userId);

        return $userId;
    }

    public function getTotalReservableCount(): int
    {
        $result = 0;
        foreach ($this->getUserIds() as $userId) {
            $result += $this->getUserReservableCount($userId);
        }
        return $result;
    }

    public function getUserReservableCount(string $userId): int
    {
        return self::MAX_RESERVABLE_COUNT - count($this->getReservedBooks($userId));
    }

    private function __getReservableUserId(): string|null
    {
        foreach ($this->getUserIds() as $userId) {
            if ($this->getUserReservableCount($userId) > 0) {
                return $userId;
            }
        }
        return null;
    }

    public function extend(string $userId, string $bookId): void
    {
        $this->logger->log("extending book {$bookId} of user {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $lendingBook = $crawler->extendLendingBook($bookId);
        $this->__updateLendingBookInfo($userId, $lendingBook);
        $this->updateLendingBooksUpdatedDate();
    }

    public function reserveAgain(string $userId, string $bookId): string
    {
        $this->logger->log("reserving again {$bookId} of user {$userId}");
        $this->cancelReservation($userId, $bookId);
        return $this->reserve($bookId, $userId);
    }

    public function cancelReservation(string $userId, string $bookId): void
    {
        $this->logger->log("canceling reservation of book {$bookId} of user {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $crawler->cancelReservation($bookId, $this->getUserReservedBook($userId, $bookId)->changingId, count($this->getReservedBooks($userId)));
        $this->__removeReservedBookInfo($userId, $bookId);
        $this->updateReservedBooksUpdatedDate();
    }

    private function __removeReservedBookInfo(string $userId, string $bookId): void
    {
        $this->logger->log("removing reservedBook of {$bookId}");
        $query = $this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)
                    ->collection($userId)->where("reserved_book_id", "==",  $bookId);
        $documents = iterator_to_array($query->documents());
        if (count($documents) != 1) {
            throw new \Exception("Got more/less than " . count($documents) . " documents by bookId " . $bookId);
        }
        $documents[0]->reference()->delete();
    }

    public function getUpcomingAdultList(): array
    {
        return [
            "00" => "読書・報道・雑学",
            "01" => "哲学・心理学・宗教",
            "02" => "歴史・伝記",
            "03" => "地理・旅行ガイド",
            "04" => "政治・法律・経済・社会科学",
            "05" => "社会福祉・教育",
            "06" => "自然科学",
            "07" => "動物・植物",
            // "08" => "医学・薬学",
            "09" => "技術・工学・環境問題",
            "10" => "コンピュータ・情報科学",
            "11" => "生活・料理・育児",
            "12" => "産業・園芸・ペット",
            "13" => "芸術・音楽",
            "15" => "スポーツ・娯楽",
            "16" => "言語・語学・スピーチ",
            "17" => "文学",
            "18" => "日本の小説",
            "19" => "外国の小説",
            "20" => "エッセイ",
        ];
    }

    public function getUpcomingChildList(): array
    {
        return [
            "02" => "歴史・伝記",
            "04" => "社会・仕事",
            "05" => "バリアフリー・教育",
            "06" => "科学・算数・宇宙",
            "07" => "動物・植物",
            "09" => "工業・環境・乗り物",
            "11" => "手芸・料理",
            "12" => "産業・生き物の育て方",
            "13" => "芸術・工作・音楽",
            "14" => "絵本",
            "15" => "スポーツ・遊び",
            "16" => "言葉・外国語",
            "17" => "文学",
            "18" => "日本の物語",
            "19" => "外国の物語",
        ];
    }

    public function getBestListPeriods(): array
    {
        $result = [];

        $currentMonth = new \DateTime("last month");
        for ($i = 0; $i < 12; $i++) {
            $previousMonth = clone $currentMonth;
            $previousMonth->modify("-1 month");
            $result[$previousMonth->format("Ym")] = $currentMonth->format("Y/m");
            $currentMonth->modify("-1 month");
        }

        return $result;
    }
}