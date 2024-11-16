<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use yananob\mytools\Logger;
use yananob\mytools\Utils;
use yananob\mytools\Line;
use yananob\my_gcptools\CFUtils;

FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): string
{
    $logger = new Logger("line-send");
    $query = $request->getQueryParams();
    $body = $request->getParsedBody();
    $logger->log(str_repeat("-", 120));
    $logger->log("Query: " . json_encode($query));
    $logger->log("Body: " . json_encode($body));

    // $smarty = new Smarty();
    // $smarty->setTemplateDir(__DIR__ . "/templates");

    $isLocal = CFUtils::isLocalHttp($request);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    //     qs = urllib.parse.parse_qs(body)
    //     logging.info("qs: {}".format(qs))
    //     target = qs["target"][0] if "target" in qs else ""
    //     message = qs["message"][0] if "message" in qs else ""
    //     logging.info("target: {}, message: {}".format(target, message))

    if (empty($body)) {
        $logger->log("GET");

        return "Done";
    } else {
        $logger->log("POST");
        if (!array_key_exists("target", $body)) {
            throw new \Exception("target was not passed");
        }
        if (!array_key_exists("message", $body)) {
            throw new \Exception("message was not passed");
        }
        $target = $body["target"];
        $message = $body["message"];

        $line = new Line(__DIR__ . "/configs/line.json");
        // MEMO: UIのシンプル化のために、botとtargetが同じであることを前提にしている
        $line->sendMessage($target, $target, $message);

        return "";
    }


    // $config = Utils::getConfig(__DIR__ . "/configs/config.json");

    // if (isset($params["cmd"])) {
    //     $cmd = $params["cmd"];
    // } else {
    //     $cmd = "main";
    // }
    // switch ($cmd) {
    //     case Command::Main->value:    // TODO: ajax化（遅延で詳細取得）
    //         $books = $all_reserved_books = $all_lending_books = [];
    //         foreach ($oml->getUserIds() as $user_id) {
    //             $logger->log("processing user " . $user_id);
    //             $reserved_books = $oml->getReservedBooks($user_id);
    //             $lending_books = $oml->getLendingBooks($user_id);
    //             $count_keeping = 0;
    //             $count_overdue = 0;
    //             // TODO: omlあたりにカウントしてほしい
    //             foreach ($reserved_books as $reserved_book) {
    //                 if ($reserved_book->state === BookState::Keeping) $count_keeping++;
    //             }
    //             foreach ($lending_books as $lending_book) {
    //                 if ($lending_book->isReturndateCame()) $count_overdue++;
    //             }
    //             $books[$user_id] = [
    //                 "reserved_books" => $reserved_books,
    //                 "count_keeping" => $count_keeping,
    //                 "lending_books" => $lending_books,
    //                 "count_overdue" => $count_overdue,
    //             ];
    //             array_push($all_reserved_books, ...$reserved_books);
    //             array_push($all_lending_books, ...$lending_books);
    //         }
    //         $alerter->checkAll($all_reserved_books, $all_lending_books);
    //         $smarty->assign("books", $books);
    //         $smarty->assign("alerts", $alerter->getMessages());
    //         return $smarty->fetch('main.tpl');

    //     case Command::ListReserved->value:
    //         $books = [];
    //         foreach ($oml->getUserIds() as $userId) {
    //             foreach ($oml->getReservedBooks($userId) as $book) {
    //                 $books[] = $book;
    //             }
    //         }
    //         $alerter->checkKeepLimitdate($books);

    //         $smarty->assign("books", $books);
    //         $smarty->assign("alerts", $alerter->getMessages());
    //         return $smarty->fetch('reservedList.tpl');

    //     case Command::ListLending->value:
    //         $books = [];
    //         foreach ($oml->getUserIds() as $userId) {
    //             foreach ($oml->getLendingBooks($userId) as $book) {
    //                 $books[] = $book;
    //             }
    //         }
    //         $alerter->checkReturnLimitDate($books);

    //         $smarty->assign("books", $books);
    //         $smarty->assign("alerts", $alerter->getMessages());
    //         return $smarty->fetch('lendingList.tpl');

    //     case Command::UpdateAllReserved->value:    // TODO: ajax化
    //         __update_all_reserved($isLocal, $oml, $logger, $isParallel = !$isLocal);
    //         $messagesQueue->pushMessage("予約リストを更新しました。");
    //         __redirect(CFUtils::getBasePath($isLocal, $request) .  "?cmd=" . Command::ListReserved->value);

    //     case Command::UpdateAllLending->value:    // TODO: ajax化
    //         __update_all_lending($isLocal, $oml, $logger, $isParallel = !$isLocal);
    //         $messagesQueue->pushMessage("貸出リストを更新しました。");
    //         __redirect(CFUtils::getBasePath($isLocal, $request) .  "?cmd=" . Command::ListLending->value);

    //     case Command::UpdateAccountReserved->value:
    //         $oml->updateReservedBooks($params["account"]);
    //         return __json_response(200);

    //     case Command::UpdateAccountLending->value:
    //         $oml->updateLendingBooks($params["account"]);
    //         return __json_response(200);

    //     case Command::Reserve->value:
    //         $smarty->assign("totalReservableCount", $oml->getTotalReservableCount());
    //         $smarty->assign("upcomingAdultList", $oml->getUpcomingAdultList());
    //         $smarty->assign("upcomingChildList", $oml->getUpcomingChildList());
    //         $smarty->assign("bestList", $oml->getBestListPeriods());
    //         return $smarty->fetch('reserve.tpl');

    //     case Command::JsonSearch->value:
    //         try {
    //             $searchedBooks = $oml->search(
    //                 $params["keyword"],
    //                 $params["title"],
    //                 $params["author"],
    //                 (int)$params["page"]
    //             );
    //             $smarty->assign("books", $searchedBooks);
    //             $html = $smarty->fetch('ajax/booksList.tpl');
    //             return json_encode([
    //                 "success" => true,
    //                 "html" => $html,
    //                 "bookIds" => array_map(function ($book) {
    //                     return $book->reservedBookId;
    //                 }, $searchedBooks),
    //             ]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonShowList->value:
    //         try {
    //             $books = $oml->getList(RssType::from($params["type"]), $params["category"]);
    //             if ($isLocal) {
    //                 $books = array_slice($books, 0, 5);
    //             }

    //             $smarty->assign("books", $books);
    //             $html = $smarty->fetch('ajax/booksList.tpl');
    //             return json_encode([
    //                 "success" => true,
    //                 "html" => $html,
    //                 "bookIds" => array_map(function ($book) {
    //                     return $book->reservedBookId;
    //                 }, $books),
    //             ]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonReserve->value:
    //         try {
    //             $userId = $oml->reserve($params["book_id"]);
    //             return json_encode(["success" => true, "message" => __get_success_tag(substr($userId, -2))]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonBookReserveInfo->value:
    //         try {
    //             $info = $oml->getBookReserveInfo($params["bookId"]);
    //             return json_encode([
    //                 "success" => true,
    //                 "reserves" => $info["reserves"],
    //                 "waitWeeks" => $info["waitWeeks"],
    //             ]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonReserveAgain->value:
    //         try {
    //             $userId = $oml->reserveAgain($params["user_id"], $params["book_id"]);
    //             return json_encode(["success" => true, "message" => __get_success_tag(substr($userId, -2))]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonCancelReservation->value:
    //         try {
    //             $oml->cancelReservation($params["user_id"], $params["book_id"]);
    //             return json_encode(["success" => true, "message" => __get_success_tag()]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //     case Command::JsonExtend->value:
    //         try {
    //             $oml->extend($params["user_id"], $params["book_id"]);
    //             return json_encode(["success" => true, "message" => __get_success_tag()]);
    //         } catch (Exception $e) {
    //             return json_encode(["success" => false, "message" => $e->getMessage()]);
    //         }

    //         // case Command::JsonBookContent->value:
    //         //     try {
    //         //         $crawler = new Crawler("", "");     // TODO: 検索時（ログインしないとき）のuseridの渡し方改善
    //         //         $info = $crawler->getBookContent($params["bookId"]);
    //         //         return json_encode([
    //         //             "success" => true,
    //         //             "content" => $info["content"],
    //         //         ]);
    //         //     }
    //         //     catch (Exception $e) {
    //         //         return json_encode([
    //         //             "success" => false,
    //         //             "message" => $e->getMessage(),
    //         //         ]);
    //         //     }
    // }
    // throw new Exception("Unknown command: " . $cmd);
}
