<?php
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
require_once __DIR__ . '/../../class/booking.class.php';
require_once __DIR__ . '/../../class/departure.class.php';
require_once __DIR__ . '/../../class/settlement.class.php';
require_once __DIR__ . '/../../lib/excursionmanager.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

/**
 * Trigger for Excursion Manager business rules.
 */
class InterfaceAllExcursionmanager extends DolibarrTriggers
{
    /** @var array */
    private $commissionRules = array();

    /** @var array */
    private $thirdpartyCache = array();

    /** @var int */
    private $currentEntity = 1;

    /**
     * @var string
     */
    public $family = 'excursionmanager';

    /**
     * Constructor.
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->description = 'Excursion Manager triggers for automatic bookings and obligations.';
        $this->version = 'dolibarr';
    }

    /**
     * Execute trigger
     *
     * @param string $action Event action
     * @param Object $object Object related to action
     * @param User   $user   User
     * @param Translate $langs Languages
     * @param Conf   $conf   Conf
     *
     * @return int <0 if KO, 0 if OK but nothing done, >0 if OK
     */
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if (empty($conf->excursionmanager->enabled)) {
            return 0;
        }

        switch ($action) {
            case 'BILL_VALIDATE':
                return $this->handleInvoiceValidation($object, $user, $conf);
            case 'BILL_PAYED':
                return $this->handleInvoicePayment($object, $user, $conf);
            case 'BILL_CANCEL':
                return $this->handleInvoiceCancellation($object, $user);
            case 'LINEBILL_DELETE':
            case 'LINEBILL_UPDATE':
                return $this->handleInvoiceLineChange($object, $user);
            case 'DEPARTURE_CLOSE':
                return $this->handleDepartureClose($object, $user, $conf);
            case 'PAYMENT_REGISTER':
                return $this->handlePaymentRegister($object, $user);
        }

        return 0;
    }

    private function handleInvoiceValidation($invoice, $user, $conf)
    {
        if (empty($conf->global->EXCUR_AUTOBOOK_ON) || $conf->global->EXCUR_AUTOBOOK_ON === 'VALIDATE' || $conf->global->EXCUR_AUTOBOOK_ON === 'BOTH') {
            return $this->synchronizeInvoiceLines($invoice, $user, 'validate');
        }

        return 0;
    }

    private function handleInvoicePayment($invoice, $user, $conf)
    {
        if ($conf->global->EXCUR_AUTOBOOK_ON === 'PAYED' || $conf->global->EXCUR_AUTOBOOK_ON === 'BOTH') {
            return $this->synchronizeInvoiceLines($invoice, $user, 'payed');
        }

        return 0;
    }

    private function handleInvoiceCancellation($invoice, $user)
    {
        return $this->releaseInvoiceLines($invoice, $user, 'cancel');
    }

    private function handleInvoiceLineChange($invoiceLine, $user)
    {
        if (!empty($invoiceLine->fk_invoice)) {
            $invoice = new Facture($this->db);
            if ($invoice->fetch($invoiceLine->fk_invoice) > 0) {
                return $this->synchronizeInvoiceLines($invoice, $user, 'update');
            }
        }

        return 0;
    }

    private function synchronizeInvoiceLines($invoice, $user, $context)
    {
        $langs = $GLOBALS['langs'];
        $langs->load('excursionmanager@excursionmanager');

        foreach ($invoice->lines as $line) {
            if (empty($line->array_options['options_fk_departure'])) {
                continue;
            }

            $booking = new Booking($this->db);
            $result = $booking->fetch(null, '', '', '', '', '', array('customsql' => 'fk_facturedet = ' . ((int) $line->id)));
            if ($result <= 0) {
                $booking->fk_facture = $invoice->id;
                $booking->fk_facturedet = $line->id;
                $booking->fk_departure = (int) $line->array_options['options_fk_departure'];
                $booking->qty = (int) $line->qty;
                $booking->entity = $invoice->entity;
                $booking->ref = $invoice->ref . '-' . $line->id;
                $booking->status = Booking::STATUS_DRAFT;
                $booking->create($user);
            }

            $departure = new Departure($this->db);
            if ($departure->fetch($booking->fk_departure) <= 0) {
                continue;
            }

            if ($booking->status != Booking::STATUS_CONFIRMED) {
                $booking->qty = (int) $line->qty;
                $booking->fk_departure = (int) $line->array_options['options_fk_departure'];
                $booking->confirm($departure, $user);
            }
        }

        return 1;
    }

    private function releaseInvoiceLines($invoice, $user, $context)
    {
        foreach ($invoice->lines as $line) {
            $booking = new Booking($this->db);
            if ($booking->fetch(null, '', '', '', '', '', array('customsql' => 'fk_facturedet = ' . ((int) $line->id))) <= 0) {
                continue;
            }

            $departure = new Departure($this->db);
            if ($departure->fetch($booking->fk_departure) > 0) {
                if (method_exists($line, 'fetch_optionals')) {
                    $line->fetch_optionals();
                }
                $departure->releaseSeats($booking->qty);
                $sql = 'UPDATE ' . MAIN_DB_PREFIX . "exc_departure SET capacity_used = capacity_used - " . ((int) $booking->qty) .
                    ' WHERE rowid = ' . ((int) $departure->id);
                $this->db->query($sql);

                $this->updateObligationsForLine($departure, $line, $user, -1);
            }

            $booking->status = Booking::STATUS_CANCELED;
            $booking->update($user);
        }

        return 1;
    }

    private function handleDepartureClose($object, $user, $conf)
    {
        $departure = $object instanceof Departure ? $object : new Departure($this->db);
        if (!($object instanceof Departure)) {
            if (empty($object->id) && empty($object->rowid)) {
                return 0;
            }
            if ($departure->fetch($object->id ?? $object->rowid) <= 0) {
                return 0;
            }
        }

        if (empty($departure->id)) {
            return 0;
        }

        $sql = 'SELECT fk_facturedet FROM ' . MAIN_DB_PREFIX . 'exc_booking WHERE fk_departure = ' . ((int) $departure->id)
            . ' AND status IN (' . Booking::STATUS_CONFIRMED . ', ' . Booking::STATUS_COMPLETED . ')';
        $resql = $this->db->query($sql);
        if (!$resql) {
            return -1;
        }

        $result = 0;
        $this->currentEntity = (int) $departure->entity;

        while ($obj = $this->db->fetch_object($resql)) {
            $line = new FactureLigne($this->db);
            if ($line->fetch($obj->fk_facturedet) <= 0) {
                continue;
            }
            $line->fetch_optionals();

            $assignments = $this->extractAssignments($line);
            foreach ($assignments as $assignment) {
                $thirdpartyId = $assignment['thirdparty'];
                if (empty($thirdpartyId)) {
                    continue;
                }

                $serviceType = $this->resolveServiceType($thirdpartyId, $assignment['fallback']);
                $amount = $this->computeObligationAmount($serviceType, $line);
                if ($amount <= 0) {
                    continue;
                }

                $this->createOrIncreaseObligation($departure, $thirdpartyId, $serviceType, $amount, $user);
                $result++;
            }
        }

        return $result > 0 ? 1 : 0;
    }

    private function handlePaymentRegister($payload, $user)
    {
        if ($payload instanceof Settlement) {
            return 1;
        }

        return 0;
    }

    private function extractAssignments($invoiceLine)
    {
        $assignments = array();

        if (!empty($invoiceLine->array_options['options_vehiculo_asignado'])) {
            $assignments[] = array(
                'thirdparty' => (int) $invoiceLine->array_options['options_vehiculo_asignado'],
                'fallback' => 'TRANSPORTISTA',
            );
        }

        if (!empty($invoiceLine->array_options['options_guia_asignado'])) {
            $assignments[] = array(
                'thirdparty' => (int) $invoiceLine->array_options['options_guia_asignado'],
                'fallback' => 'GUIA',
            );
        }

        return $assignments;
    }

    private function resolveServiceType($thirdpartyId, $fallback)
    {
        if (isset($this->thirdpartyCache[$thirdpartyId])) {
            return $this->thirdpartyCache[$thirdpartyId];
        }

        $soc = new Societe($this->db);
        if ($soc->fetch($thirdpartyId) > 0) {
            $soc->fetch_optionals();
            if (!empty($soc->array_options['options_tipo_servicio'])) {
                $this->thirdpartyCache[$thirdpartyId] = $soc->array_options['options_tipo_servicio'];

                return $this->thirdpartyCache[$thirdpartyId];
            }
        }

        $this->thirdpartyCache[$thirdpartyId] = $fallback;

        return $fallback;
    }

    private function computeObligationAmount($serviceType, $invoiceLine)
    {
        $rule = $this->getCommissionRule($serviceType);
        if (!$rule) {
            return excursionmanager_calculate_obligation_amount($invoiceLine);
        }

        $amountValue = (float) $rule['amount_value'];
        $amountType = strtoupper($rule['amount_type']);

        switch ($amountType) {
            case 'PER_PAX':
                return round((float) ($invoiceLine->qty ?? 0) * $amountValue, 8);
            case 'PER_DEPARTURE':
                return round($amountValue, 8);
            case 'PERCENT':
                $base = (float) ($invoiceLine->total_ht ?? 0);
                return round($base * ($amountValue / 100), 8);
            default:
                return excursionmanager_calculate_obligation_amount($invoiceLine);
        }
    }

    private function getCommissionRule($serviceType)
    {
        if (isset($this->commissionRules[$serviceType])) {
            return $this->commissionRules[$serviceType];
        }

        $sql = 'SELECT rowid, amount_type, amount_value FROM ' . MAIN_DB_PREFIX . "exc_commission_rule WHERE code = '"
            . $this->db->escape($serviceType) . "' AND status = 1 AND entity IN (0, " . ((int) $this->currentEntity) . ') ORDER BY entity DESC LIMIT 1';

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->commissionRules[$serviceType] = $this->db->fetch_array($resql) ?: null;
        }

        return $this->commissionRules[$serviceType] ?? null;
    }

    private function createOrIncreaseObligation($departure, $thirdpartyId, $serviceType, $amount, $user)
    {
        $obligation = new Settlement($this->db);
        $obligation->entity = $departure->entity;
        $existing = $obligation->fetchByThirdDeparture($thirdpartyId, $departure->id, $serviceType, $departure->entity);

        if ($existing <= 0) {
            $obligation->ref = sprintf('OBL-%d-%d-%s', $departure->id, $thirdpartyId, dol_print_date(dol_now(), 'dayxcard'));
            $obligation->fk_thirdparty = $thirdpartyId;
            $obligation->fk_departure = $departure->id;
            $obligation->type_service = $serviceType;
            $obligation->amount_due = 0;
            $obligation->amount_paid = 0;
            $obligation->status = Settlement::STATUS_PENDING;
            $obligation->date_creation = dol_now();
            $obligation->notes = '';
            if ($obligation->create($user) <= 0) {
                return -1;
            }
        }

        return $obligation->increaseAmount($amount, $user);
    }

    private function updateObligationsForLine($departure, $invoiceLine, $user, $direction = -1)
    {
        $this->currentEntity = (int) $departure->entity;
        $assignments = $this->extractAssignments($invoiceLine);
        foreach ($assignments as $assignment) {
            $thirdpartyId = $assignment['thirdparty'];
            if (empty($thirdpartyId)) {
                continue;
            }

            $serviceType = $this->resolveServiceType($thirdpartyId, $assignment['fallback']);
            $obligation = new Settlement($this->db);
            $obligation->entity = $departure->entity;
            if ($obligation->fetchByThirdDeparture($thirdpartyId, $departure->id, $serviceType, $departure->entity) > 0) {
                $amount = $this->computeObligationAmount($serviceType, $invoiceLine) * $direction;
                $obligation->increaseAmount($amount, $user);
            }
        }
    }
}
