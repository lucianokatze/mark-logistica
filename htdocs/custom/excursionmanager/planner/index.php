<?php
require_once dirname(__DIR__, 4) . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once __DIR__ . '/../lib/excursionmanager.lib.php';

global $user, $langs, $conf;

if (!$user->rights->excursionmanager->r) {
    accessforbidden();
}

$langs->load('excursionmanager@excursionmanager');

llxHeader('', $langs->trans('ExcursionPlanner'));

print load_fiche_titre($langs->trans('ExcursionPlanner'));
print '<div id="excursion-planner">';
print '<p>' . $langs->trans('ExcursionPlannerPlaceholder') . '</p>';
print '</div>';

llxFooter();
