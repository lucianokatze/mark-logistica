<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Excursion aggregate object.
 */
class Excursion extends CommonObject
{
    public $table_element = 'exc_departure';
    public $element = 'excursion';
    public $fk_element = 'fk_departure';
    public $isextrafieldmanaged = 1;

    public $fields = array(
        'ref' => array('type' => 'varchar(128)', 'label' => 'Reference', 'enabled' => 1, 'position' => 10),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 30),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 40),
        'date_departure' => array('type' => 'timestamp', 'label' => 'DepartureDate', 'enabled' => 1, 'position' => 50),
        'capacity_total' => array('type' => 'integer', 'label' => 'CapacityTotal', 'enabled' => 1, 'position' => 60),
        'capacity_used' => array('type' => 'integer', 'label' => 'CapacityUsed', 'enabled' => 1, 'position' => 70),
        'fk_soc_vehicle' => array('type' => 'integer', 'label' => 'Vehicle', 'enabled' => 1, 'position' => 80),
        'fk_user_guide' => array('type' => 'integer', 'label' => 'Guide', 'enabled' => 1, 'position' => 90),
    );

    public $rowid;
    public $ref;
    public $label;
    public $entity;
    public $status;
    public $date_departure;
    public $capacity_total;
    public $capacity_used;
    public $fk_soc_vehicle;
    public $fk_user_guide;

    /**
     * Validate available capacity before committing booking changes.
     *
     * @param int $requestedCapacity
     * @return bool
     */
    public function hasCapacityFor($requestedCapacity)
    {
        $capacityAvailable = max(0, (int) $this->capacity_total - (int) $this->capacity_used);

        return $requestedCapacity <= $capacityAvailable;
    }
}
