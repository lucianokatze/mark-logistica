<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once __DIR__ . '/../class/settlement.class.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

global $db, $user, $langs, $conf;

if (empty($user->rights->excursionmanager->r)) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');
$langs->load('banks');

$action = GETPOST('action', 'aZ09');
$token = newToken();

if (
    $action === 'record_payment'
    && ( !empty($user->rights->excursionmanager->registerpayments) || !empty($user->rights->excursionmanager->w))
    && GETPOSTINT('obligation_id')
) {
    if (!checkToken()) {
        accessforbidden('CSRF token not valid');
    }
    $obligationId = GETPOSTINT('obligation_id');
    $amount = price2num(GETPOST('amount', 'alphanohtml'), 'MT');
    $bankId = GETPOSTINT('fk_bankaccount');
    $notes = GETPOST('notes', 'restricthtml');
    $dateString = GETPOST('date_payment', 'alpha');
    $datePayment = $dateString ? dol_stringtotime($dateString) : dol_now();

    $obligation = new Settlement($db);
    if ($obligation->fetch($obligationId) > 0) {
        $result = $obligation->recordPayment($user, $amount, $bankId, $datePayment, $notes);
        if ($result > 0) {
            $obligation->call_trigger('PAYMENT_REGISTER', $user);
            setEventMessages($langs->trans('ExcPaymentRegistered'), null, 'mesgs');
        } else {
            setEventMessages($langs->trans('ExcPaymentError'), null, 'errors');
        }
    }
}

$sql = 'SELECT o.rowid, o.ref, o.type_service, o.amount_due, o.amount_paid, o.status, o.date_creation, o.notes, '
    . 'o.fk_departure, s.nom as thirdparty_name '
    . 'FROM ' . MAIN_DB_PREFIX . 'exc_obligation as o '
    . 'LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = o.fk_thirdparty '
    . 'WHERE o.entity IN (' . getEntity('excursionmanager') . ') '
    . 'ORDER BY o.date_creation DESC';

$resql = $db->query($sql);

$form = new Form($db);

llxHeader('', $langs->trans('ExcursionObligations')); 

print load_fiche_titre($langs->trans('ExcursionObligations'), '', 'fa-money');

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Ref') . '</th>';
print '<th>' . $langs->trans('ThirdParty') . '</th>';
print '<th>' . $langs->trans('ServiceType') . '</th>';
print '<th class="right">' . $langs->trans('AmountDue') . '</th>';
print '<th class="right">' . $langs->trans('AmountPaid') . '</th>';
print '<th class="right">' . $langs->trans('AmountRemaining') . '</th>';
print '<th>' . $langs->trans('Status') . '</th>';
print '<th>' . $langs->trans('Date') . '</th>';
if (!empty($user->rights->excursionmanager->registerpayments) || !empty($user->rights->excursionmanager->w)) {
    print '<th>' . $langs->trans('Actions') . '</th>';
}
print '</tr>';

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $remaining = price2num($obj->amount_due - $obj->amount_paid, 'MT');
        $statusLabel = $langs->trans('ExcObligationStatus' . (int) $obj->status);

        print '<tr class="oddeven">';
        print '<td>' . dol_escape_htmltag($obj->ref) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->thirdparty_name) . '</td>';
        print '<td>' . dol_escape_htmltag($obj->type_service) . '</td>';
        print '<td class="right">' . price($obj->amount_due) . '</td>';
        print '<td class="right">' . price($obj->amount_paid) . '</td>';
        print '<td class="right">' . price($remaining) . '</td>';
        print '<td>' . $statusLabel . '</td>';
        print '<td>' . dol_print_date($db->jdate($obj->date_creation), 'day') . '</td>';
        if (!empty($user->rights->excursionmanager->registerpayments) || !empty($user->rights->excursionmanager->w)) {
            print '<td>';
            if ($remaining > 0) {
                print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
                print '<input type="hidden" name="token" value="' . $token . '">';
                print '<input type="hidden" name="action" value="record_payment">';
                print '<input type="hidden" name="obligation_id" value="' . ((int) $obj->rowid) . '">';
                print '<input type="number" step="0.01" name="amount" value="' . price($remaining) . '" class="flat" style="width:80px"> ';
                print $form->select_comptes('', 'fk_bankaccount', '', 1, 0, 0, '', 0, '');
                print ' <input type="date" name="date_payment" value="' . dol_print_date(dol_now(), 'dayxcard') . '">';
                print ' <input type="text" name="notes" placeholder="' . dol_escape_htmltag($langs->trans('Comment')) . '">';
                print ' <input type="submit" class="button" value="' . $langs->trans('Register') . '">';
                print '</form>';
            } else {
                print $langs->trans('ExcObligationSettled');
            }
            print '</td>';
        }
        print '</tr>';
    }
}

print '</table>';

llxFooter();
