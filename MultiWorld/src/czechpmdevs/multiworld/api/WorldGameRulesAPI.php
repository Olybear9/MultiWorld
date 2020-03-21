@@ -1,228 +0,0 @@
<?php

/**
 * MultiWorld - PocketMine plugin that manages worlds.
 * Copyright (C) 2018  CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace multiworld\api;

use pocketmine\level\format\io\BaseLevelProvider;
use pocketmine\level\format\io\data\BaseNbtLevelData;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\Player;


/**
 * Class WorldGameRulesAPI
 * @package multiworld\api
 */
class WorldGameRulesAPI {


    /**
     * @param Level $level
     *
     * @return array
     */
    public static function getLevelGameRules(Level $level): array {
        $levelData = $level->getProvider()->getLevelData();
        if(!$levelData instanceof BaseNbtLevelData) {
            return self::getDefaultGameRules();
        }

        $compound = $levelData->getCompoundTag()->getCompoundTag("GameRules");

        if(!$compound instanceof CompoundTag) {
            $levelData->getCompoundTag()->setTag(new CompoundTag("GameRules", []));
            $rules = $levelData->getCompoundTag()->getCompoundTag("GameRules");
            foreach (self::getDefaultGameRules() as $rule => [$type, $value]) {
                $rules->offsetSet($rule, $value);
            }
        }

        $gameRules = [];

        foreach (self::getAllGameRules() as $rule) {
            if($compound->offsetExists($rule)) {
                $value = self::getValueFromString($compound->getString($rule));
                $gameRules[$rule] = is_bool($value) ? [1, $value] : [2, $value];
            }
        }

        return $gameRules;
    }

    /**
     * @param Level $level
     * @param array $gameRules
     *
     * @return bool
     */
    public static function setLevelGameRules(Level $level, array $gameRules): bool {
        $levelData = $level->getProvider()->getLevelData();
        if(!$levelData instanceof BaseNbtLevelData || !$levelData->getCompoundTag()->getCompoundTag("GameRules") instanceof CompoundTag) {
            return false;
        }

        foreach ($gameRules as $index => [$type, $value]) {
            $levelData->getCompoundTag()->setString($index, self::getStringFromValue($value));
        }

        $levelData->save();
        self::handleGameRuleChange($level, $gameRules);
        return true;
    }

    /**
     * @param Level $level
     * @param string $gameRule
     * @param bool $value
     *
     * @return bool
     */
    public static function updateLevelGameRule(Level $level, string $gameRule, bool $value): bool {
        $levelData = $level->getProvider()->getLevelData();
        if(!$levelData instanceof BaseNbtLevelData) {
            return false;
        }

        $compound = $levelData->getCompoundTag()->getCompoundTag("GameRules");
        $compound->setString($gameRule, self::getStringFromValue($value));

        $levelData->save();
        self::reloadGameRules($level);
        self::handleGameRuleChange($level, [$gameRule => [1, $value]]);
        return true;
    }

    /**
     * @param Level $level
     */
    public static function reloadGameRules(Level $level) {
        foreach ($level->getPlayers() as $player) {
            self::updateGameRules($player);
        }
    }

    /**
     * @return array $gameRules
     */
    public static function getDefaultGameRules(): array {
        return [
            //"commandBlockOutput" => [1, true], not implemented
            "doDaylightCycle" => [1, true],
            //"doEntityDrops" => [1, true], useless
            //"doFireTick" => [1, true], not implemented
            //"doInsomnia" => [1, true], (1.6)
            "doMobLoot" => [1, true],
            //"doMobSpawning" => [1, true], not implemented
            "doTileDrops" => [1, true],
            //"doWeatherCycle" => [1, true], not implemented
            "keepInventory" => [1, false],
            //"maxCommandChainLength" => [2, 65536], int ._.
            //"mobGriefing" => [1, true], not implemented
            "naturalRegeneration" => [1, true],
            "pvp" => [1, true],
            //"sendCommandFeedback" => [1, true], not implemented
            "showcoordinates" => [1, false],
            "tntexplodes" => [1, true]
        ];
    }

    /**
     * @param string $lowerString
     *
     * @return string
     */
    public static function getRuleFromLowerString(string $lowerString): string {
        $rules = [];
        foreach (self::getAllGameRules() as $rule) {
            $rules[strtolower($rule)] = $rule;
        }
        return isset($rules[$lowerString]) ? $rules[$lowerString] : $lowerString;
    }

    /**
     * @param Player $player
     * @param Level|null $level
     */
    public static function updateGameRules(Player $player, ?Level $level = null) {
        if($level === null) $level = $player->getLevel();
        $pk = new GameRulesChangedPacket();
        $pk->gameRules = self::getLevelGameRules($level);
        $player->dataPacket($pk);
    }

    /**
     * @return array
     */
    public static function getAllGameRules(): array {
        return array_keys(self::getDefaultGameRules());
    }

    /**
     * @param Level $level
     * @param array $gameRules
     */
    public static function handleGameRuleChange(Level $level, array $gameRules) {
        foreach ($gameRules as $gameRule => [$valueType, $value]) {
            switch ($gameRule) {
                case "doDaylightCycle":
                    $level->setTime(0);
                    $level->stopTime = !$value;
                    continue;
            }
        }
    }

    /**
     * @param bool|int|string $value
     *
     * @return string
     */
    private static function getStringFromValue($value): string {
        if(is_bool($value)) {
            return $value ? "true" : "false";
        }
        return (string) $value;
    }

    /**
     * @param string $string
     *
     * @return bool|int|string
     */
    private static function getValueFromString(string $string) {
        switch ($string) {
            case "true":
                return true;
            case "false":
                return false;
            default:
                if(is_numeric($string)) {
                    return (int) $string;
                }
                else {
                    return (string) $string;
                }
        }
    }
}