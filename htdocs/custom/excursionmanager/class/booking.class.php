<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once __DIR__ . '/departure.class.php';

/**
 * Booking model mapping invoice lines to departures.
 */
class Booking extends CommonObject
{
    public $table_element = 'exc_booking';
    public $element = 'excbooking';
    public $ismultientitymanaged = 1;
    public $isextrafieldmanaged = 1;

    public $fields = array(
        'ref' => array('type' => 'varchar(128)', 'label' => 'Reference', 'enabled' => 1, 'position' => 10),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 20),
        'fk_facture' => array('type' => 'integer', 'label' => 'Invoice', 'enabled' => 1, 'position' => 30),
        'fk_facturedet' => array('type' => 'integer', 'label' => 'InvoiceLine', 'enabled' => 1, 'position' => 40),
        'fk_departure' => array('type' => 'integer', 'label' => 'Departure', 'enabled' => 1, 'position' => 50),
        'qty' => array('type' => 'integer', 'label' => 'Quantity', 'enabled' => 1, 'position' => 60),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 70),
        'date_booking' => array('type' => 'timestamp', 'label' => 'DateBooking', 'enabled' => 1, 'position' => 80),
        'note_public' => array('type' => 'text', 'label' => 'PublicNote', 'enabled' => 1, 'position' => 90),
    );

    public $rowid;
    public $ref;
    public $entity;
    public $fk_facture;
    public $fk_facturedet;
    public $fk_departure;
    public $qty;
    public $status;
    public $date_booking;
    public $note_public;

    const STATUS_DRAFT = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELED = -1;

    /**
     * Confirm booking and update departure capacity in transaction.
     *
     * @param Departure $departure
     * @param User      $user
     * @param int       $mode        Trigger context
     *
     * @return int  <0 if KO, >0 if OK
     */
    public function confirm(Departure $departure, $user, $mode = 0)
    {
        $this->db->begin();

        if (!$departure->bookSeats($this->qty)) {
            $this->db->rollback();

            return -1;
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX . "exc_departure SET capacity_used = capacity_used + " . ((int) $this->qty) .
            ' WHERE rowid = ' . ((int) $this->fk_departure);

        if ($this->db->query($sql) <= 0) {
            $this->db->rollback();

            return -2;
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->date_booking = dol_now();

        $result = $this->update($user, true);
        if ($result <= 0) {
            $this->db->rollback();

            return -3;
        }

        $this->db->commit();

        return 1;
    }
}
