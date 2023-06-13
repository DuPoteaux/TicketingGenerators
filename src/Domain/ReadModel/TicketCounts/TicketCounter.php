<?php

namespace ConferenceTools\Tickets\Domain\ReadModel\TicketCounts;

use Doctrine\ORM\Mapping as ORM;
use ConferenceTools\Tickets\Domain\ValueObject\TicketType;

/**
 * Class TicketCounter
 * @package ConferenceTools\Tickets\Domain\ReadModel\TicketCounts
 * @ORM\Entity()
 */
class TicketCounter
{
    /**
     * @var integer
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var TicketType
     * @ORM\Embedded(class="ConferenceTools\Tickets\Domain\ValueObject\TicketType")
     */
    private $ticketType;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $remaining;

    /**
     * TicketCounter constructor.
     * @param TicketType $ticketType
     * @param int $remaining
     */
    public function __construct(TicketType $ticketType, int $remaining)
    {
        $this->ticketType = $ticketType;
        $this->remaining = $remaining;
    }

    /**
     * @return int
     */
    public function getRemaining()
    {
        return $this->remaining;
    }

    /**
     * @return TicketType
     */
    public function getTicketType()
    {
        return $this->ticketType;
    }

    public function ticketsReserved(int $number)
    {
        $this->remaining -= $number;
    }

    public function ticketsReleased(int $number)
    {
        $this->remaining += $number;
    }
}