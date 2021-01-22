<?php

declare(strict_types=1);

namespace IvanCraft623\AtomicModel;

use IvanCraft623\AtomicModel\{Entity\AtomicModel3D};

use pocketmine\command\{Command, CommandSender};
use pocketmine\event\{Listener, entity\EntityDamageEvent, entity\EntityDamageByEntityEvent};
use pocketmine\nbt\tag\{CompoundTag, ByteArrayTag, StringTag};
use pocketmine\{Player, Server, level\Position, entity\Entity, plugin\PluginBase, Utils\Config};

class AtomicModel extends PluginBase implements Listener {

	public function onEnable() : void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResources();
		$this->loadEntitys();
	}

	public function saveResources() : void {
		$this->saveResource('config.yml');
		$this->saveResource('Entities/Skins/AtomicModel.png');
		$this->saveResource('Entities/Geometries/AtomicModel.json');
	}

	public function loadEntitys() : void {
		$values = [AtomicModel3D::class];
		foreach ($values as $entitys) {
			Entity::registerEntity($entitys, true);
		}
		unset ($values);
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		switch ($cmd->getName()) {
			case 'modeloa':
				if ($sender instanceof Player) {
					if (isset($args[0])) {
						switch ($args[0]) {
							case 'spawn':
								if ($sender->hasPermission("modeloa.cmd.spawn")) {
									$this->spawnAtomicModel($sender);
									$sender->sendMessage("§l§9» §r§a" . $this->getMessage("AtomicModel.Spawn"));
								} else {
									$sender->sendMessage("§cYou do not have permission to use this command!");
								}
							break;

							case 'kill':
								foreach ($sender->getLevel()->getEntities() as $entity) {
									if ($entity instanceof AtomicModel3D) {
										$entity->kill();
									}
								}
								$sender->sendMessage("§l§9» §r§e" . $this->getMessage("AtomicModel.Kill"));
							break;

							case 'setpos':
								if ($sender->hasPermission("modeloa.cmd.setpos")) {
									$this->setModelPos($sender);
									$sender->sendMessage("§l§9» §r§a" . $this->getMessage("AtomicModel.SetPos"));
								} else {
									$sender->sendMessage("§cYou do not have permission to use this command!");
								}
							break;

							case 'tp':
								$modelPos = $this->getModelPos();
								if (!is_null($modelPos)) {
									if (!Server::getInstance()->isLevelLoaded($modelPos->getLevel())) {
										Server::getInstance()->loadLevel($modelPos->getLevel());
									}
									$modelPos->getLevel()->loadChunk($modelPos->getFloorX(), $modelPos->getFloorZ());
									$sender->teleport($modelPos);
									$sender->sendMessage("§l§9» §e" . $this->getMessage("AtomicModel.Tp"));
								} else {
									$sender->sendMessage("§l§c» §r§c" . $this->getMessage("AtomicModel.isNotSetPos"));
								}
							break;
							
							default:
								if ($sender->hasPermission("modeloa.cmd.spawn") || $sender->hasPermission("modeloa.cmd.setpos")) {
									$sender->sendMessage(
										"§f---- §bAtomic Model Commands §f----"."\n".
										"§eUse: §a/modeloa spawn §7(To spawn the 3D Model)"."\n".
										"§eUse: §a/modeloa setpos §7(To set the 3D Model position for tp)"."\n".
										"§eUse: §a/modeloa tp §7(To teleport to the Atomic Model)"
									);
								} else {
									$sender->sendMessage(
										"§f---- §bAtomic Model Commands §f----"."\n".
										"§eUse: §a/modeloa tp §7(To teleport to the Atomic Model)"
									);
								}
							break;
						}
					} else {
						if ($sender->hasPermission("modeloa.cmd.spawn") || $sender->hasPermission("modeloa.cmd.setpos")) {
							$sender->sendMessage(
								"§f---- §bAtomic Model Commands §f----"."\n".
								"§eUse: §a/modeloa spawn §7(To spawn the 3D Model)"."\n".
								"§eUse: §a/modeloa setpos §7(To set the 3D Model position for tp)"."\n".
								"§eUse: §a/modeloa tp §7(To teleport to the Atomic Model)"
							);
						} else {
							$sender->sendMessage(
								"§f---- §bAtomic Model Commands §f----"."\n".
								"§eUse: §a/modeloa tp §7(To teleport to the Atomic Model)"
							);
						}
					}
				} else {
					$sender->sendMessage("§cYou can only use this command in the game!");
				}
			break;
		}
		return true;
	}

	public function getMessage($arg) {
		return $this->getConfig()->get("Message.".$arg);
	}

	public function spawnAtomicModel(Player $player) : void {
		$nbt = Entity::createBaseNBT($player, null, 2, 2);
		$dir = $this->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Skins" . DIRECTORY_SEPARATOR . "AtomicModel.png";
		$img = @imagecreatefrompng($dir);
		$skinbytes = '';
		$values = (int)@getimagesize($dir)[1];
		for($y = 0; $y < $values; $y++) {
			for($x = 0; $x < 64; $x++) {
				$bytes = @imagecolorat($img, $x, $y);
				$a = ((~((int)($bytes >> 24))) << 1) & 0xff;
				$b = ($bytes >> 16) & 0xff;
				$c = ($bytes >> 8) & 0xff;
				$d = $bytes & 0xff;
				$skinbytes .= chr($b) . chr($c) . chr($d) . chr($a);
			}
		}
		@imagedestroy($img);
		$skinTag = new CompoundTag("Skin", [
			"Name" => new StringTag("Name", "AtomicModel"),
			"Data" => new ByteArrayTag("Data", $skinbytes),
			"GeometryName" => new StringTag("GeometryName", "geometry.AtomicModel"),
			"GeometryData" => new ByteArrayTag("GeometryData", file_get_contents($this->getDataFolder() . "Entities" . DIRECTORY_SEPARATOR . "Geometries" . DIRECTORY_SEPARATOR . "AtomicModel.json"))
		]);
		$nbt->setTag($skinTag);
		$npc = new AtomicModel3D($player->getLevel(), $nbt);
		$npc->setNameTagAlwaysVisible(false);
		$npc->setNameTagVisible(false);
		$npc->yaw = $player->getYaw();
		$npc->spawnToAll();
	}

	public function setModelPos(Player $player) : void {
		$Pos = [
			"X" => $player->getX(),
			"Y" => $player->getY(),
			"Z" => $player->getZ(),
			"world" => $player->getLevel()->getName()
		];
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$config->set("AtomicModelPos", $Pos);
		$config->save();
	}

	public function getModelPos() {
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
		$modelPos = $config->get("AtomicModelPos");
		if ($modelPos["X"] === "" || $modelPos["Y"] === "" || $modelPos["Z"] === "") {
			return null;
		} else {
			return new Position($modelPos["X"], $modelPos["Y"], $modelPos["Z"], Server::getInstance()->getLevelByName($modelPos["world"]));
		}
	}

	public function onDamageToNPC(EntityDamageEvent $event) {
		$npc = $event->getEntity();
		if ($npc instanceof AtomicModel3D) {
			if ($event instanceof EntityDamageByEntityEvent) {
				$event->setCancelled();
			}
		}
	}
}
