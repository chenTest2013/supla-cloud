<?php
/*
 Copyright (C) AC SOFTWARE SP. Z O.O.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace SuplaBundle\Enums;

use MyCLabs\Enum\Enum;
use SuplaBundle\Entity\IODeviceChannel;
use SuplaBundle\Exception\ApiException;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @method static ChannelFunction NONE()
 * @method static ChannelFunction CONTROLLINGTHEGATEWAYLOCK()
 * @method static ChannelFunction CONTROLLINGTHEGATE()
 * @method static ChannelFunction CONTROLLINGTHEGARAGEDOOR()
 * @method static ChannelFunction THERMOMETER()
 * @method static ChannelFunction HUMIDITY()
 * @method static ChannelFunction HUMIDITYANDTEMPERATURE()
 * @method static ChannelFunction OPENINGSENSOR_GATEWAY()
 * @method static ChannelFunction OPENINGSENSOR_GATE()
 * @method static ChannelFunction OPENINGSENSOR_GARAGEDOOR()
 * @method static ChannelFunction NOLIQUIDSENSOR()
 * @method static ChannelFunction CONTROLLINGTHEDOORLOCK()
 * @method static ChannelFunction OPENINGSENSOR_DOOR()
 * @method static ChannelFunction CONTROLLINGTHEROLLERSHUTTER()
 * @method static ChannelFunction OPENINGSENSOR_ROLLERSHUTTER()
 * @method static ChannelFunction POWERSWITCH()
 * @method static ChannelFunction LIGHTSWITCH()
 * @method static ChannelFunction DIMMER()
 * @method static ChannelFunction RGBLIGHTING()
 * @method static ChannelFunction DIMMERANDRGBLIGHTING()
 * @method static ChannelFunction DEPTHSENSOR()
 * @method static ChannelFunction DISTANCESENSOR()
 * @method static ChannelFunction OPENINGSENSOR_WINDOW()
 * @method static ChannelFunction MAILSENSOR()
 * @method static ChannelFunction WINDSENSOR()
 * @method static ChannelFunction PRESSURESENSOR()
 * @method static ChannelFunction RAINSENSOR()
 * @method static ChannelFunction WEIGHTSENSOR()
 * @method static ChannelFunction WEATHER_STATION()
 * @method static ChannelFunction STAIRCASETIMER()
 * @method static ChannelFunction ELECTRICITYMETER()
 * @method static ChannelFunction GASMETER()
 * @method static ChannelFunction WATERMETER()
 */
final class ChannelFunction extends Enum {
    const NONE = 0;
    const CONTROLLINGTHEGATEWAYLOCK = 10;
    const CONTROLLINGTHEGATE = 20;
    const CONTROLLINGTHEGARAGEDOOR = 30;
    const THERMOMETER = 40;
    const HUMIDITY = 42;
    const HUMIDITYANDTEMPERATURE = 45;
    const OPENINGSENSOR_GATEWAY = 50;
    const OPENINGSENSOR_GATE = 60;
    const OPENINGSENSOR_GARAGEDOOR = 70;
    const NOLIQUIDSENSOR = 80;
    const CONTROLLINGTHEDOORLOCK = 90;
    const OPENINGSENSOR_DOOR = 100;
    const CONTROLLINGTHEROLLERSHUTTER = 110;
    const OPENINGSENSOR_ROLLERSHUTTER = 120;
    const POWERSWITCH = 130;
    const LIGHTSWITCH = 140;
    const DIMMER = 180;
    const RGBLIGHTING = 190;
    const DIMMERANDRGBLIGHTING = 200;
    const DEPTHSENSOR = 210;
    const DISTANCESENSOR = 220;
    const OPENINGSENSOR_WINDOW = 230;
    const MAILSENSOR = 240;
    const WINDSENSOR = 250;
    const PRESSURESENSOR = 260;
    const RAINSENSOR = 270;
    const WEIGHTSENSOR = 280;
    const WEATHER_STATION = 290;
    const STAIRCASETIMER = 300;
    const ELECTRICITYMETER = 310;
    const GASMETER = 320;
    const WATERMETER = 330;

    /** @Groups({"basic"}) */
    public function getId(): int {
        return $this->value;
    }

    /** @Groups({"basic"}) */
    public function getName(): string {
        return $this->getKey();
    }

    /** @Groups({"basic"}) */
    public function getCaption(): string {
        return self::captions()[$this->value] ?? 'Unknown';
    }

    /**
     * @Groups({"basic"})
     * @return ChannelFunctionAction[]
     */
    public function getPossibleActions(): array {
        return self::actions()[$this->getValue()] ?? [];
    }

    /**
     * @Groups({"basic"})
     * @return string[]
     */
    public function getPossibleVisualStates(): array {
        return self::possibleVisualStates()[$this->getValue()] ?? [];
    }

    /**
     * @param IODeviceChannel $channel
     * @return ChannelFunction[]
     */
    public static function forChannel(IODeviceChannel $channel): array {
        $type = $channel->getType();
        if ($type->equals(ChannelType::RELAY())) {
            return RelayFunctionBits::getSupportedFunctions($channel->getFuncList());
        } else {
            return ChannelType::functions()[$type->getValue()];
        }
    }

    /** @Groups({"basic"}) */
    public function getMaxAlternativeIconIndex(): int {
        return self::maxAlternativeIconIndexes()[$this->getValue()] ?? 0;
    }

    public static function actions(): array {
        return [
            self::CONTROLLINGTHEGATEWAYLOCK => [ChannelFunctionAction::OPEN()],
            self::CONTROLLINGTHEDOORLOCK => [ChannelFunctionAction::OPEN()],
            self::CONTROLLINGTHEGATE => [
                ChannelFunctionAction::OPEN(),
                ChannelFunctionAction::CLOSE(),
                ChannelFunctionAction::OPEN_CLOSE(),
            ],
            self::CONTROLLINGTHEGARAGEDOOR => [
                ChannelFunctionAction::OPEN(),
                ChannelFunctionAction::CLOSE(),
                ChannelFunctionAction::OPEN_CLOSE(),
            ],
            self::CONTROLLINGTHEROLLERSHUTTER => [
                ChannelFunctionAction::SHUT(),
                ChannelFunctionAction::REVEAL(),
                ChannelFunctionAction::REVEAL_PARTIALLY(),
            ],
            self::POWERSWITCH => [ChannelFunctionAction::TURN_ON(), ChannelFunctionAction::TURN_OFF()],
            self::LIGHTSWITCH => [ChannelFunctionAction::TURN_ON(), ChannelFunctionAction::TURN_OFF()],
            self::STAIRCASETIMER => [ChannelFunctionAction::TURN_ON(), ChannelFunctionAction::TURN_OFF()],
            self::DIMMER => [ChannelFunctionAction::SET_RGBW_PARAMETERS()],
            self::RGBLIGHTING => [ChannelFunctionAction::SET_RGBW_PARAMETERS()],
            self::DIMMERANDRGBLIGHTING => [ChannelFunctionAction::SET_RGBW_PARAMETERS()],
        ];
    }

    public static function captions(): array {
        return [
            self::NONE => 'None',
            self::CONTROLLINGTHEGATEWAYLOCK => 'Gateway lock operation',
            self::CONTROLLINGTHEGATE => 'Gate operation',
            self::CONTROLLINGTHEGARAGEDOOR => 'Garage door operation',
            self::THERMOMETER => 'Thermometer',
            self::OPENINGSENSOR_GATEWAY => 'Gateway opening sensor',
            self::OPENINGSENSOR_GATE => 'Gate opening sensor',
            self::OPENINGSENSOR_GARAGEDOOR => 'Garage door opening sensor',
            self::NOLIQUIDSENSOR => 'No liquid sensor',
            self::CONTROLLINGTHEDOORLOCK => 'Door lock operation',
            self::OPENINGSENSOR_DOOR => 'Door opening sensor',
            self::CONTROLLINGTHEROLLERSHUTTER => 'Roller shutter operation',
            self::OPENINGSENSOR_ROLLERSHUTTER => 'Roller shutter opening sensor',
            self::POWERSWITCH => 'On/Off switch',
            self::LIGHTSWITCH => 'Light switch',
            self::HUMIDITY => 'Humidity sensor',
            self::HUMIDITYANDTEMPERATURE => 'Temperature and humidity sensor',
            self::DIMMER => 'Dimmer',
            self::RGBLIGHTING => 'RGB lighting',
            self::DIMMERANDRGBLIGHTING => 'Dimmer and RGB lighting',
            self::DISTANCESENSOR => 'Distance sensor',
            self::DEPTHSENSOR => 'Depth sensor',
            self::OPENINGSENSOR_WINDOW => 'Window opening sensor',
            self::MAILSENSOR => 'Mail sensor',
            self::WINDSENSOR => 'Wind sensor',
            self::PRESSURESENSOR => 'Pressure sensor',
            self::RAINSENSOR => 'Rain sensor',
            self::WEIGHTSENSOR => 'Weight sensor',
            self::WEATHER_STATION => 'Weather Station',
            self::STAIRCASETIMER => 'Staircase timer',
            self::ELECTRICITYMETER => 'Electricity meter',
            self::GASMETER => 'Gas meter',
            self::WATERMETER => 'Water meter',
        ];
    }

    public static function maxAlternativeIconIndexes(): array {
        return [
            self::POWERSWITCH => 4,
            self::LIGHTSWITCH => 1,
            self::CONTROLLINGTHEGATE => 2,
            self::OPENINGSENSOR_GATE => 2,
            self::STAIRCASETIMER => 1,
        ];
    }

    public static function possibleVisualStates(): array {
        return [
            self::NONE => [],
            self::CONTROLLINGTHEGATEWAYLOCK => ['opened', 'closed'],
            self::CONTROLLINGTHEGATE => ['opened', 'closed', 'partially_closed'],
            self::CONTROLLINGTHEGARAGEDOOR => ['opened', 'closed'],
            self::THERMOMETER => ['default'],
            self::OPENINGSENSOR_GATEWAY => ['opened', 'closed'],
            self::OPENINGSENSOR_GATE => ['opened', 'partially_closed', 'closed'],
            self::OPENINGSENSOR_GARAGEDOOR => ['opened', 'closed'],
            self::NOLIQUIDSENSOR => ['empty', 'full'],
            self::CONTROLLINGTHEDOORLOCK => ['opened', 'closed'],
            self::OPENINGSENSOR_DOOR => ['opened', 'closed'],
            self::CONTROLLINGTHEROLLERSHUTTER => ['revealed', 'shut'],
            self::OPENINGSENSOR_ROLLERSHUTTER => ['revealted', 'shut'],
            self::POWERSWITCH => ['on', 'off'],
            self::LIGHTSWITCH => ['on', 'off'],
            self::HUMIDITY => ['default'],
            self::HUMIDITYANDTEMPERATURE => ['default'],
            self::DIMMER => ['on', 'off'],
            self::RGBLIGHTING => ['on', 'off'],
            self::DIMMERANDRGBLIGHTING => ['rgb_on_dim_on', 'rgb_on_dim_off', 'rgb_off_dim_on', 'rgb_off_dim_off'],
            self::DISTANCESENSOR => ['default'],
            self::DEPTHSENSOR => ['default'],
            self::OPENINGSENSOR_WINDOW => ['opened', 'closed'],
            self::MAILSENSOR => ['empty', 'full'],
            self::WINDSENSOR => ['default'],
            self::PRESSURESENSOR => ['default'],
            self::RAINSENSOR => ['empty', 'full'],
            self::WEIGHTSENSOR => ['default'],
            self::WEATHER_STATION => ['default'],
            self::STAIRCASETIMER => ['on', 'off'],
            self::ELECTRICITYMETER => ['default'],
            self::GASMETER => ['default'],
            self::WATERMETER => ['default'],
        ];
    }

    public static function fromString(string $functionName): ChannelFunction {
        $functionName = trim($functionName);
        try {
            if (is_numeric($functionName)) {
                return new self((int)$functionName);
            } else {
                $functionName = strtoupper($functionName);
                return self::$functionName();
            }
        } catch (\RuntimeException $e) {
            throw new ApiException('Invalid function given: ' . $functionName, 400, $e);
        }
    }

    /**
     * @param string[] $functionNames
     * @return ChannelFunction[]
     */
    public static function fromStrings(array $functionNames): array {
        return array_map(self::class . '::fromString', $functionNames);
    }
}
