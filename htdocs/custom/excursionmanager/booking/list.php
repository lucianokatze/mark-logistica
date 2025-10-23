<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

dol_include_once('/custom/excursionmanager/class/booking.class.php');

global $user, $langs;

if (!$user->rights->excursionmanager->r) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');

$sql = 'SELECT b.rowid, b.ref, b.date_booking, b.qty, b.status, d.ref AS departure_ref, f.ref AS invoice_ref'
    . ' FROM ' . MAIN_DB_PREFIX . 'exc_booking as b'
    . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'exc_departure as d ON d.rowid = b.fk_departure'
    . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'facture as f ON f.rowid = b.fk_facture'
    . ' WHERE b.entity IN (' . getEntity('excursionmanager') . ')'
    . ' ORDER BY b.date_booking DESC'
    . ' LIMIT 100';

$resql = $db->query($sql);

llxHeader('', $langs->trans('ExcursionBookings'));

print load_fiche_titre($langs->trans('ExcursionBookings'));
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Ref') . '</th>';
print '<th>' . $langs->trans('Invoice') . '</th>';
print '<th>' . $langs->trans('Departure') . '</th>';
print '<th>' . $langs->trans('Quantity') . '</th>';
print '<th>' . $langs->trans('Status') . '</th>';
print '</tr>';

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        print '<tr class="oddeven">';
        print '<td>' . dol_escape_htmltag($obj->ref) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->invoice_ref) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->departure_ref) . '</td>';
        print '<td>' . ((int) $obj->qty) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->status) . '</td>';
        print '</tr>';
    }
}
print '</table>';

llxFooter();
