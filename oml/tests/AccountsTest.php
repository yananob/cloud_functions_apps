<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MyApp\common\Utils;
use MyApp\Accounts;

final class AccountsTest extends TestCase
{
    private Accounts $accounts;
    private array $testAccounts;

    protected function setUp(): void
    {
        $this->accounts = new Accounts($is_test=true);
        $testAccounts = Utils::getConfig(__DIR__ . "/config/accounts.json")["test_users"];
        $this->testAccounts = [];
        foreach ($testAccounts as $testAccount) {
            $this->testAccounts[$testAccount["userid"]] = $testAccount;
        }
    }

    public function testList(): void
    {
        $accounts = $this->accounts->list();

        $accountsWoPassword = [];
        foreach ($accounts as $userId => $account) {
            // unset($account["password"]);
            $accountsWoPassword[$userId] = $account;
        }

        $this->assertEquals($this->testAccounts, $accountsWoPassword);
    }
}
