<?php

/**
 * MultiWorld - PocketMine plugin that manages worlds.
 * Copyright (C) 2018 - 2020  CzechPMDevs
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

namespace czechpmdevs\multiworld\generator\normal\biome;

use czechpmdevs\multiworld\generator\normal\biome\types\Biome;
use czechpmdevs\multiworld\generator\normal\object\Tree;
use czechpmdevs\multiworld\generator\normal\populator\impl\PlantPopulator;
use czechpmdevs\multiworld\generator\normal\populator\impl\TreePopulator;
use czechpmdevs\multiworld\generator\normal\populator\object\Plant;
use pocketmine\block\BlockIds;
use pocketmine\block\BrownMushroom;
use pocketmine\block\Dirt;
use pocketmine\block\Mycelium;
use pocketmine\block\RedMushroom;

/**
 * Class MushroomIsland
 * @package czechpmdevs\multiworld\generator\normal\biome
 */
class MushroomIsland extends Biome {

    /**
     * MushroomIsland constructor.
     */
    public function __construct() {
        parent::__construct(0.9, 1);
        $this->setGroundCover([
            new Mycelium(),
            new Dirt(),
            new Dirt(),
            new Dirt()

        ]);

        $mushrooms = new PlantPopulator(5, 4, 95);
        $mushrooms->addPlant(new Plant(new RedMushroom()));
        $mushrooms->addPlant(new Plant(new BrownMushroom()));
        $mushrooms->allowBlockToStayAt(BlockIds::MYCELIUM);

        $this->addPopulators([$mushrooms, new TreePopulator(1, 1, 100, Tree::MUSHROOM)]);

        $this->setElevation(64, 74);
    }

    /**
     * @return string
     */
    public function getName(): string {
        return "Mushroom Island";
    }
}