<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Settlement object handles excursion obligations and manual payments.
 */
class Settlement extends CommonObject
{
    /** @var string */
    public $table_element = 'exc_obligation';

    /** @var string */
    public $element = 'excobligation';

    /** @var int */
    public $ismultientitymanaged = 1;

    /** @var array */
    public $fields = array(
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10),
        'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 20),
        'fk_thirdparty' => array('type' => 'integer', 'label' => 'Thirdparty', 'enabled' => 1, 'position' => 30),
        'fk_departure' => array('type' => 'integer', 'label' => 'Departure', 'enabled' => 1, 'position' => 40),
        'type_service' => array('type' => 'varchar(64)', 'label' => 'ServiceType', 'enabled' => 1, 'position' => 50),
        'amount_due' => array('type' => 'double(24,8)', 'label' => 'AmountDue', 'enabled' => 1, 'position' => 60),
        'amount_paid' => array('type' => 'double(24,8)', 'label' => 'AmountPaid', 'enabled' => 1, 'position' => 70),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'position' => 80),
        'date_creation' => array('type' => 'timestamp', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 90),
        'date_valid' => array('type' => 'timestamp', 'label' => 'DateValid', 'enabled' => 1, 'position' => 100),
        'fk_user_valid' => array('type' => 'integer', 'label' => 'UserValid', 'enabled' => 1, 'position' => 110),
        'notes' => array('type' => 'text', 'label' => 'Notes', 'enabled' => 1, 'position' => 120),
    );

    /** @var int */
    public $rowid;

    /** @var string */
    public $ref;

    /** @var int */
    public $entity;

    /** @var int */
    public $fk_thirdparty;

    /** @var int */
    public $fk_departure;

    /** @var string */
    public $type_service;

    /** @var float */
    public $amount_due;

    /** @var float */
    public $amount_paid;

    /** @var int */
    public $status;

    /** @var int */
    public $fk_user_valid;

    /** @var int */
    public $date_creation;

    /** @var int */
    public $date_valid;

    /** @var string */
    public $notes;

    const STATUS_PENDING = 0;
    const STATUS_PARTIALLY_PAID = 1;
    const STATUS_PAID = 2;
    const STATUS_CANCELLED = -1;

    /**
     * Load obligation by third, departure and service type.
     *
     * @param int    $thirdpartyId
     * @param int    $departureId
     * @param string $serviceType
     *
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetchByThirdDeparture($thirdpartyId, $departureId, $serviceType, $entity = null)
    {
        $sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "exc_obligation WHERE fk_thirdparty = " . ((int) $thirdpartyId)
            . ' AND type_service = \'' . $this->db->escape($serviceType) . '\''
            . ' AND status <> ' . self::STATUS_CANCELLED;

        if (!empty($entity)) {
            $sql .= ' AND entity = ' . ((int) $entity);
        }

        if (!empty($departureId)) {
            $sql .= ' AND fk_departure = ' . ((int) $departureId);
        } else {
            $sql .= ' AND fk_departure IS NULL';
        }

        $sql .= ' ORDER BY rowid DESC LIMIT 1';

        $resql = $this->db->query($sql);
        if (!$resql) {
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        if (!$obj) {
            return 0;
        }

        return $this->fetch($obj->rowid);
    }

    /**
     * Add amount to the obligation and adjust status.
     *
     * @param float $amount
     * @param User  $user
     *
     * @return int
     */
    public function increaseAmount($amount, $user)
    {
        $this->amount_due = round((float) $this->amount_due + (float) $amount, 8);
        if ($this->amount_due < 0) {
            $this->amount_due = 0.0;
        }
        if ($this->amount_paid >= $this->amount_due) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } else {
            $this->status = self::STATUS_PENDING;
        }

        return $this->update($user);
    }

    /**
     * Record a manual payment for the obligation.
     *
     * @param User $user
     * @param float $amount
     * @param int $fk_bankaccount
     * @param int $datePayment Timestamp
     * @param string $notes
     *
     * @return int
     */
    public function recordPayment($user, $amount, $fk_bankaccount, $datePayment, $notes = '')
    {
        if ($amount <= 0) {
            return 0;
        }

        $this->db->begin();

        $sql = 'INSERT INTO ' . MAIN_DB_PREFIX . "exc_payment_line (entity, fk_obligation, fk_bankaccount, amount, date_payment, notes, fk_user_author) VALUES ("
            . ((int) $this->entity) . ', '
            . ((int) $this->id) . ', '
            . ((int) $fk_bankaccount ?: 'NULL') . ', '
            . price2num($amount, 'MT') . ', '
            . ($datePayment ? "'" . $this->db->idate($datePayment) . "'" : 'NULL') . ', '
            . ($notes !== '' ? "'" . $this->db->escape($notes) . "'" : 'NULL') . ', '
            . ((int) $user->id) . ')';

        if ($this->db->query($sql) <= 0) {
            $this->db->rollback();

            return -1;
        }

        $this->amount_paid = round((float) $this->amount_paid + (float) $amount, 8);
        if ($this->amount_paid >= $this->amount_due) {
            $this->status = self::STATUS_PAID;
            $this->date_valid = $datePayment ?: dol_now();
            $this->fk_user_valid = $user->id;
        } else {
            $this->status = self::STATUS_PARTIALLY_PAID;
        }

        $res = $this->update($user);
        if ($res <= 0) {
            $this->db->rollback();

            return -2;
        }

        $this->db->commit();

        return 1;
    }

    /**
     * Amount remaining to pay.
     *
     * @return float
     */
    public function getRemainingAmount()
    {
        return round((float) $this->amount_due - (float) $this->amount_paid, 8);
    }
}
