<?php

namespace athena\sabone;

use pocketmine\command\{
	Command, CommandSender
};
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Warp extends PluginBase implements Listener{
	public $g = [];
	public $config;
	public $commands = [];
	public $portals = [];
	public $making = [];
	public $prefix = "[워프] ";

	/*
	$name:{
 		portals:{
			(Position) (Position) ...
		}
		destination:(Position)
		banned:(bool)
	}
	*/
	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "config.json", Config::JSON);
		$this->g = $this->config->getAll();
		$this->commands = array_keys($this->g);
		foreach($this->commands as $m){
			$this->getServer()->getCommandMap()->register($m, new Command($m));
		}
		foreach($this->g as $name => $value){
			array_push($this->portals, $value["portals"]);
		}

		//포탈에 예쁘게 파티클 꾸미기 ☆☆
	}

	public function onDisable(){
		$this->save();
	}

	public function onTouch(PlayerInteractEvent $ev){
		$player = $ev->getPlayer();
		if(isset($this->making[$playerName = $player->getLowerCaseName()])){
			/* $block=$ev->getBlock();
			$pos=Position::fromObject($block,$block->getLevel()).add(0,1,0); */
			$pos = Position::fromObject($ev->getTouchVector(), $player->getLevel()) . add(0, 1, 0);
			$this->addDes($this->making[$playerName], $pos);
			unset($this->making[$playerName]);
			$player->sendMessage("§e{$this->prefix}포탈이 생성되었습니다!");
			return;
		}
	}

	public function onBreak(BlockBreakEvent $ev){
		$player = $ev->getPlayer();
		if(isset($this->making[$playerName = $player->getLowerCaseName()])){
			$block = $ev->getBlock();
			$pos = Position::fromObject($block, $block->getLevel()) . add(0, 1, 0);
			$this->addPortal($this->making[$playerName], $pos);
			$player->sendMessage("§e{$this->prefix}포탈을 더 추가하려면 더 부수고 그만 만드려면 도착지 아래 블럭을 터치해주세요.");
			return;
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			return false;
		}
		if(in_array(strtolower($label), $this->commands)){
			if($this->isBanned($label)){
				return false;
			}
			$this->warp($sender, $label);
			return true;
		}
		if($label === "워프" || $label === "warp"){
			if(count($args) < 2){
				return false;
			}
			if($args[0] === "추가" || $args[0] === "a"){
				//워프 추가 $name $amount
				if(count($args) < 3){
					$sender->sendTip("§e{$this->prefix}/워프 추가 $name $amount");
					return false;
				}
				if(isset($this->making[$playerName = $sender->getLowerCaseName()])){
					$sender->sendMessage("§e{$this->prefix}이미 만드는중입니다.");
					return false;
				}
				if(!is_numeric($args[2])){
					$sender->sendTip("§e{$this->prefix}포탈수를 숫자로 입력해주세요.");
					return false;
				}
				if($this->isWarp($args[1])){
					$sender->sendTip("§e{$this->prefix}이미 존재하는 워프입니다.");
					return false;
				}
				$this->making[$playerName] = $args[1];
				$sender->sendMessage("§e{$this->prefix}포탈을 설치할곳의 아래 블럭을 부서주세요.");
				$this->getServer()->getCommandMap()->register($args[1], new Command($args[1]));
				$this->addWarp($args[1]);
				return true;
			}
			if($args[0] === "삭제" || $args[0] === "d"){
				//워프 삭제 $name
				if(count($args) < 2){
					$sender->sendTip("§e{$this->prefix}/워프 삭제 $name");
					return false;
				}
				if(!$this->isWarp($args[1])){
					$sender->sendTip("§e{$this->prefix}존재하지 않는 워프입니다.");
					return false;
				}
				$this->delWarp($args[1]);
				$sender->sendMessage("§e{$this->prefix}워프를 삭제했습니다.");
				$this->getServer()->getCommandMap()->unregister(new Command($args[1]));
				return true;
			}
			if($args[0] === "금지" || $args[0] === "b"){
				//워프 금지 $name
				if(count($args) < 2){
					$sender->sendMessage("§e{$this->prefix}/워프 금지 $name");
					return false;
				}
				if(!$this->isWarp($args[1])){
					$sender->sendMessage("§e{$this->prefix}존재하지 않는 워프입니다.");
					return false;
				}
				if($this->isBanned($args[1])){
					$this->setBanned($args[1], false);
					$sender->sendMessage("§e{$this->prefix}워프의 금지를 풀었습니다.");
					return true;
				}
				$this->setBanned($args[1], true);
				$sender->sendMessage("§e{$this->prefix}워프를 금지했습니다.");
				return true;
			}
			if($args[0] === "목록" || $args[0] === "l"){
				//워프 목록
				$n = "";
				foreach($this->commands as $q){
					$n .= $q . ", ";
				}
				$sender->sendMessage("§e{$this->prefix}워프의 개수 : {count($this->g)}\n {$n}");
				return true;
			}
		}
	}

	public function onSnick(PlayerToggleSneakEvent $ev){
		$player = $ev->getPlayer();
		$position = $player->getPosition();
		if($this->isWarp($position)){
			return;
		}
		if($this->isBanned($position)){
			return;
		}
		$this->warp($player, $position);
		return;
	}

	public function addWarp($name){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		$this->g[strtolower($name)] = ["portals" => [], "destination" => null, "banned" => false];
	}

	public function addPortal($name, array $portals){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		$p = $this->g[strtolower($name)]["portals"];
		foreach($portals as $a){
			array_push($p, $this->floor($a));
		}
		$this->g[strtolower($name)]["portals"] = $p;
	}

	public function delPortal($name, Position $portal){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		array_splice($this->g[strtolower($name)]["portals"], array_search($this->floor($portal), $g[strtolower($name)]["portals"]), 1);
	}

	public function setDes($name, Position $pos){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		$this->g[strtolower($name)]["destination"] = $this->floor($pos);
	}

	public function isBanned($name){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		return $this->g[strtolower($name)];
	}

	public function setBanned($name, bool $banned = true){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		$this->g[strtolower($name)]["banned"] = $banned;
	}

	public function delWarp($name){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		unset($this->g[strtolower($name)]);
	}

	public function isWarp($name){
		if($name instanceof Position){
			return in_array($this->floor($pos), $this->portals);
		}
		return in_array($name, $this->g);
	}

	public function getWarpName(Position $pos){
		foreach($this->g as $name => $value){
			if(in_array($this->floor($pos), $value["portals"])){
				return $name;
			}
		}
	}

	public function getDes($name){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		return $this->g[$name]["destination"];
	}

	public function floor(Position $pos){
		$pos->x = (int) $pos->x;
		$pos->y = (int) $pos->y;
		$pos->z = (int) $pos->z;
		return $pos;
	}

	function warp($player, $name){
		if($name instanceof Position){
			$name = $this->getWarpName($name);
		}
		$des = $this->getDes($name);
		$player->teleport($des);
		$player->sendTip("§6워프 !");
		//돈 줄이기
	}

	function save(){
		$this->config->setAll($this->g);
		$this->config->save();
	}
}