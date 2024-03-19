<?php declare(strict_types=1);

namespace MyApp\common;

// require __DIR__ . '/vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;
use MyApp\common\Logger;

final class GmailWrapper
{
    /**
     * Returns an authorized API client.
     * @return Client the authorized client object
     */
    public static function getClient()
    {
        $logger = new Logger("GoogleAPI");

        $SCOPES = [
            Gmail::MAIL_GOOGLE_COM,
            Gmail::GMAIL_MODIFY,
            Gmail::GMAIL_READONLY,
        ];
        $client = new Client();
        $client->setApplicationName('MyCFApp');
        $client->setScopes($SCOPES);
        $client->setAuthConfig(dirname(__FILE__) . '/configs/googleapi_clientsecret.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        // $client->setRedirectUri('https://www.google.com/');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        // https://sqripts.com/2022/08/25/20386/
        $tokenPath = dirname(__FILE__) . '/configs/googleapi_token.json';
        if (file_exists($tokenPath)) {
            $logger->log("token file exists");
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            $logger->log("AccessToken expired");
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $logger->log("fetching Access token with refresh token");
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // ★THIS DOESNT WORK. use hello_gmail/create_refresh_token instead
                throw new \Exception("THIS DOESN'T WORK");
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}