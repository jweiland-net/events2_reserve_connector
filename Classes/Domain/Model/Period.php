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
 *  Add "events2Event" property to period model of EXT:reserve
 *  Please keep FQCN as EXT:extender will not migrate the "use" statement into the proxy classes
 */
class Period extends \JWeiland\Reserve\Domain\Model\Period
{
    protected ?\JWeiland\Events2\Domain\Model\Event $events2Event = null;

    public function getEvents2Event(): ?\JWeiland\Events2\Domain\Model\Event
    {
        return $this->events2Event;
    }

    public function setEvents2Event(\JWeiland\Events2\Domain\Model\Event $events2Event): void
    {
        $this->events2Event = $events2Event;
    }
}
