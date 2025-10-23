<?php
require_once dirname(__DIR__, 2) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once __DIR__ . '/lib/excursionmanager.lib.php';

dol_include_once('/custom/excursionmanager/class/departure.class.php');

global $user, $langs, $conf;

if (!$user->rights->excursionmanager->r) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');

llxHeader('', $langs->trans('ExcursionManager'));

print load_fiche_titre($langs->trans('ExcursionManagerDashboard'));

print '<div class="fichecenter">';
print '<div class="fichethirdleft">';
print $langs->trans('ExcursionManagerDashboardIntro');
print '</div>';
print '</div>';

llxFooter();
