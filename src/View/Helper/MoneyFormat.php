<?php

namespace ConferenceTools\Tickets\View\Helper;

use ConferenceTools\Tickets\Domain\ValueObject\Money;
use ConferenceTools\Tickets\Domain\ValueObject\Price;
use Zend\View\Helper\AbstractHelper;

class MoneyFormat extends AbstractHelper
{
    /**
     * @param Money $money
     * @param bool $useNet
     * @return string
     */
    public function __invoke($money, $useNet = false)
    {
        if ($money instanceof Price) {
            if ($useNet) {
                $money = $money->getNet();
            } else {
                $money = $money->getGross();
            }
        }
        $currencyFormat = $this->getView()->plugin('currencyFormat');

        return $currencyFormat($money->getAmount() / 100, $money->getCurrency());
    }
}
