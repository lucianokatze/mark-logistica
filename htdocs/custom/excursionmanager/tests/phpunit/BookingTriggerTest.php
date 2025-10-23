<?php
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once __DIR__ . '/../../class/booking.class.php';
require_once __DIR__ . '/../../class/departure.class.php';

class BookingTriggerTest extends TestCase
{
    public function testDepartureCapacityCheck()
    {
        global $db;
        $departure = new Departure($db);
        $departure->capacity_total = 10;
        $departure->capacity_used = 5;

        $booking = new Booking($db);
        $booking->qty = 6;

        $this->assertFalse($departure->bookSeats($booking->qty));
        $this->assertSame(5, $departure->capacity_used);
    }
}
