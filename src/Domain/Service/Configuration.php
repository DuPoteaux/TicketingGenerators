<?php

namespace ConferenceTools\Tickets\Domain\Service;

use ConferenceTools\Tickets\Domain\ValueObject\DiscountCode;
use ConferenceTools\Tickets\Domain\ValueObject\DiscountCodeMetadata;
use ConferenceTools\Tickets\Domain\ValueObject\Money;
use ConferenceTools\Tickets\Domain\ValueObject\Price;
use ConferenceTools\Tickets\Domain\ValueObject\TaxRate;
use ConferenceTools\Tickets\Domain\ValueObject\TicketMetadata;
use ConferenceTools\Tickets\Domain\ValueObject\TicketType;
use Zend\Stdlib\ArrayUtils;

class Configuration
{
    //@TODO change to private const
    private static $defaults = [
        'tickets' => [],
        'discountCodes' => [],
        'financial' => [
            'currency' => 'GBP',
            'taxRate' => 0,
            'displayTax' => false
        ]
    ];

    /**
     * Defines the currency in use across the app.
     *
     * Defaults to GBP, should be a proper currency code otherwise you will have issues with display of
     * currency values and creating stripe charges.
     *
     * config key: financial->currency
     *
     * @var string
     */
    private $currency;

    /**
     * The tax rate in use across the app.
     *
     * Defaults to 0% Will be added to all cost values for tickets. The app assumes a single tax rate for all tickets
     * and also assumes that tickets are for a physical event. As such the EU VATMOSS rules do not apply. If you are
     * selling tickets for a webinar or online conference, you should check with legal advisers if this is appropriate.
     *
     * config key: financial->taxRate
     *
     * @var TaxRate
     */
    private $taxRate;

    /**
     * Should VAT/sales tax be displayed in the app.
     *
     * Defaults to false. If enabled, this will display sales tax (VAT) in various points in the purchase process. You
     * should also update the layout template to include the relevent legal information. There are three ways this app
     * can deal with tax:
     * - if you are not vat registered or do not need to charge VAT, you can set this to false and the tax rate to 0;
     *   the app will not track any tax for you.
     * - if you set a tax rate but disable this flag, VAT will be added to purchases and tracked by the app but not
     *   made visible to customers. The main purpose of this is for when you have a pending VAT registration; the app
     *   will still track the tax and you can turn on the display of this tracking when the registration completes.
     *   Another use for this, if you don't need to track VAT would be to add a handling/processing fee to tickets.
     * - If you set a tax rate and enable this flag, VAT will be tracked and displayed to your customers at purchase
     *   time.
     *
     *
     * config key: financial->displayTax
     *
     * @var bool
     */
    private $displayTax;

    /**
     * An array of available ticket types. The app uses this for determining how many tickets are available and their
     * prices.
     *
     * If you change this config, you will need to rebuild the ticket counters projection to get the updated types in
     * your app.
     *
     * config key: tickets
     * structure: identifier => [
     *      'cost' => Net cost in pence/cents (eg before tax price),
     *      'name' => display name shown to customers,
     *      'available' => Number available for purchase
     * ]
     *
     * @var TicketType[]
     */
    private $ticketTypes;

    /**
     * Holds a count of avaliable tickets by type.
     *
     * @see ticketTypes for how to configure
     *
     * @var int[]
     */
    private $avaliableTickets;

    /**
     * An array of discount codes. The app uses this for validating and applying different codes.
     *
     * If you change this config, you will need to rebuild the discount codes projection
     *
     * configkey: discountCodes
     * structure: identifier => [
     *      'type' => The class name of the discount type eg Percentage::class,
     *      'name' => User friendly name for the code
     *      'options' => An array of options for the type you are using
     * ]
     *
     * @var DiscountCode[]
     */
    private $discountCodes = [];

    /**
     * Contains metadata about tickets eg when they are available for sale
     *
     * configkey: tickets->metadata
     * structure: [
     *      'availableFrom' => DateTime ticket is to go on sale from,
     *      'availableTo' => DateTime after which ticket is no longer on sale
     * ]
     *
     * @var TicketMetadata[]
     */
    private $ticketMetadata;

    /**
     * Contains metadata about discount codes eg when they are available for use
     *
     * configkey: discountCodes->metadata
     * structure: [
     *     'availableFrom' => DateTime code can be used from,
     *     'availableTo' => DateTime code expires at
     * ]
     *
     * @var DiscountCodeMetadata[]
     */
    private $discountCodeMetadata;

    private function __construct() {}

    public static function fromArray(array $settings)
    {
        /** Ensures that all the keys exist @TODO remove dependency on Zend Array Utils for this */
        $settings = ArrayUtils::merge(self::$defaults, $settings);
        $instance = new static();

        $instance->currency = (string) $settings['financial']['currency'];
        $instance->displayTax = (string) $settings['financial']['displayTax'];
        $instance->taxRate = new TaxRate($settings['financial']['taxRate']);

        foreach ($settings['tickets'] as $identifier => $ticket) {
            $instance->addTicketInformation($ticket, $identifier);
        }

        foreach ($settings['discountCodes'] as $identifier => $code) {
            $instance->addDiscountCodeInformation($code, $identifier);

        }

        return $instance;
    }

    /**
     * @param $ticket
     * @param $identifier
     */
    private function addTicketInformation(array $ticket, string $identifier)
    {
        $price = Price::fromNetCost(
            new Money($ticket['cost'], $this->currency),
            $this->taxRate
        );

        $this->ticketTypes[$identifier] = new TicketType(
            $identifier,
            $price,
            $ticket['name'],
            $ticket['description'] ?? '',
            $ticket['supplementary'] ?? false
        );

        $this->avaliableTickets[$identifier] = $ticket['available'];

        $this->ticketMetadata[$identifier] = TicketMetadata::fromArray(
            $this->ticketTypes[$identifier],
            $ticket['metadata'] ?? []
        );
    }

    private function addDiscountCodeInformation(array $code, string $identifier)
    {
        // Be careful here; configuration object is still under constrcution at the time it is passed in
        // Might need to rethink this at some point
        $discountType = call_user_func([$code['type'], 'fromArray'], $code['options'], $this);
        $this->discountCodes[$identifier] = new DiscountCode(
            $identifier,
            $code['name'],
            $discountType
        );

        $this->discountCodeMetadata[$identifier] = DiscountCodeMetadata::fromArray(
            $this->discountCodes[$identifier],
            $code['metadata'] ?? []
        );
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return TaxRate
     */
    public function getTaxRate(): TaxRate
    {
        return $this->taxRate;
    }

    /**
     * @return boolean
     */
    public function displayTax(): bool
    {
        return $this->displayTax;
    }

    /**
     * @return TicketType[]
     */
    public function getTicketTypes(): array
    {
        return $this->ticketTypes;
    }

    /**
     * @return TicketType
     */
    public function getTicketType(string $identifier): TicketType
    {
        return $this->ticketTypes[$identifier];
    }

    /**
     * @param string $identifier
     * @return int
     */
    public function getAvailableTickets(string $identifier): int
    {
        return $this->avaliableTickets[$identifier];
    }

    public function getDiscountCodes(): array
    {
        return $this->discountCodes;
    }

    public function getTicketMetadata(string $identifier): TicketMetadata
    {
        return $this->ticketMetadata[$identifier];
    }

    public function getDiscountCodeMetadata(string $code): DiscountCodeMetadata
    {
        return $this->discountCodeMetadata[$code];
    }
}