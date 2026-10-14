<?php

namespace Coin;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {
    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->coin = new Config($this->getDataFolder() . 'Coin.yml', Config::YAML);
    }
    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        if (!$this->coin->exists($player->getName())) {
            $this->coin->set($player->getName(), 0);
            $this->coin->save;
        }
        return true;
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        switch ($cmd->getName()) {
            case 'mycoin':
                $sender->sendMessage('§e⊹⊱§aSố Coin Của Bạn Là §b' . $this->coin->get($sender->getName()) . '§a Coin§e⊰⊹');
                break;
            case 'seecoin':
                if (!isset($args[0])) {
                    $sender->sendMessage('§e⊹⊱§aSử Dụng §b/seecoin <Tên Người Chơi>§e⊰⊹');
                    return true;
                }
                $player = $this->getServer()->getPlayer($args[0]);
                if (!$player) {
                    $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                    return true;
                }
                $sender->sendMessage('§e⊹⊱§aSố Coin Của Người Chơi§b ' . $player->getName() . ' §aLà §b' . $this->coin->get($player->getName()) . '§a Coin§e⊰⊹');/*$player->sendMessage("§e⊹⊱§aNgười Chơi §b".$sender->getName()."§a Đang Xem Số Coin Của Bạn§e⊰⊹");*/
                break;
            case 'paycoin':
                if (!isset($args[0])) {
                    $sender->sendMessage('§e⊹⊱§aSử Dụng§b /paycoin <Tên Người Chơi> <Số Lượng>§e⊰⊹');
                    return true;
                }
                $player = $this->getServer()->getPlayer($args[0]);
                if (!$player) {
                    $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                    return true;
                }
                if ($args[1] < 1 || !is_numeric($args[1])) {
                    $sender->sendMessage('§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹');
                    return true;
                }
                if ($this->coin->get($sender->getName()) >= $args[1]) {
                    $name = $player->getName();
                    $this->coin->set($name, $this->coin->get($name) + $args[1]);
                    $this->coin->set($sender->getName(), $this->coin->get($sender->getName()) - $args[1]);
                    $this->coin->save();
                    $sender->sendMessage('§e⊹⊱§aĐã Chuyển §b' . $args[1] . '§a Coin Cho Người Chơi §b' . $player->getName() . '§e⊰⊹');
                    $player->sendMessage('§e⊹⊱§aBạn Đã Nhận Được §b' . $args[1] . '§a Coin Từ Người Chơi §b' . $sender->getName() . '§e⊰⊹');
                } else {
                    $sender->sendMessage('§e⊹⊱§cBạn Không Đủ §b' . $args[1] . '§c Coin Để Chuyển Cho Người Chơi §b' . $player->getName() . '§e⊰⊹');
                    break;
                }
                break;
            case 'topcoin':
                $max = 0;
                foreach ($this->coin->getAll() as $c) {
                    $max += count($c);
                }
                $max = ceil(($max / 5));
                $page = array_shift($args);
                $page = max(1, $page);
                $page = min($max, $page);
                $page = (int)$page;
                $sender->sendMessage('§e⊹⊱§aBảng Xếp Hạng Coin Của Máy Chủ §f<§aTrang §b' . $page . '§f/§b' . $max . '§f>§e⊰⊹');
                $aa = $this->coin->getAll();
                arsort($aa);
                $i = 1;
                foreach ($aa as $b => $a) {
                    if (($page - 1) * 5 <= $i && $i <= ($page - 1) * 5 + 4) {
                        $i1 = $i + 1;
                        $c = $this->coin->get($b);
                        $sender->sendMessage('§e⊹⊱§cXếp Hạng §e' . $i . ' §f|§b ' . $b . ' §f|§a ' . $c . ' Coin§e⊰⊹');
                    }
                    $i++;
                }
                break;
            case 'setcoin':
                if ($sender->isOp() || $sender->getName() == 'Ghast_Noob' || $sender->hasPermission('setcoin.cmd')) {
                    if (!isset($args[0])) {
                        $sender->sendMessage('§e⊹⊱§aSử Dụng: §b/setcoin <Tên Người Chơi> <Số Lượng>§e⊰⊹');
                        return true;
                    }
                    $player = $this->getServer()->getPlayer($args[0]);
                    if (!$player) {
                        $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                        return true;
                    }
                    if ($args[1] < 0 || !is_numeric($args[1])) {
                        $sender->sendMessage('§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹');
                        return true;
                    }
                    $this->coin->set($player->getName(), $args[1]);
                    $this->coin->save();
                    $sender->sendMessage('§e⊹⊱§aSố Coin Của Người Chơi§b ' . $player->getName() . '§a Đã Được Chỉnh Thành§b ' . $args[1] . '§a Coin§e⊰⊹');
                    $player->sendMessage('§e⊹⊱§aQuản Trị Viên§b ' . $sender->getName() . '§a Đã Chỉnh Số Coin Của Bạn Thành§b ' . $args[1] . '§a Coin§e⊰⊹');
                } else {
                    $sender->sendMessage('§e⊹⊱§cBạn Không Có Quyền Sử Dụng Câu Lệnh Này§e⊰⊹');
                    break;
                }
                break;
            case 'givecoin':
                if ($sender->isOp() || $sender->getName() == 'Ghast_Noob' || $sender->hasPermission('givecoin.cmd')) {
                    if (!isset($args[0])) {
                        $sender->sendMessage('§e⊹⊱§aSử Dụng §b/givecoin <Tên Người Chơi> <Số Lượng>§e⊰⊹');
                        return true;
                    }
                    $player = $this->getServer()->getPlayer($args[0]);
                    if (!$player) {
                        $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                        return true;
                    }
                    if ($args[1] < 1 || !is_numeric($args[1])) {
                        $sender->sendMessage('§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹');
                        return true;
                    }
                    $this->coin->set($player->getName(), $this->coin->get($player->getName()) + $args[1]);
                    $this->coin->save();
                    $sender->sendMessage('§e⊹⊱§aSố Coin Của Người Chơi§b ' . $player->getName() . '§a Đã Được Tăng Thêm§b ' . $args[1] . '§a Coin§e⊰⊹');
                    $player->sendMessage('§e⊹⊱§aSố Coin Của Bạn Đã Được Tăng Thêm§b ' . $args[1] . '§a Coin Bởi Quản Trị Viên§b ' . $sender->getName() . '§e⊰⊹');
                } else {
                    $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                    break;
                }
                break;
            case 'takecoin':
                if ($sender->isOp() || $sender->getName() == 'Ghast_Noob' || $sender->hasPermission('takecoin.cmd')) {
                    if (!isset($args[0])) {
                        $sender->sendMessage('§e⊹⊱§aSử Dụng §b/takecoin <Tên Người Chơi> <Số Lượng>§e⊰⊹');
                        return true;
                    }
                    $player = $this->getServer()->getPlayer($args[0]);
                    if (!$player) {
                        $sender->sendMessage('§e⊹⊱§cKhông Tìm Thấy Người Chơi§e⊰⊹');
                        return true;
                    }
                    if ($args[1] < 1 || !is_numeric($args[1])) {
                        $sender->sendMessage('§e⊹⊱§cSố Lượng Coin Không Hợp Lệ§e⊰⊹');
                        return true;
                    }
                    $this->coin->set($player->getName(), $this->coin->get($player->getName()) - $args[1]);
                    $this->coin->save();
                    $sender->sendMessage('§e⊹⊱§aSố Coin Của Người Chơi§b ' . $player->getName() . '§a Đã Giảm Xuống§b ' . $args[1] . '§a Coin§e⊰⊹');
                    $player->sendMessage('§e⊹⊱§aSố Coin Của Bạn Đã Bị Giảm Xuống§b ' . $args[1] . '§a Coin Bởi Quản Trị Viên§b ' . $sender->getName() . '§e⊰⊹');
                    return true;
                } else {
                    $sender->sendMessage('§e⊹⊱§cBạn Không Có Quyền Sử Dụng Câu Lệnh Này§e⊰⊹');
                    break;
                }
            case 'helpcoin':
                $sender->sendMessage('§e⊹⊱§aHướng Dẫn Sử Dụng Coin§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/mycoin§f -§a Hiển Thị Số Coin Của Bản Thân§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/topcoin <Trang> §f-§a Hiển Thị Bảng Xếp Hạng Coin§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/seecoin <Tên Người Chơi> §f-§a Hiển Thị Số Coin Của Người Chơi§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/setcoin <Tên Người Chơi> <Số Lượng> §f-§a Chỉnh Số Coin Người Chơi§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/givecoin <Tên Người Chơi> <Số Lượng> §f-§a Tăng Số Coin Người Chơi§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/takecoin <Tên Người Chơi> <Số Lượng> §f-§a Giảm Số Coin Người Chơi§e⊰⊹');
                $sender->sendMessage('§e⊹⊱§b/paycoin <Tên Người Chơi> <Số Lượng> §f-§a Chuyển Số Coin Cho Người Chơi§e⊰⊹');
                break;
        }
    }
    public function addCoin($player, $coin) {
        if ($player instanceof Player) {
            $player = $player->getName();
        }
        $this->coin = new Config($this->getDataFolder() . 'Coin.yml', Config::YAML);
        $this->coin->set($player, (int)$this->coin->get($player) + $coin);
        $this->coin->save();
    }
    public function reduceCoin($player, $coin) {
        if ($player instanceof Player) {
            $player = $player->getName();
        }
        if ($this->myCoin($player) - $coin < 0) {
            return true;
        }
        $this->coin = new Config($this->getDataFolder() . 'Coin.yml', Config::YAML);
        $this->coin->set($player, (int)$this->coin->get($player) - $coin);
        $this->coin->save();
    }
    public function myCoin($player) {
        if ($player instanceof Player) {
            $player = $player->getName();
        }
        $coin = $this->coin->get($player);
        return $coin;
    }
}
