<?php
namespace SuplaBundle\Model\ChannelParamsUpdater;

use SuplaBundle\Enums\ChannelFunction;

class OpeningGarageDoorSensorRelatedChannel extends OpeningAnySensorRelatedChannel {
    public function __construct() {
        parent::__construct(ChannelFunction::CONTROLLINGTHEGARAGEDOOR(), ChannelFunction::OPENINGSENSOR_GARAGEDOOR());
    }
}
