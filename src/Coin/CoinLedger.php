<?php

declare(strict_types=1);

namespace Coin;

final class CoinLedger{

	/** @var array<string, mixed> */
	private array $balances;

	/**
	 * @param array<string, mixed> $balances
	 */
	public function __construct(array $balances){
		$this->balances = $balances;
	}

	public function getBalance(string $name) : int|float{
		$value = $this->balances[$name] ?? 0;
		if(!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))){
			return 0;
		}
		$balance = (float) $value;
		if(!is_finite($balance) || $balance < 0){
			return 0;
		}
		return is_int($value) ? $value : $balance;
	}

	public function ensureAccount(string $name) : bool{
		if(array_key_exists($name, $this->balances)){
			return false;
		}
		$this->balances[$name] = 0;
		return true;
	}

	public function setBalance(string $name, int|float $amount) : bool{
		if(!is_finite((float) $amount) || $amount < 0){
			return false;
		}
		$this->balances[$name] = $amount;
		return true;
	}

	public function add(string $name, int|float $amount) : bool{
		if(!is_finite((float) $amount) || $amount <= 0){
			return false;
		}
		$balance = $this->getBalance($name) + $amount;
		if(!is_finite((float) $balance)){
			return false;
		}
		$this->balances[$name] = $balance;
		return true;
	}

	public function reduce(string $name, int|float $amount) : bool{
		$balance = $this->getBalance($name);
		if(!is_finite((float) $amount) || $amount <= 0 || $balance < $amount){
			return false;
		}
		$this->balances[$name] = $balance - $amount;
		return true;
	}

	public function transfer(string $from, string $to, int|float $amount) : bool{
		if($from === $to || !is_finite((float) $amount) || $amount <= 0 || $this->getBalance($from) < $amount){
			return false;
		}

		$recipientBalance = $this->getBalance($to) + $amount;
		if(!is_finite((float) $recipientBalance)){
			return false;
		}

		$this->balances[$from] = $this->getBalance($from) - $amount;
		$this->balances[$to] = $recipientBalance;
		return true;
	}

	/** @return array<string, mixed> */
	public function getAll() : array{
		return $this->balances;
	}

	/** @return array<string, int|float> */
	public function getRankedBalances() : array{
		$ranked = [];
		foreach($this->balances as $name => $value){
			if(!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))){
				continue;
			}
			$balance = (float) $value;
			if(is_finite($balance) && $balance >= 0){
				$ranked[$name] = is_int($value) ? $value : $balance;
			}
		}
		arsort($ranked, SORT_NUMERIC);
		return $ranked;
	}
}
