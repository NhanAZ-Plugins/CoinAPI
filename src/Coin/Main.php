<?php

declare(strict_types=1);

namespace Coin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use RuntimeException;

final class Main extends PluginBase implements Listener{
	private const PREFIX = "[CoinAPI] ";

	private Config $coin;
	private CoinLedger $ledger;

	protected function onEnable() : void{
		$dataFolder = $this->getDataFolder();
		if(!is_dir($dataFolder) && !mkdir($dataFolder, 0777, true) && !is_dir($dataFolder)){
			throw new RuntimeException("Could not create the CoinAPI data directory.");
		}

		$this->coin = new Config($dataFolder . "Coin.yml", Config::YAML, []);
		$balances = [];
		foreach($this->coin->getAll() as $name => $balance){
			$normalizedName = $this->normalizeBalanceKey($name);
			if($normalizedName !== null){
				$balances[$normalizedName] = $balance;
			}
		}
		$this->ledger = new CoinLedger($balances);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		if($this->ledger->ensureAccount($event->getPlayer()->getName())){
			$this->saveLedger();
		}
	}

	/**
	 * @param list<string> $args
	 */
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch(strtolower($command->getName())){
			case "mycoin":
				if(!($sender instanceof Player)){
					$sender->sendMessage(self::PREFIX . "This command can only be used in-game.");
					return true;
				}
				$sender->sendMessage(self::PREFIX . "Your coin balance: " . $this->ledger->getBalance($sender->getName()));
				return true;

			case "seecoin":
				if(!isset($args[0])){
					$sender->sendMessage(self::PREFIX . "Usage: /seecoin <player>");
					return true;
				}
				$player = $this->getServer()->getPlayerExact($args[0]);
				if($player === null){
					$sender->sendMessage(self::PREFIX . "Player not found.");
					return true;
				}
				$sender->sendMessage(self::PREFIX . $player->getName() . "'s coin balance: " . $this->ledger->getBalance($player->getName()));
				return true;

			case "paycoin":
				if(!isset($args[0], $args[1])){
					$sender->sendMessage(self::PREFIX . "Usage: /paycoin <player> <amount>");
					return true;
				}
				if(!($sender instanceof Player)){
					$sender->sendMessage(self::PREFIX . "This command can only be used in-game.");
					return true;
				}
				$player = $this->getServer()->getPlayerExact($args[0]);
				$amount = $this->parseAmount($args[1], false);
				if($player === null){
					$sender->sendMessage(self::PREFIX . "Player not found.");
					return true;
				}
				if($amount === null){
					$sender->sendMessage(self::PREFIX . "Invalid coin amount.");
					return true;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage(self::PREFIX . "You cannot transfer coins to yourself.");
					return true;
				}
				if(!$this->ledger->transfer($sender->getName(), $player->getName(), $amount)){
					$sender->sendMessage(self::PREFIX . "You do not have enough coins.");
					return true;
				}
				$this->saveLedger();
				$sender->sendMessage(self::PREFIX . "Transferred " . $amount . " coins to " . $player->getName() . ".");
				$player->sendMessage(self::PREFIX . "You received " . $amount . " coins from " . $sender->getName() . ".");
				return true;

			case "topcoin":
				$page = $this->parsePage($args[0] ?? "1");
				if($page === null){
					$sender->sendMessage(self::PREFIX . "Usage: /topcoin <page>");
					return true;
				}
				$balances = $this->ledger->getRankedBalances();
				$maxPages = max(1, (int) ceil(count($balances) / 5));
				$page = min($page, $maxPages);
				$sender->sendMessage(self::PREFIX . "Coin leaderboard (page " . $page . "/" . $maxPages . "):");
				$offset = ($page - 1) * 5;
				$rank = $offset + 1;
				foreach(array_slice($balances, $offset, 5, true) as $name => $balance){
					$sender->sendMessage(self::PREFIX . "#" . $rank . " " . $name . " - " . $balance . " coins");
					++$rank;
				}
				return true;

			case "setcoin":
			case "givecoin":
			case "takecoin":
				return $this->handleAdminCommand($sender, strtolower($command->getName()), $args);

			case "helpcoin":
				$sender->sendMessage(self::PREFIX . "Commands:");
				$sender->sendMessage(self::PREFIX . "/mycoin - View your coin balance");
				$sender->sendMessage(self::PREFIX . "/topcoin <page> - View the coin leaderboard");
				$sender->sendMessage(self::PREFIX . "/seecoin <player> - View another player's balance");
				$sender->sendMessage(self::PREFIX . "/setcoin <player> <amount> - Set a player's balance");
				$sender->sendMessage(self::PREFIX . "/givecoin <player> <amount> - Add coins to a player");
				$sender->sendMessage(self::PREFIX . "/takecoin <player> <amount> - Remove coins from a player");
				$sender->sendMessage(self::PREFIX . "/paycoin <player> <amount> - Transfer coins to a player");
				return true;
		}
		return false;
	}

	/**
	 * @param list<string> $args
	 */
	private function handleAdminCommand(CommandSender $sender, string $name, array $args) : bool{
		$permission = $name . ".cmd";
		if(!$sender->hasPermission($permission)){
			$sender->sendMessage(self::PREFIX . "You do not have permission to use this command.");
			return true;
		}
		if(!isset($args[0], $args[1])){
			$sender->sendMessage(self::PREFIX . "Usage: /" . $name . " <player> <amount>");
			return true;
		}

		$player = $this->getServer()->getPlayerExact($args[0]);
		if($player === null){
			$sender->sendMessage(self::PREFIX . "Player not found.");
			return true;
		}
		$amount = $this->parseAmount($args[1], $name === "setcoin");
		if($amount === null){
			$sender->sendMessage(self::PREFIX . "Invalid coin amount.");
			return true;
		}

		$targetName = $player->getName();
		$updated = match($name){
			"setcoin" => $this->ledger->setBalance($targetName, $amount),
			"givecoin" => $this->ledger->add($targetName, $amount),
			"takecoin" => $this->ledger->reduce($targetName, $amount),
			default => false
		};
		if(!$updated){
			$sender->sendMessage(self::PREFIX . "Could not update the coin balance.");
			return true;
		}

		$this->saveLedger();
		$sender->sendMessage(self::PREFIX . $targetName . "'s balance is now " . $this->ledger->getBalance($targetName) . " coins.");
		$player->sendMessage(self::PREFIX . "Your balance was set to " . $this->ledger->getBalance($targetName) . " coins by " . $sender->getName() . ".");
		return true;
	}

	public function addCoin(Player|string $player, int|float $coin) : bool{
		$name = $player instanceof Player ? $player->getName() : $player;
		if(!$this->ledger->add($name, $coin)){
			return false;
		}
		$this->saveLedger();
		return true;
	}

	public function reduceCoin(Player|string $player, int|float $coin) : bool{
		$name = $player instanceof Player ? $player->getName() : $player;
		if(!$this->ledger->reduce($name, $coin)){
			return false;
		}
		$this->saveLedger();
		return true;
	}

	public function myCoin(Player|string $player) : int|float{
		$name = $player instanceof Player ? $player->getName() : $player;
		return $this->ledger->getBalance($name);
	}

	private function saveLedger() : void{
		$this->coin->setAll($this->ledger->getAll());
		$this->coin->save();
	}

	private function normalizeBalanceKey(mixed $key) : ?string{
		return is_int($key) || is_string($key) ? (string) $key : null;
	}

	private function parseAmount(string $value, bool $allowZero) : int|float|null{
		if(!is_numeric($value)){
			return null;
		}
		$amount = (float) $value;
		if(!is_finite($amount) || $amount < 0 || (!$allowZero && $amount === 0.0)){
			return null;
		}
		return floor($amount) === $amount ? (int) $amount : $amount;
	}

	private function parsePage(string $value) : ?int{
		if(!ctype_digit($value)){
			return null;
		}
		$page = (int) $value;
		return $page > 0 ? $page : null;
	}
}
