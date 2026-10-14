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
					$sender->sendMessage("§e⊹⊱§cLệnh này chỉ dùng trong trò chơi.§e⊰⊹");
					return true;
				}
				$sender->sendMessage("§e⊹⊱§aSố Coin Của Bạn Là §b" . $this->ledger->getBalance($sender->getName()) . "§a Coin§e⊰⊹");
				return true;

			case "seecoin":
				if(!isset($args[0])){
					$sender->sendMessage("§e⊹⊱§aSử Dụng §b/seecoin <Tên Người Chơi>§e⊰⊹");
					return true;
				}
				$player = $this->getServer()->getPlayerExact($args[0]);
				if($player === null){
					$sender->sendMessage("§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹");
					return true;
				}
				$sender->sendMessage("§e⊹⊱§aSố Coin Của Người Chơi§b " . $player->getName() . " §aLà §b" . $this->ledger->getBalance($player->getName()) . "§a Coin§e⊰⊹");
				return true;

			case "paycoin":
				if(!isset($args[0], $args[1])){
					$sender->sendMessage("§e⊹⊱§aSử Dụng§b /paycoin <Tên Người Chơi> <Số Lượng>§e⊰⊹");
					return true;
				}
				if(!($sender instanceof Player)){
					$sender->sendMessage("§e⊹⊱§cLệnh này chỉ dùng trong trò chơi.§e⊰⊹");
					return true;
				}
				$player = $this->getServer()->getPlayerExact($args[0]);
				$amount = $this->parseAmount($args[1], false);
				if($player === null){
					$sender->sendMessage("§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹");
					return true;
				}
				if($amount === null){
					$sender->sendMessage("§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹");
					return true;
				}
				if($sender->getName() === $player->getName()){
					$sender->sendMessage("§e⊹⊱§cBạn Không Thể Chuyển Coin Cho Chính Mình.§e⊰⊹");
					return true;
				}
				if(!$this->ledger->transfer($sender->getName(), $player->getName(), $amount)){
					$sender->sendMessage("§e⊹⊱§cBạn Không Đủ Coin Để Chuyển.§e⊰⊹");
					return true;
				}
				$this->saveLedger();
				$sender->sendMessage("§e⊹⊱§aĐã Chuyển §b" . $amount . "§a Coin Cho Người Chơi §b" . $player->getName() . "§e⊰⊹");
				$player->sendMessage("§e⊹⊱§aBạn Đã Nhận Được §b" . $amount . "§a Coin Từ Người Chơi §b" . $sender->getName() . "§e⊰⊹");
				return true;

			case "topcoin":
				$page = $this->parsePage($args[0] ?? "1");
				if($page === null){
					$sender->sendMessage("§e⊹⊱§aSử Dụng §b/topcoin <Trang>§e⊰⊹");
					return true;
				}
				$balances = $this->ledger->getRankedBalances();
				$maxPages = max(1, (int) ceil(count($balances) / 5));
				$page = min($page, $maxPages);
				$sender->sendMessage("§e⊹⊱§aBảng Xếp Hạng Coin Của Máy Chủ §f<§aTrang §b" . $page . "§f/§b" . $maxPages . "§f>§e⊰⊹");
				$offset = ($page - 1) * 5;
				$rank = $offset + 1;
				foreach(array_slice($balances, $offset, 5, true) as $name => $balance){
					$sender->sendMessage("§e⊹⊱§cXếp Hạng §e" . $rank . " §f|§b " . $name . " §f|§a " . $balance . " Coin§e⊰⊹");
					++$rank;
				}
				return true;

			case "setcoin":
			case "givecoin":
			case "takecoin":
				return $this->handleAdminCommand($sender, strtolower($command->getName()), $args);

			case "helpcoin":
				$sender->sendMessage("§e⊹⊱§aHướng Dẫn Sử Dụng Coin§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/mycoin§f -§a Hiển Thị Số Coin Của Bản Thân§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/topcoin <Trang> §f-§a Hiển Thị Bảng Xếp Hạng Coin§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/seecoin <Tên Người Chơi> §f-§a Hiển Thị Số Coin Của Người Chơi§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/setcoin <Tên Người Chơi> <Số Lượng> §f-§a Chỉnh Số Coin Người Chơi§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/givecoin <Tên Người Chơi> <Số Lượng> §f-§a Tăng Số Coin Người Chơi§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/takecoin <Tên Người Chơi> <Số Lượng> §f-§a Giảm Số Coin Người Chơi§e⊰⊹");
				$sender->sendMessage("§e⊹⊱§b/paycoin <Tên Người Chơi> <Số Lượng> §f-§a Chuyển Số Coin Cho Người Chơi§e⊰⊹");
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
			$sender->sendMessage("§e⊹⊱§cBạn Không Có Quyền Sử Dụng Câu Lệnh Này§e⊰⊹");
			return true;
		}
		if(!isset($args[0], $args[1])){
			$sender->sendMessage("§e⊹⊱§aSử Dụng §b/" . $name . " <Tên Người Chơi> <Số Lượng>§e⊰⊹");
			return true;
		}

		$player = $this->getServer()->getPlayerExact($args[0]);
		if($player === null){
			$sender->sendMessage("§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹");
			return true;
		}
		$amount = $this->parseAmount($args[1], $name === "setcoin");
		if($amount === null){
			$sender->sendMessage("§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹");
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
			$sender->sendMessage("§e⊹⊱§cKhông Thể Cập Nhật Số Coin.§e⊰⊹");
			return true;
		}

		$this->saveLedger();
		$sender->sendMessage("§e⊹⊱§aSố Coin Của Người Chơi§b " . $targetName . " §aĐã Được Cập Nhật Thành §b" . $this->ledger->getBalance($targetName) . "§a Coin§e⊰⊹");
		$player->sendMessage("§e⊹⊱§aSố Coin Của Bạn Đã Được Quản Trị Viên " . $sender->getName() . " §aCập Nhật Thành §b" . $this->ledger->getBalance($targetName) . "§a Coin§e⊰⊹");
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
