<?php
/**
 * Hooks for Excursion Manager.
 */
class ActionsExcursionmanager
{
    /**
     * Add tabs on invoice and project cards.
     */
    public function addMoreTabs($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $langs->load('excursionmanager@excursionmanager');

        if ($parameters['context'] === 'invoicecard') {
            $newTab = array(
                'url' => dol_buildpath('/custom/excursionmanager/tabs/invoice_extras.php?id=' . $object->id, 1),
                'title' => $langs->trans('ExcursionManager'),
                'id' => 'tabExcursionManager',
            );
            $this->tabs[] = $newTab;

            return 0;
        }

        return 0;
    }

    /**
     * Display extra fields within invoice line form.
     */
    public function formBuilddocOptions($parameters, &$object, &$action, $hookmanager)
    {
        if ($parameters['context'] !== 'invoicelinecard') {
            return 0;
        }

        return 0;
    }
}
