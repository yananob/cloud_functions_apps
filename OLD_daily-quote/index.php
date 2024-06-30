<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

Google\CloudFunctions\FunctionsFramework::cloudEvent('main', 'main');
function main(CloudEvents\V1\CloudEventInterface $event): void
{
    $logger = new yananob\mytools\Logger("daily-quote");

    $quotes = new MyApp\Quotes();
    $quote = $quotes->getRandom();

    // No.${index}
    $message = <<<EOF
Quote of the day:
No. {$quote["no"]}

{$quote["message"]}

[{$quote["author"]}] {$quote["source"]} {$quote["source_link"]}
EOF;
    $line = new yananob\mytools\Line(__DIR__ . '/configs/line.json');
    $line->sendMessage("stnb", $message);

    $logger->log("Succeeded.");
}

Google\CloudFunctions\FunctionsFramework::http('editor', 'editor');
function editor(Psr\Http\Message\ServerRequestInterface $request): string
{
    $appName = "daily-quote-editor";

    $logger = new yananob\mytools\Logger($appName);
    $query_params = $request->getQueryParams();
    $logger->log("params: " . json_encode($query_params));

    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . "/templates");

    $isLocal = yananob\my_gcptools\GcpUtils::isLocalHttp($request);
    $quotes = $isLocal ? new MyApp\Quotes("daily-quotes-test", 5) : new MyApp\Quotes();

    session_start([
        "cookie_lifetime" => 60 * 60 * 2,   // 2h
    ]);

    if (isset($query_params["cmd"]) && $query_params["cmd"] === "login") {
        $body = $request->getParsedBody();
        try {
            __login($quotes->getPassword(), $body);
            __redirect_to_list($appName, $isLocal);
        } catch (MyApp\UnauthorizedException $e) {
            $smarty->assign("message", $e->getMessage());
            return $smarty->fetch('login.tpl');
        }
    }
    try {
        __check_login();
    } catch (MyApp\UnauthorizedException $e) {
        $smarty->assign("message", $e->getMessage());
        return $smarty->fetch('login.tpl');
    }

    if (isset($query_params["cmd"])) {
        $cmd = $query_params["cmd"];
    } else {
        $cmd = "view";
    }
    $doc_no = isset($query_params["doc_no"]) ? intval($query_params["doc_no"]) : null;
    switch ($cmd) {
        case "add":
            $smarty->assign("doc_no", "");
            $smarty->assign("quote", $quotes->blank());
            return $smarty->fetch('form.tpl');

        case "edit":
            $quote = $quotes->get($doc_no);
            $smarty->assign("doc_no", $doc_no);
            $smarty->assign("quote", $quote);
            return $smarty->fetch('form.tpl');

        case "remove":
            $quotes->remove($doc_no);
            __redirect_to_list($appName, $isLocal);
            break;  // dummy

        case "save":
            if ($doc_no !== null) {
                // save from edit
                $body = $request->getParsedBody();
                $quotes->update($doc_no, $body);
            }
            else {
                // save from add
                $body = $request->getParsedBody();
                $quotes->add($body);
            }
            __redirect_to_list($appName, $isLocal);
            break;  // dummy

        case "view":
            $max_page = $quotes->maxPage();
            $page = 1;  // default
            if (isset($query_params["page"])) {
                $page = IntVal($query_params["page"]);
                if ($page < 1) {
                    $page = 1;
                }
                elseif ($page > $max_page) {
                    $page = $max_page;
                }
            }

            $quote_list = $quotes->list($page);
            $smarty->assign("quotes", $quote_list);
            $smarty->assign("page", $page);
            $smarty->assign("max_page", $max_page);
            return $smarty->fetch('list.tpl');
    }
    throw new Exception("Unknown command: " . $cmd);
}

function __login(string $password, array $form_body): void
{
    if ($form_body['password'] !== $password) {
        unset($_SESSION["authorized"]);
        throw new MyApp\UnauthorizedException("パスワードが違います。");
    }

    $_SESSION["authorized"] = true;
}

function __check_login(): void
{
    if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] !== true) {
        throw new MyApp\UnauthorizedException("パスワードを入力してログインしてください。");
    }
}

function __redirect_to_list(string $appName, bool $isLocal): void
{
    header("Location: " . \yananob\mytools\Utils::getBaseUrl($isLocal, $appName));
    exit;
}
