<?php
/**
 * Utility helpers for Excursion Manager module.
 */
function excursionmanager_load_departure($db, $departureId)
{
    dol_include_once('/custom/excursionmanager/class/departure.class.php');
    $departure = new Departure($db);
    if ($departure->fetch($departureId) > 0) {
        return $departure;
    }

    return null;
}

/**
 * Calculate obligation amount based on settlement strategy.
 *
 * @param FactureLigne $line
 * @return float
 */
function excursionmanager_calculate_obligation_amount($line)
{
    global $conf;

    $strategy = $conf->global->EXCUR_SETTLEMENT_STRATEGY ?? 'PER_PAX';
    $amount = 0.0;

    switch ($strategy) {
        case 'PER_DEPARTURE':
            $amount = (float) ($line->total_ht ?? 0);
            break;
        case 'PER_SALE':
            $amount = (float) ($line->total_ttc ?? $line->total_ht ?? 0);
            break;
        case 'PER_PAX':
        default:
            $qty = (float) ($line->qty ?? 0);
            $price = (float) ($line->subprice ?? 0);
            $amount = $qty * $price;
            break;
    }

    return round($amount, 8);
}
