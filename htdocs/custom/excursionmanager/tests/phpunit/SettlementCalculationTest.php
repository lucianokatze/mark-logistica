<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/excursionmanager.lib.php';

class SettlementCalculationTest extends TestCase
{
    protected function setUp(): void
    {
        global $conf;
        if (!isset($conf)) {
            $conf = new stdClass();
        }
        if (!isset($conf->global)) {
            $conf->global = new stdClass();
        }
    }

    public function testPerPaxStrategy()
    {
        global $conf;
        $conf->global->EXCUR_SETTLEMENT_STRATEGY = 'PER_PAX';

        $line = (object) array('qty' => 5, 'subprice' => 20);
        $this->assertSame(100.0, excursionmanager_calculate_obligation_amount($line));
    }

    public function testPerDepartureStrategy()
    {
        global $conf;
        $conf->global->EXCUR_SETTLEMENT_STRATEGY = 'PER_DEPARTURE';

        $line = (object) array('total_ht' => 150);
        $this->assertSame(150.0, excursionmanager_calculate_obligation_amount($line));
    }

    public function testPerSaleStrategy()
    {
        global $conf;
        $conf->global->EXCUR_SETTLEMENT_STRATEGY = 'PER_SALE';

        $line = (object) array('total_ttc' => 210.5);
        $this->assertSame(210.5, excursionmanager_calculate_obligation_amount($line));
    }
}
