<?php

declare(strict_types=1);

namespace Test\Orm\Integration;

use DateTimeImmutable;
use Test\Orm\Config\IntegrationTestCase;
use Test\Orm\Fixture\Entity\Amount;
use Test\Orm\Fixture\Entity\Payment;
use Test\Orm\Fixture\Entity\PaymentStatus;

class ChildEntityTest extends IntegrationTestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = new DateTimeImmutable('2020-09-14 22:25:30');
    }

    public function testRetrievePaymentWithStatus(): void
    {
        $id = 'payment-xxx';
        $payment = new Payment($id, new Amount(100, 'EUR'), new PaymentStatus($id, 'opened', $this->now));

        $this->em->getRepository(Payment::class)->insert($payment);
        $this->em->getRepository(PaymentStatus::class)->insert($payment->getStatus());

        $this->assertEquals(
            new Payment(
                $id,
                new Amount(100, 'EUR'),
                new PaymentStatus($id, 'opened', $this->now),
            ),
            $this->em->getRepository(Payment::class)->loadById($id),
        );
    }
}
