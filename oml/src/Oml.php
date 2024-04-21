<?php declare(strict_types=1);

namespace MyApp;

use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use MyApp\common\Utils;
use MyApp\common\Logger;
use MyApp\common\FirestoreAccessor;
use MyApp\common\CacheStore;
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

    // TODO: Omlの機能で、検索・予約一覧取得・更新とかをする

    public function __construct($is_test=true) {
        date_default_timezone_set('Asia/Tokyo');

        $this->logger = new Logger("Oml");
        $this->dbAccessor = FirestoreAccessor::getClient();
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
            CacheStore::clear(CacheItems::ReservedCount->value);
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

    public function updateReservedBookInfo(string $userId, ReservedBook $book): void
    {
        $this->logger->log("updating reservedBookInfo of {$book->reservedBookId}");
        $query = $this->rootCollection->document(self::RESERVED_BOOKS_COLLECTION_NAME)
                    ->collection($userId)->where("reserved_book_id", "==",  $book->reservedBookId);
        $documents = iterator_to_array($query->documents());
        if (count($documents) != 1) {
            throw new \Exception("Got more/less than " . count($documents) . " documents by bookId " . $book->reservedBookId);
        }
        $documents[0]->reference()->set(
            $book->toArray(),
            ["merge" => true],
        );
    }

    public function updateLendingBookInfo(string $userId, LendingBook $book): void
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

    public function getBookReserveInfo(string $bookId): array
    {
        $crawler = new Crawler("", "");     // TODO: 検索時（ログインしないとき）のuseridの渡し方改善
        return $crawler->getBookReserveInfo($bookId);
    }

    public function reserve(string $bookId): string
    {
        $userId = $this->__getReservableUserId();
        if (empty($userId)) {
            throw new \Exception("予約できるユーザーがなく、予約できませんでした");
        }
        $this->logger->log("using account {$userId} to reserve");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $reservedBook = $crawler->reserve($bookId);
        $this->updateReservedBookInfo($userId, $reservedBook); // TODO
        $this->updateReservedBooksUpdatedDate(); // TODO
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
        $reservedCounts = CacheStore::get(CacheItems::ReservedCount->value);
        $reservedCount = isset($reservedCounts[$userId]) ? $reservedCounts[$userId] : 0;
        return self::MAX_RESERVABLE_COUNT - (count($this->getReservedBooks($userId)) + $reservedCount);
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

    // private function __addReservedCount(string $userId): void
    // {
    //     $reservedCounts = CacheStore::get(CacheItems::ReservedCount->value);

    //     if (!isset($reservedCounts[$userId])) {
    //         $reservedCounts[$userId] = 0;
    //     }
    //     $reservedCounts[$userId]++;

    //     CacheStore::put(CacheItems::ReservedCount->value, $reservedCounts);
    // }

    public function extend(string $userId, string $bookId): void
    {
        $this->logger->log("extending book {$bookId} of user {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $lendingBook = $crawler->extendLendingBook($bookId);
        $this->updateLendingBookInfo($userId, $lendingBook);
        // TODO: $this->updateLendingBooksUpdatedDate()
    }

    public function reserveAgain(string $userId, string $bookId): string
    {
        $this->logger->log("reserving again {$bookId} of user {$userId}");
        $this->cancelReservation($userId, $bookId);
        return $this->reserve($bookId);
    }

    public function cancelReservation(string $userId, string $bookId): void
    {
        $this->logger->log("canceling reservation of book {$bookId} of user {$userId}");
        $crawler = new Crawler($userId, $this->accounts->list()[$userId]["password"]);
        $crawler->cancelReservation($bookId, $this->getUserReservedBook($userId, $bookId)->changingId, count($this->getReservedBooks($userId)));
    }
}
