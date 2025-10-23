<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $db, $user, $langs;

if (empty($user->rights->excursionmanager->r)) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');
$langs->load('banks');

$sql = 'SELECT p.rowid, p.amount, p.date_payment, p.notes, '
    . 'o.ref as obligation_ref, o.type_service, s.nom as thirdparty_name, '
    . 'ba.label as bank_label, u.login as user_login '
    . 'FROM ' . MAIN_DB_PREFIX . 'exc_payment_line as p '
    . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'exc_obligation as o ON o.rowid = p.fk_obligation '
    . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = o.fk_thirdparty '
    . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON ba.rowid = p.fk_bankaccount '
    . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u ON u.rowid = p.fk_user_author '
    . 'WHERE p.entity IN (' . getEntity('excursionmanager') . ') '
    . 'ORDER BY p.date_payment DESC';

$resql = $db->query($sql);

llxHeader('', $langs->trans('ExcursionPayments'));

print load_fiche_titre($langs->trans('ExcursionPayments'), '', 'fa-credit-card');

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Obligation') . '</th>';
print '<th>' . $langs->trans('ThirdParty') . '</th>';
print '<th>' . $langs->trans('ServiceType') . '</th>';
print '<th>' . $langs->trans('BankAccount') . '</th>';
print '<th class="right">' . $langs->trans('Amount') . '</th>';
print '<th>' . $langs->trans('Date') . '</th>';
print '<th>' . $langs->trans('RecordedBy') . '</th>';
print '<th>' . $langs->trans('Comment') . '</th>';
print '</tr>';

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        print '<tr class="oddeven">';
        print '<td>' . dol_escape_htmltag($obj->obligation_ref) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->thirdparty_name) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->type_service) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->bank_label) . '</td>';
        print '<td class="right">' . price($obj->amount) . '</td>';
        print '<td>' . dol_print_date($db->jdate($obj->date_payment), 'day') . '</td>';
        print '<td>' . dol_escape_htmltag($obj->user_login) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->notes) . '</td>';
        print '</tr>';
    }
}

print '</table>';

llxFooter();
