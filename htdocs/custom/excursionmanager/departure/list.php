<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

dol_include_once('/custom/excursionmanager/class/departure.class.php');

global $user, $langs;

if (!$user->rights->excursionmanager->r) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');

$departures = array();
$departure = new Departure($db);
$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . "exc_departure WHERE entity IN (" . getEntity('excursionmanager') . ') ORDER BY date_departure DESC LIMIT 50';
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        if ($departure->fetch($obj->rowid) > 0) {
            $departures[] = clone $departure;
        }
    }
}

llxHeader('', $langs->trans('ExcursionDepartures'));

print load_fiche_titre($langs->trans('ExcursionDepartures'));

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Ref') . '</th>';
print '<th>' . $langs->trans('Date') . '</th>';
print '<th>' . $langs->trans('Vehicle') . '</th>';
print '<th>' . $langs->trans('CapacityUsed') . '</th>';
print '</tr>';

foreach ($departures as $item) {
    print '<tr class="oddeven">';
    print '<td>' . dol_escape_htmltag($item->ref) . '</td>';
    print '<td>' . dol_print_date($item->date_departure, 'dayhourtext') . '</td>';
    print '<td>' . dol_escape_htmltag($item->fk_soc_vehicle) . '</td>';
    print '<td>' . ((int) $item->capacity_used) . ' / ' . ((int) $item->capacity_total) . '</td>';
    print '</tr>';
}
print '</table>';

llxFooter();
