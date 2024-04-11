<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2-reserve-connector.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2ReserveConnector\Domain\Model;

/**
 * Add "facitity" property to location model of EXT:events2
 * Please keep FQCN as EXT:extender will not migrate the "use" statement into the proxy classes
 */
class Location extends \JWeiland\Events2\Domain\Model\Location
{
    protected ?\JWeiland\Reserve\Domain\Model\Facility $facility = null;

    public function getFacility(): ?\JWeiland\Reserve\Domain\Model\Facility
    {
        return $this->facility;
    }

    public function setFacility(?\JWeiland\Reserve\Domain\Model\Facility $facility = null): void
    {
        $this->facility = $facility;
    }
}
