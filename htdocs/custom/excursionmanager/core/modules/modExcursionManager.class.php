<?php
/*
 * Copyright (C) 2024
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *  Class to describe and enable the Excursion Manager module.
 */
class modExcursionManager extends DolibarrModules
{
    /**
     * Constructor.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $conf;
        $this->db = $db;

        $this->numero = 104400; // reserved id range for custom modules
        $this->rights_class = 'excursionmanager';

        $this->family = 'logistics';
        $this->module_position = 500;
        $this->name = preg_replace('/^mod/', '', get_class($this));
        $this->description = 'Operational logistics, capacity control and obligations for excursions.';
        $this->editor_name = 'Excursion Manager Team';
        $this->editor_url = 'https://example.com';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'fa-bus';
        $this->module_parts = array(
            'hooks' => array(
                'invoicecard',
                'invoicelinecard',
                'thirdpartycard',
                'projectcard',
                'supplierinvoicecard',
                'bankaccountcard',
            ),
            'triggers' => 1,
            'theme' => 0,
            'tpl' => 1,
        );

        $this->dirs = array('/excursionmanager/doc');

        $this->config_page_url = array('admin/excursionmanager_setup.php@excursionmanager');
        $this->hidden = false;
        $this->depends = array('modFacture');
        $this->requiredby = array();
        $this->langfiles = array('excursionmanager@excursionmanager');

        $this->const = array(
            array('EXCUR_AUTOBOOK_ON', 'chaine', 'VALIDATE', 'Auto booking mode: VALIDATE, PAYED or BOTH', 0, 'current', 0),
            array('EXCUR_OVERBOOK_POLICY', 'chaine', 'BLOCK', 'Overbooking policy: BLOCK or WARN', 0, 'current', 0),
            array('EXCUR_PAYMENT_MODE', 'chaine', 'MANUAL', 'Payment mode: MANUAL or AUTO', 0, 'current', 0),
            array('EXCUR_DOC_TEMPLATE', 'chaine', '', 'Default document template for service orders', 0, 'current', 0),
            array('EXCUR_SETTLEMENT_STRATEGY', 'chaine', 'PER_PAX', 'Settlement calculation strategy', 0, 'current', 0),
        );

        $this->tabs = array(
            'invoice:+excursionmanager:ExcursionManager:excursionmanager@excursionmanager:/excursionmanager/tabs/invoice_extras.php',
        );

        $this->dictionaries = array(
            'langs' => 'excursionmanager@excursionmanager',
            'tabname' => array(MAIN_DB_PREFIX . 'exc_commission_rule'),
            'tablib' => array('Excursion commission rules'),
            'tabsql' => array('SELECT rowid as rowid, label, code, amount_type, amount_value, fk_soc_type, entity FROM ' . MAIN_DB_PREFIX . 'exc_commission_rule'),
            'tabsqlsort' => array('label ASC'),
            'tabfield' => array('label,code,amount_type,amount_value,fk_soc_type'),
            'tabfieldvalue' => array('label,code,amount_type,amount_value,fk_soc_type'),
            'tabfieldinsert' => array('label,code,amount_type,amount_value,fk_soc_type,entity'),
            'tabrowid' => array('rowid'),
            'tabcond' => array(($conf->global->EXCUR_ENABLE_COMMISSION_RULES ?? 1) ? 1 : 0),
        );

        $this->rights = array();
        $this->rights[] = array(104401, 'View excursions and obligations', 'r', 'r', $this->rights_class, 0, 0, 'read');
        $this->rights[] = array(104402, 'Manage departures and bookings', 'w', 'w', $this->rights_class, 1, 0, 'managedepartures');
        $this->rights[] = array(104403, 'Manage obligations', 'w', 'w', $this->rights_class, 1, 0, 'manageobligations');
        $this->rights[] = array(104404, 'Register payments', 'w', 'w', $this->rights_class, 1, 0, 'registerpayments');
        $this->rights[] = array(104405, 'Configure Excursion Manager', 'a', 'a', $this->rights_class, 1, 0, 'setup');

        $this->menu = array();
        $r = 0;
        $this->menu[$r++] = array(
            'fk_menu' => 0,
            'type' => 'top',
            'titre' => 'Excursiones',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager',
            'url' => '/custom/excursionmanager/index.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 100,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'Salidas',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_departures',
            'url' => '/custom/excursionmanager/departure/list.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 110,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'Reservas',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_bookings',
            'url' => '/custom/excursionmanager/booking/list.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 120,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'Planificador',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_planner',
            'url' => '/custom/excursionmanager/planner/index.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 130,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'ExcursionObligations',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_obligations',
            'url' => '/custom/excursionmanager/settlement/obligations.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 135,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'ExcursionPayments',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_payments',
            'url' => '/custom/excursionmanager/settlement/payments.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 140,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->r',
            'target' => '',
            'user' => 2,
        );
        $this->menu[$r++] = array(
            'fk_menu' => 'fk_mainmenu=excursiones',
            'type' => 'left',
            'titre' => 'Configuración',
            'mainmenu' => 'excursiones',
            'leftmenu' => 'excursionmanager_setup',
            'url' => '/custom/excursionmanager/admin/excursionmanager_setup.php',
            'langs' => 'excursionmanager@excursionmanager',
            'position' => 150,
            'enabled' => '$conf->excursionmanager->enabled',
            'perms' => '$user->rights->excursionmanager->a',
            'target' => '',
            'user' => 2,
        );
    }
}
