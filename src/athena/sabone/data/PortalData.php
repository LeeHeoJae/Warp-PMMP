<?php

/**
 * @author PresentKim (debe3721@gmail.com)
 * @link   https://github.com/PresentKim
 */

namespace athena\sabone\data;

use pocketmine\level\Level;
use pocketmine\level\Position;

class PortalData extends PositionData{
	/** @var PortalData[] */
	private static $instances = [];

	/**
	 * @return PortalData[]
	 */
	public static function getInstances() : array{
		return self::$instances;
	}

	/**
	 * @param string $key
	 *
	 * @return null|PortalData
	 */
	public static function getInstance(string $key) : ?PortalData{
		return self::$instances[$key] ?? null;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public static function removeInstance(string $key) : bool{
		if(isset(self::$instances[$key])){
			unset(self::$instances[$key]);
		}
		return false;
	}

	/** @var DestinationData */
	private $destination;

	/**
	 * PortalData constructor.
	 *
	 * @param int             $x
	 * @param int             $y
	 * @param int             $z
	 * @param Level|null      $level
	 * @param DestinationData $destination
	 */
	public function __construct(int $x = 0, int $y = 0, int $z = 0, Level $level = null, DestinationData $destination){
		parent::__construct($x, $y, $z, $level);
		$this->destination = $destination;

		self::$instances[$this->hashKey()] = $this;
	}

	/**
	 * @return DestinationData
	 */
	public function getDestination() : DestinationData{
		return $this->destination;
	}

	/**
	 * @param DestinationData $destination
	 */
	public function setDestination(DestinationData $destination) : void{
		$this->destination = $destination;
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() : array{
		return [$this->destination->getName()];
	}

	/**
	 * Deserialize in JSON to generate PortalData
	 *
	 * @param array  $json mixed data which can be serialized by <b>json_encode</b>,
	 * @param string $hashKey
	 *
	 * @return PortalData
	 */
	public static function jsonDeserialize(array $json, string $hashKey = "") : PositionData{
		return self::fromObject(PositionData::fromHashKey($hashKey), DestinationData::getInstance($json[0]));
	}

	/**
	 * generate PortalData from Position
	 *
	 * @param Position             $pos
	 * @param DestinationData|null $destination
	 *
	 * @return PortalData
	 */
	public static function fromPosition(Position $pos, DestinationData $destination = null) : PositionData{
		return new PortalData(
			$pos->getFloorX(),
			$pos->getFloorY(),
			$pos->getFloorZ(),
			$pos->level,
			$destination
		);
	}
}