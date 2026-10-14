<?php

declare(strict_types=1);

namespace CoinTests;

use Coin\CoinLedger;

require_once __DIR__ . "/../src/Coin/CoinLedger.php";

function expect(bool $condition, string $message) : void{
	if(!$condition){
		throw new \RuntimeException($message);
	}
}

$ledger = new CoinLedger([
	"Legacy" => 25,
	"Decimal" => 1.5,
	"Corrupt" => "not-a-number"
]);
expect($ledger->getBalance("Legacy") === 25, "Legacy integer balances must remain readable.");
expect($ledger->getBalance("Decimal") === 1.5, "Numeric decimal balances must remain readable.");
expect($ledger->getBalance("Corrupt") === 0, "Invalid balances must not be treated as currency.");
expect($ledger->ensureAccount("NewPlayer"), "A new player should receive an account.");
expect(!$ledger->ensureAccount("NewPlayer"), "An existing account should not be reset.");
expect(!$ledger->transfer("Legacy", "Recipient", 26), "Transfers above the balance must fail.");
expect($ledger->getBalance("Legacy") === 25 && $ledger->getBalance("Recipient") === 0, "A failed transfer must preserve both balances.");
expect($ledger->transfer("Legacy", "Recipient", 10), "A valid transfer should succeed.");
expect($ledger->getBalance("Legacy") === 15 && $ledger->getBalance("Recipient") === 10, "A transfer must debit and credit exactly once.");
expect(!$ledger->transfer("Legacy", "Legacy", 1), "Self transfers must not mutate balances.");
expect(!$ledger->reduce("Legacy", 16), "A withdrawal above the balance must fail.");
expect($ledger->add("Recipient", 0.5), "Positive decimal credits should succeed.");
expect($ledger->getBalance("Recipient") === 10.5, "Decimal credits must be retained.");
expect(!$ledger->setBalance("Legacy", -1), "Negative balances must be rejected.");
expect(array_key_exists("Corrupt", $ledger->getAll()), "Unknown legacy entries must be preserved on save.");
expect(!array_key_exists("Corrupt", $ledger->getRankedBalances()), "Corrupt balances must be omitted from ranking.");

fwrite(STDOUT, "CoinAPI ledger checks passed.\n");
