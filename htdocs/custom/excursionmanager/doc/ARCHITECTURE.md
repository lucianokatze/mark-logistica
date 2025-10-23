# Excursion Manager Architecture

## Overview

Excursion Manager orchestrates excursion logistics by combining invoice line extrafields, custom tables and Dolibarr triggers.

## Data model

- `llx_exc_departure`: daily departures with vehicle, guide and capacity usage.
- `llx_exc_booking`: link between invoice lines and departures.
- `llx_exc_obligation`: obligation ledger by service type and departure.
- `llx_exc_payment_line`: manual payment history linked to obligations.
- `llx_exc_commission_rule`: commission calculation rules.
- Extrafields on invoice lines and third parties for operational metadata.

## Business flow

1. Invoice validation trigger evaluates configured mode (`EXCUR_AUTOBOOK_ON`) and registers bookings.
2. Bookings reserve capacity atomically and update departures.
3. Reassignments update `fk_departure` and log seat changes.
4. When departures close the trigger generates obligations per service type applying commission rules and `EXCUR_SETTLEMENT_STRATEGY`.
5. Manual payments update balances and register bank account usage, with obligations switching to partially paid or paid automatically.

## Hooks and UI

- Invoice and project hooks expose excursion data.
- Menu structure provides access to departures, bookings, planner, obligations and payments.
- Setup page manages configuration constants without editing code.

## Testing

PHPUnit test stubs are located under `tests/phpunit`. Custom tests cover triggers and settlement calculations.
