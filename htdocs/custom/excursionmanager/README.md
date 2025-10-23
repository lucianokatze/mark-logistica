# Excursion Manager for Dolibarr

Excursion Manager centralises daily excursion logistics, automatic capacity control, service orders and dynamic obligations toward third parties. The module is compatible with Dolibarr 21 and 22 and follows the official module development guidelines.

## Features

- Automatic booking and capacity tracking on invoice validation or payment.
- Daily departures with vehicle, guide, time and passenger assignments.
- Operational console for reassignments and cancellations that adjusts capacities atomically.
- Obligations register with commission rules per service type and manual multi-account payments.
- Payment history by obligation with exported bank references.
- Planner dashboard and invoice tab integration.

## Installation

1. Copy the module folder into `htdocs/custom/excursionmanager`.
2. Load the SQL schema from `script/sql/excursionmanager.sql` within your Dolibarr database.
3. Activate the module from **Setup → Modules** and configure constants from the module setup page.

## Development

- Descriptor: `core/modules/modExcursionManager.class.php`.
- Business objects: `class/` directory.
- Triggers: `core/triggers/interface_99_all_excursionmanager.class.php`.
- Hooks: `class/actions_excursionmanager.class.php`.

For more documentation see the `doc/` folder.
