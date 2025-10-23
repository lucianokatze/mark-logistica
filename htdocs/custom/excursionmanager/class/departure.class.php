<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Departure model for excursion departures.
 */
class Departure extends CommonObject
{
    public $table_element = 'exc_departure';
    public $element = 'excdeparture';
    public $ismultientitymanaged = 1;
    public $isextrafieldmanaged = 1;

    public $fields = array(
        'ref' => array('type' => 'varchar(128)', 'label' => 'Reference', 'enabled' => 1, 'position' => 10),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 30),
        'date_departure' => array('type' => 'timestamp', 'label' => 'DepartureDate', 'enabled' => 1, 'position' => 40),
        'date_return' => array('type' => 'timestamp', 'label' => 'ReturnDate', 'enabled' => 1, 'position' => 50),
        'fk_soc_vehicle' => array('type' => 'integer', 'label' => 'VehicleSoc', 'enabled' => 1, 'position' => 60),
        'fk_soc_guide' => array('type' => 'integer', 'label' => 'GuideSoc', 'enabled' => 1, 'position' => 70),
        'capacity_total' => array('type' => 'integer', 'label' => 'CapacityTotal', 'enabled' => 1, 'position' => 80),
        'capacity_used' => array('type' => 'integer', 'label' => 'CapacityUsed', 'enabled' => 1, 'position' => 90),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 100),
    );

    public $rowid;
    public $ref;
    public $label;
    public $entity;
    public $date_departure;
    public $date_return;
    public $fk_soc_vehicle;
    public $fk_soc_guide;
    public $capacity_total;
    public $capacity_used;
    public $status;

    const STATUS_DRAFT = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CLOSED = 9;

    /**
     * Increase capacity used.
     *
     * @param int $quantity
     * @return bool
     */
    public function bookSeats($quantity)
    {
        if ($quantity <= 0) {
            return true;
        }

        if ($this->capacity_used + $quantity > $this->capacity_total) {
            return false;
        }

        $this->capacity_used += $quantity;

        return true;
    }

    /**
     * Release seats from the departure.
     *
     * @param int $quantity
     * @return void
     */
    public function releaseSeats($quantity)
    {
        $this->capacity_used = max(0, (int) $this->capacity_used - (int) $quantity);
    }
}
