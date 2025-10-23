<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

global $langs, $user, $db;

if (!$user->rights->excursionmanager->r) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');

$invoiceId = GETPOST('id', 'int');
$invoice = new Facture($db);
if ($invoice->fetch($invoiceId) <= 0) {
    accessforbidden();
}

llxHeader('', $langs->trans('ExcursionDetails'));

print load_fiche_titre($langs->trans('ExcursionDetailsForInvoice', $invoice->ref));

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Line') . '</th>';
print '<th>' . $langs->trans('Departure') . '</th>';
print '<th>' . $langs->trans('Passengers') . '</th>';
print '<th>' . $langs->trans('Vehicle') . '</th>';
print '<th>' . $langs->trans('Guide') . '</th>';
print '</tr>';

foreach ($invoice->lines as $line) {
    $departureId = $line->array_options['options_fk_departure'] ?? 0;
    $departure = $departureId ? excursionmanager_load_departure($db, $departureId) : null;
    print '<tr class="oddeven">';
    print '<td>' . $line->id . '</td>';
    print '<td>' . ($departure ? dol_escape_htmltag($departure->ref) : '-') . '</td>';
    print '<td>' . ((int) ($line->array_options['options_pasajeros'] ?? 0)) . '</td>';
    print '<td>' . dol_escape_htmltag($line->array_options['options_vehiculo_asignado'] ?? '') . '</td>';
    print '<td>' . dol_escape_htmltag($line->array_options['options_guia_asignado'] ?? '') . '</td>';
    print '</tr>';
}
print '</table>';

llxFooter();
