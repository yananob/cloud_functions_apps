<?php declare(strict_types=1);

use CloudEvents\V1\CloudEventInterface;
use Psr\Http\Message\ServerRequestInterface;
use Google\CloudFunctions\FunctionsFramework;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MyApp\common\Logger;
use MyApp\common\Utils;
use MyApp\common\LINE;
use MyApp\common\MessagesQueue;
use MyApp\AlertType;
use MyApp\Alerter;
use MyApp\BookState;
use MyApp\BookType;
use MyApp\Command;
use MyApp\Oml;

const APP_NAME = "oml";

FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): string
{
    $logger = new Logger(APP_NAME);
    $params = $request->getQueryParams();
    $params = array_merge($params, $request->getParsedBody());
    $logger->log(str_repeat("-", 120));
    $logger->log("Params: " . json_encode($params));

    session_start([
        "cookie_lifetime" => 60 * 30,    // 30 mins
    ]);

    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . "/templates");

    $isLocal = Utils::isLocalHttp($request);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $oml = new Oml($isLocal);
    $alerter = new Alerter($isLocal, "oml");
    $messagesQueue = new MessagesQueue();

    $smarty->assign("is_local", $isLocal);
    $smarty->assign("base_path", Utils::getBasePath($isLocal, APP_NAME));
    $smarty->assign("updated_dates", $oml->getUpdatedDates());
    $smarty->assign("messages", $messagesQueue->popMessages());
    $smarty->assign("alerts", $messagesQueue->popAlerts());
    $smarty->assign("errors", $messagesQueue->popErrors());

    if (isset($params["cmd"])) {
        $cmd = $params["cmd"];
    } else {
        $cmd = "main";
    }
    switch ($cmd) {
        case Command::Main->value:    // TODO: ajax化（遅延で詳細取得）
            $books = $all_reserved_books = $all_lending_books = [];
            foreach ($oml->getUserIds() as $user_id) {
                $logger->log("processing " . $user_id);
                $reserved_books = $oml->getReservedBooks($user_id);
                $lending_books = $oml->getLendingBooks($user_id);
                $count_keeping = 0; $count_overdue = 0;
                // TODO: omlあたりにカウントしてほしい
                foreach ($reserved_books as $reserved_book) {
                    if ($reserved_book->state === BookState::Keeping) $count_keeping++;
                }
                foreach ($lending_books as $lending_book) {
                    if ($lending_book->isReturndateCame()) $count_overdue++;
                }
                $books[$user_id] = [
                    "reserved_books" => $reserved_books,
                    "count_keeping" => $count_keeping,
                    "lending_books" => $lending_books,
                    "count_overdue" => $count_overdue,
                ];
                array_push($all_reserved_books, ...$reserved_books);
                array_push($all_lending_books, ...$lending_books);
            }
            $alerter->checkAll($all_reserved_books, $all_lending_books);
            $smarty->assign("books", $books);
            $smarty->assign("alerts", $alerter->getMessages());
            return $smarty->fetch('main.tpl');

        case Command::ListReserved->value:
            $books = [];
            foreach ($oml->getUserIds() as $userId) {
                foreach ($oml->getReservedBooks($userId) as $book) {
                    $books[] = $book;
                }
            }
            $alerter->checkKeepLimitdate($books);

            $smarty->assign("books", $books);
            $smarty->assign("alerts", $alerter->getMessages());
            return $smarty->fetch('reservedList.tpl');

        case Command::ListLending->value:
            $books = [];
            foreach ($oml->getUserIds() as $userId) {
                foreach ($oml->getLendingBooks($userId) as $book) {
                    $books[] = $book;
                }
            }
            $alerter->checkReturnLimitDate($books);

            $smarty->assign("books", $books);
            $smarty->assign("alerts", $alerter->getMessages());
            return $smarty->fetch('lendingList.tpl');

        case Command::UpdateAllReserved->value:    // TODO: ajax化
            __update_all_reserved($isLocal, $oml, $logger, $isParallel=!$isLocal);
            $messagesQueue->pushMessage("予約リストを更新しました。");
            __redirect_to_reserved_list($isLocal);

        case Command::UpdateAllLending->value:    // TODO: ajax化
            __update_all_lending($isLocal, $oml, $logger, $isParallel=!$isLocal);
            $messagesQueue->pushMessage("貸出リストを更新しました。");
            __redirect_to_lending_list($isLocal);

        case Command::UpdateAccountReserved->value:
            $oml->updateReservedBooks($params["account"]);
            return __json_response(200);

        case Command::UpdateAccountLending->value:
            $oml->updateLendingBooks($params["account"]);
            return __json_response(200);

        case Command::Search->value:
            $smarty->assign("totalReservableCount", $oml->getTotalReservableCount());
            return $smarty->fetch('search.tpl');

        case Command::JsonSearch->value:
            try {
                $searchedBooks = $oml->search(
                    $params["keyword"], $params["title"], $params["author"], (int)$params["page"]);
                $smarty->assign("books", $searchedBooks);
                $html = $smarty->fetch('ajax/searchedBooks.tpl');
                return json_encode([
                    "success" => true,
                    "html" => $html,
                    "bookIds" => array_map(function($book) {
                        return $book->reservedBookId;
                    }, $searchedBooks),
                ]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        case Command::JsonReserve->value:
            try {
                $userId = $oml->reserve($params["book_id"]);
                return json_encode(["success" => true, "message" => __get_success_tag(substr($userId, -2))]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        case Command::JsonBookReserveInfo->value:
            try {
                $info = $oml->getBookReserveInfo($params["bookId"]);
                return json_encode([
                    "success" => true,
                    "reserves" => $info["reserves"],
                    "waitWeeks" => $info["waitWeeks"],
                ]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        case Command::JsonReserveAgain->value:
            try {
                $userId = $oml->reserveAgain($params["user_id"], $params["book_id"]);
                return json_encode(["success" => true, "message" => __get_success_tag(substr($userId, -2))]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        case Command::JsonCancelReservation->value:
            try {
                $oml->cancelReservation($params["user_id"], $params["book_id"]);
                return json_encode(["success" => true, "message" => __get_success_tag()]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        case Command::JsonExtend->value:
            try {
                $oml->extend($params["user_id"], $params["book_id"]);
                return json_encode(["success" => true, "message" => __get_success_tag()]);
            }
            catch (Exception $e) {
                return json_encode(["success" => false, "message" => $e->getMessage()]);
            }

        // case Command::JsonBookContent->value:
        //     try {
        //         $crawler = new Crawler("", "");     // TODO: 検索時（ログインしないとき）のuseridの渡し方改善
        //         $info = $crawler->getBookContent($params["bookId"]);
        //         return json_encode([
        //             "success" => true,
        //             "content" => $info["content"],
        //         ]);
        //     }
        //     catch (Exception $e) {
        //         return json_encode([
        //             "success" => false,
        //             "message" => $e->getMessage(),
        //         ]);
        //     }
    }
    throw new Exception("Unknown command: " . $cmd);
}

function __update_all_reserved(bool $isLocal, Oml $oml, Logger $logger, bool $isParallel): void
{
    __update_books(BookType::Reserved, $isLocal, $oml, $logger, $isParallel);
}

function __update_all_lending(bool $isLocal, Oml $oml, Logger $logger, bool $isParallel): void
{
    __update_books(BookType::Lending, $isLocal, $oml, $logger, $isParallel);
}

function __update_books(BookType $bookType, bool $isLocal, Oml $oml, Logger $logger, bool $isParallel): void
{
    if ($isParallel) {
        $generator = function () use ($bookType, $oml, $isLocal, $logger) {
            foreach ($oml->getUserIds() as $userId) {
                $command = ($bookType === BookType::Reserved ? Command::UpdateAccountReserved->value : Command::UpdateAccountLending->value);
                $url = Utils::getBaseUrl($isLocal, APP_NAME) . "?cmd={$command}&account={$userId}";
                $logger->log("updating {$bookType->value} books of {$userId}: {$url}");
                yield new Request('GET', $url);
            }
        };
        $result = [];
        $pool = new Pool(new Client(), $generator(), [
            'concurrency' => count($oml->getUserIds()),
            'fulfilled' => function (Response $response, int $index) use ($logger, &$result) {
                $logger->log("Result [{$index}] fulfilled: [" . $response->getStatusCode() . "]");
                $result[] = $response->getStatusCode();
            },
            'rejected' => function (Exception $e, int $index) use ($logger) {
                $logger->log("Result [{$index}] rejected: " . $e->getMessage());
            },
        ]);
        $pool->promise()->wait();
        if (count($result) < count($oml->getUserIds())) {
            throw new Exception("Could not get all result. Result count: " . count($result));
        }
    }
    else {
        foreach ($oml->getUserIds() as $userId) {
            if ($bookType === BookType::Reserved) {
                $oml->updateReservedBooks($userId);
            }
            else {
                $oml->updateLendingBooks($userId);
            }
        }
    }

    if ($bookType === BookType::Reserved) {
        $oml->updateReservedBooksUpdatedDate();
    }
    else {
        $oml->updateLendingBooksUpdatedDate();
    }
}

function __redirect_to_reserved_list(bool $isLocal): void
{
    __redirect(Utils::getBasePath($isLocal, APP_NAME) .  "?cmd=" . Command::ListReserved->value);
}

function __redirect_to_lending_list(bool $isLocal): void
{
    __redirect(Utils::getBasePath($isLocal, APP_NAME) .  "?cmd=" . Command::ListLending->value);
}

function __redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function __json_response(int $response_code): string
{
    return json_encode([
        "response_code" => $response_code,
    ]);
}

function __get_success_tag(string $info=""): string
{
    return "<div class='alert alert-success' role='alert'><i class='bi bi-check-circle-fill'></i> OK {$info}</div>";
}

FunctionsFramework::cloudEvent('update', 'update');
function update(CloudEventInterface $event): void
{
    $logger = new Logger(APP_NAME);

    $isLocal = Utils::isLocalEvent($event);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $oml = new Oml($isLocal);
    $alerter = new Alerter($isLocal, "oml");

    __update_all_reserved($isLocal, $oml, $logger, $isParallel=false);
    __update_all_lending($isLocal, $oml, $logger, $isParallel=false);

    $reserved_books = [];
    $lending_books = [];
    foreach ($oml->getUserIds() as $userId) {
        array_push($reserved_books, ...$oml->getReservedBooks($userId));
        array_push($lending_books, ...$oml->getLendingBooks($userId));
    }

    // 期限到来貸出本の自動延長
    foreach ($lending_books as $lendingBook) {
        if ($lendingBook->isReturndateCame() && $lendingBook->isExtendable()) {
            $oml->extend($lendingBook->owner, $lendingBook->lendingBookId);
            $alerter->addAlert(AlertType::AutoExtended, $lendingBook->title);
        }
    }

    // アラートチェック
    $alerter->checkAll($reserved_books, $lending_books);
    if (count($alerter->getMessages()) > 0) {
        $alerter->sendAlerts();
    }

    $logger->log("Succeeded.");
}
