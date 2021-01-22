<?php

declare(strict_types=1);

namespace IvanCraft623\AtomicModel\Entity;


use pocketmine\{Server, Player};
use pocketmine\entity\{Human, Monster, EntityIds};
use pocketmine\item\Item;

use IvanCraft623\MurderMystery\{Murder, PluginUtils, Arena\Arena};

class AtomicModel3D extends Human {

	public function onUpdate(int $currentTick) : bool {
		//Scale
		$this->setScale(2.4);
		//Rotate
		$this->yaw+=2.25;
		$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		$this->updateMovement();
		return parent::onUpdate($currentTick);
	}
}
