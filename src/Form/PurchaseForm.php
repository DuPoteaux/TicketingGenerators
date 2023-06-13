<?php

namespace ConferenceTools\Tickets\Form;

use ConferenceTools\Tickets\Domain\ReadModel\TicketRecord\PurchaseRecord;
use ConferenceTools\Tickets\Domain\ReadModel\TicketRecord\TicketRecord;
use ConferenceTools\Tickets\Domain\ValueObject\Delegate;
use ConferenceTools\Tickets\Form\Fieldset\DelegateInformation;
use ConferenceTools\Tickets\Hydrator\DelegateHydrator;
use Zend\Form\Element\Collection;
use Zend\Form\Element\Csrf;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Text;
use Zend\Form\Form;
use Zend\Validator\EmailAddress;
use Zend\Validator\NotEmpty;

class PurchaseForm extends Form
{
    public function __construct(PurchaseRecord $purchase)
    {
        parent::__construct('delegate-form');
        $this->add(['type' => Hidden::class, 'name' => 'stripe_token']);
        $this->add([
            'type' => Text::class,
            'name' => 'purchase_email',
            'options' => [
                'label' => 'Email',
                'help-block' => 'Your receipt will be emailed to this address'
            ]
        ]);

        foreach ($purchase->getTickets() as $i => $ticket) {
            /** @var TicketRecord $ticket */
            if (!$ticket->getTicketType()->isSupplementary()) {
                $this->add(['type' => DelegateInformation::class, 'name' => 'delegates_' . $i]);
            }
        }

        $this->add(new Csrf('security'));

        $this->getInputFilter()
            ->get('purchase_email')
            ->setAllowEmpty(false)
            ->setRequired(true)
            ->getValidatorChain()
            ->attach(new NotEmpty())
            ->attach(new EmailAddress());

    }
}