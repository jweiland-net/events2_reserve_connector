<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2-reserve-connector.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2ReserveConnector\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Class Event
 */
class Event extends \JWeiland\Events2\Domain\Model\Event
{
    /**
     * Add NotEmpty validator
     *
     * @var ?\JWeiland\Events2\Domain\Model\Location
     * @Extbase\Validate("NotEmpty")
     */
    protected ?\JWeiland\Events2\Domain\Model\Location $location;

    /**
     * Add NotEmpty validator
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\JWeiland\Events2\Domain\Model\Organizer>
     * @Extbase\Validate("NotEmpty")
     * @Extbase\ORM\Lazy
     */
    protected \TYPO3\CMS\Extbase\Persistence\ObjectStorage $organizers;

    protected \DateTime $releaseDate;

    protected string $socialTeaser = '';

    protected string $theaterDetails = '';

    protected ?\DateTime $deadline = null;

    protected bool $registrationRequired = false;

    public function getReleaseDate(): ?\DateTime
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(\DateTime $releaseDate = null): void
    {
        $this->releaseDate = $releaseDate;
    }

    public function getSocialTeaser(): string
    {
        return $this->socialTeaser;
    }

    public function setSocialTeaser(string $socialTeaser): void
    {
        $this->socialTeaser = $socialTeaser;
    }

    public function getTheaterDetails(): string
    {
        return $this->theaterDetails;
    }

    public function setTheaterDetails(string $theaterDetails): void
    {
        $this->theaterDetails = $theaterDetails;
    }

    public function getDeadline(): ?\DateTime
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTime $deadline = null): void
    {
        $this->deadline = $deadline;
    }

    public function getRegistrationRequired(): bool
    {
        return $this->registrationRequired;
    }

    public function setRegistrationRequired(bool $registrationRequired): void
    {
        $this->registrationRequired = $registrationRequired;
    }
}
