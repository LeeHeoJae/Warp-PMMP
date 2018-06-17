<?php

namespace athena\sabone;

use athena\sabone\data\{
	DestinationData, PortalData, PositionData
};
use pocketmine\command\{
	Command, CommandSender
};
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{
	PlayerInteractEvent, PlayerToggleSneakEvent
};
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Warp extends PluginBase implements Listener{
	/** @var Config */
	public $config;
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

		foreach($this->config->getAll() as $destinationName => $data){
			DestinationData::jsonDeserialize($data, $destinationName);
		}

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		//포탈에 예쁘게 파티클 꾸미기 ☆☆
	}

	public function onDisable(){
		$this->config->setAll(DestinationData::getInstances());
		$this->config->save();
	}

	public function onTouch(PlayerInteractEvent $ev){
		$player = $ev->getPlayer();
		if(isset($this->making[$playerName = $player->getLowerCaseName()])){
			$pos = Position::fromObject($ev->getBlock()->add(0, 1, 0), $player->getLevel());
			$destinationData = DestinationData::getInstance($this->making[$playerName]);
			if($destinationData instanceof DestinationData){
				$destinationData->setDestination(PositionData::fromPosition($pos));
				$player->sendMessage("§e{$this->prefix}{$this->making[$playerName]} 워프의 목적지가 변경되었습니다!");
			}else{
				new DestinationData($this->making[$playerName], PositionData::fromPosition($pos), false);
				$player->sendMessage("§e{$this->prefix}{$this->making[$playerName]} 워프가 생성되었습니다!");
			}
			unset($this->making[$playerName]);
		}
	}

	public function onBreak(BlockBreakEvent $ev){
		$player = $ev->getPlayer();
		if(isset($this->making[$playerName = $player->getLowerCaseName()])){
			$pos = Position::fromObject($ev->getBlock()->add(0, 1, 0), $player->getLevel());
			if(PortalData::getInstance(PositionData::toHashKey($pos)) instanceof PortalData){
				unset($this->making[$playerName]);
				$player->sendMessage("§e{$this->prefix}포탈 추가를 중지합니다");
			}else{
				$destinationData = DestinationData::getInstance($this->making[$playerName]);
				if($destinationData instanceof DestinationData){
					PortalData::fromPosition($pos, $destinationData);
					$player->sendMessage("§e{$this->prefix}포탈을 더 추가하려면 더 부수고 그만 만드려면 생성한 포탈을 한번 더 부셔주세요");
				}else{
					$player->sendMessage("§e{$this->prefix}블럭을 터치하여 워프의 목적지를 먼저 설정해주세요");
				}
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			return false;
		}
		if($label === "워프" || $label === "warp"){
			if(count($args) < 2){
				return false;
			}
			if($args[0] === "추가" || $args[0] === "a"){
				if(isset($this->making[$playerName = $sender->getLowerCaseName()])){
					$sender->sendMessage("§e{$this->prefix}이미 만드는중입니다.");
					return false;
				}
				if(DestinationData::getInstance($args[1]) instanceof DestinationData){
					$sender->sendMessage("§e{$this->prefix}워프의 목적지 아래 블럭을 터치하여 목적지를 생성해주세요.");
				}else{
					$sender->sendMessage("§e{$this->prefix}포탈을 설치할곳의 아래 블럭을 부서주세요.");
				}
				$this->making[$playerName] = $args[1];
				return true;
			}
			if($args[0] === "삭제" || $args[0] === "d"){
				if(DestinationData::getInstance($args[1]) instanceof DestinationData){
					DestinationData::removeInstance($args[1]);
					$sender->sendMessage("§e{$this->prefix}워프를 삭제했습니다.");
				}else{
					$sender->sendTip("§e{$this->prefix}존재하지 않는 워프입니다.");
				}
				return true;
			}
			if($args[0] === "금지" || $args[0] === "b"){
				$destinationData = DestinationData::getInstance($args[1]);
				if($destinationData instanceof DestinationData){
					$destinationData->setBanned(!$destinationData->isBanned());
					if($destinationData->isBanned()){
						$sender->sendMessage("§e{$this->prefix}워프를 금지했습니다.");
					}else{
						$sender->sendMessage("§e{$this->prefix}워프의 금지를 풀었습니다.");
					}
				}else{
					$sender->sendTip("§e{$this->prefix}존재하지 않는 워프입니다.");
				}
				return true;
			}
			if($args[0] === "목록" || $args[0] === "l"){
				$destinationDatas = DestinationData::getInstances();
				$sender->sendMessage("§e{$this->prefix}워프의 개수 : " . count($destinationDatas) . "}\n " . implode(", ", array_keys($destinationDatas)));
				return true;
			}
		}
		return false;
	}

	public function onSnick(PlayerToggleSneakEvent $ev){
		$player = $ev->getPlayer();
		$portalData = PortalData::getInstance(PositionData::toHashKey($player));
		if($portalData instanceof PortalData){
			$destinationData = $portalData->getDestination();
			if(!$destinationData->isBanned()){
				$player->teleport($destinationData->getDestination());
				$player->sendTip("§6워프 !");
				//돈 줄이기
			}
		}
		return;
	}
}