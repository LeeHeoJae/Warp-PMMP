<?php

/**
 * @author PresentKim (debe3721@gmail.com)
 * @link   https://github.com/PresentKim
 */

namespace athena\sabone\data;

class DestinationData implements \JsonSerializable{
	/** @var DestinationData[] */
	private static $instances = [];

	/**
	 * @return DestinationData[]
	 */
	public static function getInstances() : array{
		return self::$instances;
	}

	/**
	 * @param string $name
	 *
	 * @return null|DestinationData
	 */
	public static function getInstance(string $name) : ?DestinationData{
		return self::$instances[$name] ?? null;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function removeInstance(string $name) : bool{
		if(isset(self::$instances[$name])){
			unset(self::$instances[$name]);
		}
		return false;
	}

	/** @var string */
	private $name;

	/** @var PositionData */
	private $destination;

	/** @var bool */
	private $banned;

	/**
	 * DestinationData constructor.
	 *
	 * @param string       $name
	 * @param PositionData $destination
	 * @param bool         $banned
	 */
	public function __construct(string $name, PositionData $destination, bool $banned){
		$this->name = $name;
		$this->destination = $destination;
		$this->banned = $banned;

		self::$instances[$name] = $this;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name) : void{
		$this->name = $name;
	}

	/**
	 * @return PositionData
	 */
	public function getDestination() : PositionData{
		return $this->destination;
	}

	/**
	 * @param PositionData $destination
	 */
	public function setDestination(PositionData $destination) : void{
		$this->destination = $destination;
	}

	/**
	 * @return bool
	 */
	public function isBanned() : bool{
		return $this->banned;
	}

	/**
	 * @param bool $banned
	 */
	public function setBanned(bool $banned) : void{
		$this->banned = $banned;
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
		/**
		 * name not include here, used as the key of array
		 */
		$portals = [];
		foreach(PortalData::getInstances() as $hashKey => $portalData){
			if($portalData->getDestination() === $this){
				$portals[$hashKey] = $portalData;
			}
		}
		return [
			"portals" => $portals,
			"destination" => $this->destination,
			"banned" => $this->banned
		];
	}

	/**
	 * Deserialize in JSON to generate DestinationData
	 *
	 * @param array  $json mixed data which can be serialized by <b>json_encode</b>,
	 * @param string $name
	 *
	 * @return DestinationData
	 */
	public static function jsonDeserialize(array $json, string $name) : DestinationData{
		foreach($json["portals"] as $hashKey => $portalJson){
			PortalData::jsonDeserialize($portalJson, $hashKey);
		}
		return new DestinationData(
			$name,
			PositionData::jsonDeserialize($json["destination"]),
			(bool) $json["banned"]
		);
	}
}