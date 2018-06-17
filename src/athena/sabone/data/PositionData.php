<?php

/**
 * @author PresentKim (debe3721@gmail.com)
 * @link   https://github.com/PresentKim
 */

namespace athena\sabone\data;

use pocketmine\level\Position;
use pocketmine\Server;

class PositionData extends Position implements \JsonSerializable{
	/**
	 * generate PositionData from Position
	 *
	 * @param Position $pos
	 *
	 * @return PositionData
	 */
	public static function fromPosition(Position $pos) : PositionData{
		return new PositionData(
			$pos->getFloorX(),
			$pos->getFloorY(),
			$pos->getFloorZ(),
			$pos->level
		);
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
		return [$this->getFloorX(), $this->getFloorY(), $this->getFloorZ(), $this->level->getFolderName()];
	}

	/**
	 * Deserialize in JSON to generate PositionData
	 *
	 * @param array $json mixed data which can be serialized by <b>json_encode</b>,
	 *
	 * @return PositionData
	 */
	public static function jsonDeserialize(array $json) : PositionData{
		return new PositionData(
			(int) $json[0],
			(int) $json[1],
			(int) $json[2],
			Server::getInstance()->getLevelByName($json[3])
		);
	}

	/**
	 * @return string for used as the key of array
	 */
	public function hashKey() : string{
		return "{$this->getFloorX()}/{$this->getFloorY()}/{$this->getFloorZ()}/{$this->level->getFolderName()}";
	}

	/**
	 * generate PositionData from hash key
	 *
	 * @param string $hashKey
	 *
	 * @return PositionData
	 */
	public static function fromHashKey(string $hashKey) : PositionData{
		return self::jsonDeserialize(explode("/", $hashKey));
	}
}