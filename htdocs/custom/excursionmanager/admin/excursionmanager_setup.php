<?php
require_once dirname(__DIR__, 3) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

global $langs, $user, $conf, $db;

if (!$user->rights->excursionmanager->a) {
    accessforbidden();
}

$langs->load('admin');
$langs->load('excursionmanager@excursionmanager');

$action = GETPOST('action', 'aZ09');
$const = GETPOST('const', 'alpha');

if ($action === 'update_const' && $const) {
    $value = GETPOST('value', 'alpha');
    dolibarr_set_const($db, $const, $value, 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}

llxHeader('', $langs->trans('ExcursionManagerSetup'));

print load_fiche_titre($langs->trans('ExcursionManagerSetup'));

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<th>' . $langs->trans('Parameter') . '</th>';
print '<th>' . $langs->trans('Value') . '</th>';
print '</tr>';

$constants = array(
    'EXCUR_AUTOBOOK_ON',
    'EXCUR_OVERBOOK_POLICY',
    'EXCUR_PAYMENT_MODE',
    'EXCUR_DOC_TEMPLATE',
    'EXCUR_SETTLEMENT_STRATEGY',
);

foreach ($constants as $constName) {
    $value = $conf->global->{$constName} ?? '';
    print '<tr class="oddeven">';
    print '<td>' . $constName . '</td>';
    print '<td>';
    print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update_const">';
    print '<input type="hidden" name="const" value="' . $constName . '">';
    print '<input type="text" name="value" value="' . dol_escape_htmltag($value) . '">';
    print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
    print '</form>';
    print '</td>';
    print '</tr>';
}

print '</table>';

llxFooter();
