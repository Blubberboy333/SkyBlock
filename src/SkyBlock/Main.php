<?php

namespace SkyBlock;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\plugin\PluginBase as Base;
use pocketmine\math\Vector3;
use pocketmine\block\Dirt;
use pocketmine\block\Sand;
use pocketmine\block\Grass;
use pocketmine\block\Wood;
use pocketmine\level\generator\object\Tree;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\block\Sapling;
use pocketmine\utils\Random;

class Main extends Base implements Listener{
	public function onEnable(){
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!(is_dir($this->getDataFolder()."Islands/"))){
			@mkdir($this->getDataFolder()."Islands/");
		}
		$this->getLogger()->info(TextFormat::GREEN . "Done!");
	}
	public function onDisable(){
		$this->getLogger()->info(TextFormat::RED . "SkyBlock disabled");
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		if(strtolower($command->getName()) == "is"){
			if(!(isset($args[0]))){
				$sender->sendMessage(TextFormat::YELLOW . "You didn't add a subcommand");
				$sender->sendMessage(TextFormat::GREEN . "Use: " . TextFormat::RESET . "/is help");
				return true;
			}elseif(isset($args[0])){
				if($args[0] == "help"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.help")){
						if(!(isset($args[1])) or $args[1] == "1"){
							$sender->sendMessage(TextFormat::BLUE . "Showing help page 1 of 1");
							$sender->sendMessage(TextFormat::GREEN . "/is help");
							$sender->sendMessage(TextFormat::GREEN . "/is create");
							$sender->sendMessage(TextFormat::GREEN . "/is home");
							$sender->sendMessage(TextFormat::GREEN . "/is sethome");
							$sender->sendMessage(TextFormat::BLUE . "Use /is help 2 to view the second page");
							return true;
						}elseif($args[1] == "2"){
							$sender->sendMessage(TextFormat::BLUE."Showing help page 2 of 2");
							$sender->sendMessage(TextFormat::GREEN."/is find");
							$sender->sendMessage(TextFormat::GREEN."/is add");
							$sender->sendMessage(TextFormat::GREEN."/is remove");
							$sender->sendMessage(TextFormat::BLUE."End of command list");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You cannot view the help menu");
						return true;
					}
				}elseif($args[0] == "create"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.create")){
						$senderIs = $this->getDataFolder()."Islands/".$sender->getName().".yml";
						$playerLevel = $sender->getLevel()->getName();
						$skyblockWorlds = $this->getConfig()->get("Worlds");
						if(!(in_array($playerLevel, $skyblockWorlds))){
							$sender->sendMessage("You aren't in a skyblock world!");
							return true;
						}else{
							if(!(file_exists($senderIs))){
								$this->makeIsland($sender->getName());
								return true;
							}else{
								$sender->sendMessage(TextFormat::YELLOW . "You already have an island");
								return true;
							}
						}
					}else{
						$sender->sendMessage(TextFormat::RED . "You cannot create an island");
						return true;
					}
				}elseif($args[0] == "home"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.home")){
						if(!(file_exists($this->getDataFolder()."Islands/".$sender->getName().".yml"))){
							$sender->sendMessage("You don't have an island. Use /is create to make one");
							return true;
						}else{
							$sender->sendMessage(TextFormat::GREEN."Teleporting to your island...");
							$file = new Config($this->getDataFolder()."Islands/".$sender->getName().".yml", Config::YAML);
							$x = $file->get("X");
							$y = $file->get("Y");
							$z = $file->get("Z");
							$world = $this->getServer()->getLevelByName($file->get("World"));
							$sender->teleport(new Position($x, $y, $z, $world));
							$sender->sendMessage(TextFormat::GREEN."Done!");
							return true;
						}
					}else{
						$sender->sendMessage("You do not have permission to do that");
						return true;
					}
				}elseif($args[0] == "find"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.find")){
						if(isset($args[1])){
							$p = $sender->getServer()->getPlayer($args[1]);
							if($p instanceof Player){
								$name = $p->getName();
								if(file_exists($this->getDataFolder()."Islands/".$name.".yml")){
									$file = new Config($this->getDataFolder()."Islands/".$name.".yml", Config::YAML);
									$x = $file->get("X");
									$y = $file->get("Y");
									$z = $file->get("Z");
									$world = $file->get("World");
									$sender->sendMessage(TextFormat::GREEN."The location of ".$name."'s island is");
									$sender->sendMessage($x." ".$y." ".$z);
									$sender->sendMessage(TextFormat::GREEN."In world ".TextFormat::RESET.$world);
								}else{
									$sender->sendMessage($name . " does not have an island");
									return true;
								}
							}elseif(file_exists($this->getDataFolder()."Islands/".$args[1].".yml")){
								$file = new Config($this->getDataFolder()."Islands/".$args[1].".yml", Config::YAML);
								$x = $file->get("X");
								$y = $file->get("Y");
								$z = $file->get("Z");
								$world = $file->get("World");
								$sender->sendMessage(TextFormat::GREEN."The location of ".$args[1]."'s island is");
								$sender->sendMessage($x." ".$y." ".$z);
								$sender->sendMessage(TextFormat::GREEN."In world ".TextFormat::RESET.$world);
							}else{
								$sender->sendMessage("There are no islands under the name of ".$args[1]);
								return true;
							}
						}else{
							$sender->sendMessage(TextFormat::YELLOW . "You need to specify a player");
							return true;
						}
					}else{
						$sender->sendMessage(TextFormat::YELLOW . "You cannot find the coords of a player's island");
						return true;
					}
				}elseif($args[0] == "delete"){
					if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.delete")){
						if(!(isset($args[1]))){
							$sender->sendMessage("Are you sure? Use /is delete yes to confirm");
							return true;
						}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".yml")){
									unlink($this->getDataFolder()."Islands/".$sender->getName().".yml");
									$sender->sendMessage("Your island has been deleted");
									return true;
								}else{
									$sender->sendMessage("You don't have an island");
									return true;
								}
							}else{
								$sender->sendMessage("Okay, we won't delete your island");
								return true;
							}
						}else{
							$sender->sendMessage("You cannot delete your island");
							return true;
						}
					}elseif($args[0] == "sethome"){
						if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.sethome")){
							if(!(isset($args[1]))){
								$sender->sendMessage("Are you sure? Make sure you are on your island");
								$sender->sendMessage("Your island will be lost if you're not on your island. Do /is sethome yes to confirm");
								return true;
							}elseif($args[1] == "yes"){
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".yml")){
									$sender->sendMessage("Setting your home...");
									$file = new Config($this->getDataFolder()."Islands/".$sender->getName().".yml", Config::YAML);
									$file->set("X", $sender->x);
									$file->set("Y", $sender->y);
									$file->set("Z", $sender->z);
									$file->set("World", $sender->getLevel()->getName());
									$file->save();
									$sender->sendMessage("Done!");
									return true;
								}else{
									$sender->sendMessage("You don't have an island");
									return true;
								}
							}elseif($args[1] == "no"){
								$sender->sendMessage("Okay, we won't set your home");
								return true;
							}else{
								$sender->sendMessage("Unknown subcommand: ".$args[1]);
								$sender->sendMessage("/sethome <yes | no>");
								return true;
							}
						}else{
							$sender->sendMessage("You don't have permission to set your home");
							return true;
						}
					}elseif($args[0] == "add"){
						if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.add")){
							if(!(isset($args[1]))){
								$sender->sendMessage(TextFormat::YELLOW."You need to specify a player!");
							}else{
								if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".yml")){
									$player = $this->getServer()->getPlayer($args[1]);
									if($player instanceof Player){
										if(file_exists($this->getDataFolder()."Islands/".$player->getName().".yml")){
											$sender->sendMessage(TextFormat::YELLOW."That player already has an island!");
											$player->sendMessage(TextFormat::YELLOW.$sender->getName()." wants to add you to their island, but you already have one!");
											$player->sendMessage(TextFormat::YELLOW."Use /is delete to delete yous island if you want to join their island");
											return true;
										}else{
											$sender->sendMessage(TextFormat::GREEN."Adding ".$player->getName()." to your island...");
											$player->sendMessage(TextFormat::BLUE.$sender->getName()." added you to their island!");
											$this->getLogger()->info(TextFormat::YELLOW.$sender->getName()." added ".$player->getName()." to their island!");
											$newFile = new Config($this->getDataFolder()."Islands/".$player->getName().".yml", Config::YAML);
											$isFile = new Config($this->getDataFolder()."ISlands/".$sender->getName().".yml", Config::YAML);
											$newFile->set("X", $isFile->get("X"));
											$newFile->set("Y", $isFile->get("Y"));
											$newFile->set("Z", $isFile->get("Z"));
											$newFile->set("World", $isFile->get("World"));
											$newFile->set("Owner", $sender->getName());
											$newFile->save();
											$player->sendMessage(TextFormat::BLUE."Use /is home to go to your island!");
										}
									}
								}else{
									$sender->sendMessage(TextFormat::YELLOW."You don't have an island! Use /is create to make one!");
								}
							}
						}else{
							$sender->sendMessage(TextFormat::RED."You don't have permission to do that!");
							return true;
						}
					}elseif($args[0] == "remove"){
						if($sender->hasPermission("is") || $sender->hasPermission("is.command") || $sender->hasPermission("is.command.remove")){
							if(!(isset($args[1]))){
								$sender->sendMessage(TextFormat::YELLOW."You need to specify a player!");
								return true;
							}else{
								$player = $this->getServer()->getPlayer($args[1]);
								if($player instanceof Player){
									if(file_exists($this->getDataFolder()."Islands/".$sender->getName().".yml")){
										if(file_exists($this->getDataFolder()."Islands/".$player->getName().".yml")){
											$file = new Config($this->getDataFolder()."Islands/".$player->getName().".yml", Config::YAML);
											if(null !==($file->get("Owner"))){
												if($file->get("Owner") == $sender->getName()){
													$sender->sendMessage(TextFormat::YELLOW."Removed ".$player->getName()." from your island");
													$player->sendMessage(TextFormat::YELLOW."You have been removed from ".$sender->getName()."'s island");
													$this->getLogger()->info(TextFormat::YELLOW.$sender->getName()." removed ".$player->getName()." from their island");
													if($file->get("World") == $player->getLevel()->getName()){
														$spawn = $this->getServer()->getLevelByName($player->getLevel()->getName())->getSafeSpawn();
														$player->teleport($spawn);
												}
												unlink($this->getDataFolder()."Islands/".$player->getName().".yml");
												}else{
													$sender->sendMessage(TextFormat::YELLOW."That player isn't on your island!");
													return true;
												}
											}else{
												$sender->sendMessage(TextFormat::YELLOW."That player owns his/her own island!");
												return true;
											}
										}else{
											$sender->sendMessage(TextFormat::YELLOW."That player doesn't have an island!");
											return true;
										}
									}else{
										$sender->sendMessage(TextFormat::YELLOW."You don't have an island! Use /is create to make one!");
										return true;
									}
								}else{
									$sender->sendMessage($args[1]." isn't online!");
									return true;
								}
							}
						}else{
							$sender->sendMessage(TextFormat::RED."You don't have permission to do that!");
							return true;
						}
					}
				}
			}
		}
	
	public function makeIsland($name){
		$player = $this->getServer()->getPlayer($name);
		if(!($player instanceof Player)){
			return "Error: Player not found";
		}else{
			
			$randX = rand($this->getConfig()->get("LowRand"), $this->getConfig()->get("HighRand"));
			$randZ = rand($this->getConfig()->get("LowRand"), $this->getConfig()->get("HighRand"));
			$Y = 100;
			
			$levelName = $this->getServer()->getPlayer($name)->getLevel();
			
			// Make a file for the island
			$file = new Config($this->getDataFolder()."Islands/".$player->getName().".yml", Config::YAML);
			$file->set("X", $randX);
			$file->set("Y", $Y);
			$file->set("Z", $randZ);
			$file->set("World", $player->getLevel()->getName());
			$file->save();
			
			// Top layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $Y, $randZ), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+6, $Y, $randZ+1), new Grass());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+5, $Y, $randZ+1), new Grass());
			
			// 3rd side 
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+4, $Y, $randZ+1), new Grass());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+3, $Y, $randZ+1), new Grass());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+2, $Y, $randZ+1), new Grass());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+6), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+5), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+4), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+3), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+2), new Grass());
			$levelName->setBlock(new Vector3($randX+1, $Y, $randZ+1), new Grass());
			
			// Middle layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $Y-1, $randZ), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+6, $Y-1, $randZ+1), new Sand());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+5, $Y-1, $randZ+1), new Sand());
			
			// 3rd side
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+4, $Y-1, $randZ+1), new Sand());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+3, $Y-1, $randZ+1), new Sand());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+2, $Y-1, $randZ+1), new Sand());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+6), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+5), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+4), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+3), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+2), new Sand());
			$levelName->setBlock(new Vector3($randX+1, $Y-1, $randZ+1), new Sand());
			
			
			
			// Bottom layer of the island
			
			// 1st side
			$levelName->setBlock(new Vector3($randX, $Y-2, $randZ), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+6, $Y-2, $randZ+1), new Dirt());
			
			// 2nd side
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+5, $Y-2, $randZ+1), new Dirt());
			
			// 3rd side
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+4, $Y-2, $randZ+1), new Dirt());
			
			// 4th side
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+3, $Y-2, $randZ+1), new Dirt());
			
			// 5th side
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+2, $Y-2, $randZ+1), new Dirt());
			
			// 6th side
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+6), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+5), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+4), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+3), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+2), new Dirt());
			$levelName->setBlock(new Vector3($randX+1, $Y-2, $randZ+1), new Dirt());
			
			// Tree
			$type = 0;
			Tree::growTree($levelName, $randX+6, $Y+1, $randZ+6, new Random(mt_rand()), Sapling::OAK);
			
			// Teleport the player to their new island
			$player->teleport(new Position($randX, $Y+5, $randZ, $this->getServer()->getLevelByName($levelName)));
			$player->sendMessage(TextFormat::GREEN . "Welcome to your new island");
			$player->sendMessage(TextFormat::GREEN . "If your island didn't spawn,");
			$player->sendMessage(TextFormat::GREEN . "Use /is delete");
			$player->sendMessage(TextFormat::GREEN . "Then make a new island");
			
			// Give the player a starter kit
			
			// String
			$player->getInventory()->addItem(Item::get("287"));
			$player->getInventory()->addItem(Item::get("287"));
			$player->getInventory()->addItem(Item::get("287"));
			$player->getInventory()->addItem(Item::get("287"));
			$player->getInventory()->addItem(Item::get("287"));
			
			// Emerald
			$player->getInventory()->addItem(Item::get("388"));
			$player->getInventory()->addItem(Item::get("388"));
			$player->getInventory()->addItem(Item::get("388"));
			$player->getInventory()->addItem(Item::get("388"));
			$player->getInventory()->addItem(Item::get("388"));
			
			// Sapling
			$player->getInventory()->addItem(Item::get("6"));
			$player->getInventory()->addItem(Item::get("6"));
			$player->getInventory()->addItem(Item::get("6"));
			$player->getInventory()->addItem(Item::get("6"));
			$player->getInventory()->addItem(Item::get("6"));
			
			// Water
			$player->getInventory()->addItem(Item::get("8"));
			$player->getInventory()->addItem(Item::get("8"));
			
			// Lava
			$player->getInventory()->addItem(Item::get("10"));
			
			// Seeds
			$player->getInventory()->addItem(Item::get("295"));
			$player->getInventory()->addItem(Item::get("295"));
			$player->getInventory()->addItem(Item::get("295"));
			$player->getInventory()->addItem(Item::get("295"));
			$player->getInventory()->addItem(Item::get("295"));
			
			// Melon seeds
			$player->getInventory()->addItem(Item::get("362"));
			
			// Cactus
			$player->getInventory()->addItem(Item::get("81"));
			
			// Iron
			$player->getInventory()->addItem(Item::get("265"));
			$player->getInventory()->addItem(Item::get("265"));
			$player->getInventory()->addItem(Item::get("265"));
			$player->getInventory()->addItem(Item::get("265"));
			$player->getInventory()->addItem(Item::get("265"));
			$player->getInventory()->addItem(Item::get("265"));
			
			// Chest
			$player->getInventory()->addItem(Item::get("54"));
			
			// Bones
			$player->getInventory()->addItem(Item::get("352"));
			$player->getInventory()->addItem(Item::get("352"));
			$player->getInventory()->addItem(Item::get("352"));
			$player->getInventory()->addItem(Item::get("352"));
			$player->getInventory()->addItem(Item::get("352"));
			
			$this->getLogger()->info($name . TextFormat::YELLOW . " made an island");
		}
	}
}
