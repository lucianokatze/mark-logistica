CREATE TABLE IF NOT EXISTS llx_exc_vehicle (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(128) NOT NULL,
    label VARCHAR(255) NOT NULL,
    fk_soc INTEGER NULL,
    capacity INTEGER NOT NULL DEFAULT 0,
    active TINYINT NOT NULL DEFAULT 1,
    entity INTEGER NOT NULL DEFAULT 1
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_exc_departure (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(128) NOT NULL,
    label VARCHAR(255) NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    date_departure DATETIME NOT NULL,
    date_return DATETIME NULL,
    fk_soc_vehicle INTEGER NULL,
    fk_soc_guide INTEGER NULL,
    fk_user_guide INTEGER NULL,
    capacity_total INTEGER NOT NULL DEFAULT 0,
    capacity_used INTEGER NOT NULL DEFAULT 0,
    status INTEGER NOT NULL DEFAULT 0,
    note_public TEXT NULL,
    note_private TEXT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_exc_booking (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(128) NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    fk_facture INTEGER NOT NULL,
    fk_facturedet INTEGER NOT NULL,
    fk_departure INTEGER NOT NULL,
    qty INTEGER NOT NULL DEFAULT 0,
    status INTEGER NOT NULL DEFAULT 0,
    date_booking DATETIME NULL,
    note_public TEXT NULL,
    note_private TEXT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_exc_obligation (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(128) NOT NULL,
    entity INTEGER NOT NULL DEFAULT 1,
    fk_thirdparty INTEGER NOT NULL,
    fk_departure INTEGER NULL,
    type_service VARCHAR(64) NOT NULL,
    amount_due DOUBLE(24,8) NOT NULL DEFAULT 0,
    amount_paid DOUBLE(24,8) NOT NULL DEFAULT 0,
    status INTEGER NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL,
    date_valid DATETIME NULL,
    fk_user_valid INTEGER NULL,
    notes TEXT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_exc_payment_line (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER NOT NULL DEFAULT 1,
    fk_obligation INTEGER NOT NULL,
    fk_bankaccount INTEGER NULL,
    amount DOUBLE(24,8) NOT NULL DEFAULT 0,
    date_payment DATETIME NOT NULL,
    notes TEXT NULL,
    fk_user_author INTEGER NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_exc_commission_rule (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    entity INTEGER NOT NULL DEFAULT 1,
    label VARCHAR(255) NOT NULL,
    code VARCHAR(128) NOT NULL,
    amount_type VARCHAR(16) NOT NULL,
    amount_value DOUBLE(24,8) NOT NULL DEFAULT 0,
    fk_soc_type INTEGER NULL,
    status INTEGER NOT NULL DEFAULT 1
) ENGINE=innodb;

CREATE UNIQUE INDEX idx_exc_commission_rule_code ON llx_exc_commission_rule (code, entity);
CREATE INDEX idx_exc_departure_ref ON llx_exc_departure (ref, entity);
CREATE INDEX idx_exc_departure_vehicle_date ON llx_exc_departure (fk_soc_vehicle, date_departure);
CREATE INDEX idx_exc_booking_facturedet ON llx_exc_booking (fk_facturedet);
CREATE INDEX idx_exc_obligation_status ON llx_exc_obligation (fk_thirdparty, status);
CREATE INDEX idx_exc_obligation_departure ON llx_exc_obligation (fk_departure, status);
CREATE INDEX idx_exc_payment_line_obligation ON llx_exc_payment_line (fk_obligation);
INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'vehiculo_asignado', 'Assigned vehicle', 'sellist', 'facturedet', 'options=llx_societe.rowid:llx_societe.nom:Transportista', e.entity, 10
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'vehiculo_asignado' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'fecha_excursion', 'Departure date', 'date', 'facturedet', '', e.entity, 20
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'fecha_excursion' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'hora_salida', 'Departure time', 'hour', 'facturedet', '', e.entity, 30
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'hora_salida' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'guia_asignado', 'Guide', 'sellist', 'facturedet', 'options=llx_societe.rowid:llx_societe.nom:Guia', e.entity, 40
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'guia_asignado' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'pickup_point', 'Pickup point', 'varchar', 'facturedet', 'size=255', e.entity, 50
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'pickup_point' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'pasajeros', 'Passengers', 'int', 'facturedet', '', e.entity, 60
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'pasajeros' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos, param)
SELECT 'fk_departure', 'Departure link', 'int', 'facturedet', '', e.entity, 70, 'hidden'
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'fk_departure' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'notes_ops', 'Operations notes', 'text', 'facturedet', 'height=60', e.entity, 80
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'notes_ops' AND elementtype = 'facturedet' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'is_vehicle', 'Is vehicle', 'boolean', 'societe', '', e.entity, 10
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'is_vehicle' AND elementtype = 'societe' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'capacity', 'Capacity', 'int', 'societe', '', e.entity, 20
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'capacity' AND elementtype = 'societe' AND entity = e.entity);

INSERT INTO llx_extrafields (name, label, type, elementtype, fieldparams, entity, pos)
SELECT 'tipo_servicio', 'Service type', 'sellist', 'societe', 'options=llx_c_exc_soc_type.code:llx_c_exc_soc_type.label', e.entity, 30
FROM (SELECT 1 AS entity) AS e
WHERE NOT EXISTS (SELECT 1 FROM llx_extrafields WHERE name = 'tipo_servicio' AND elementtype = 'societe' AND entity = e.entity);
CREATE TABLE IF NOT EXISTS llx_c_exc_soc_type (
    rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(32) NOT NULL,
    label VARCHAR(255) NOT NULL,
    active TINYINT NOT NULL DEFAULT 1,
    entity INTEGER NOT NULL DEFAULT 0,
    sortorder INTEGER NOT NULL DEFAULT 0
) ENGINE=innodb;

INSERT INTO llx_c_exc_soc_type (code, label, active, entity, sortorder)
SELECT t.code, t.label, t.active, t.entity, t.sortorder
FROM (
    SELECT 'TRANSPORTISTA' AS code, 'Transportista' AS label, 1 AS active, 0 AS entity, 10 AS sortorder
    UNION ALL SELECT 'GUIA', 'Guía', 1, 0, 20
    UNION ALL SELECT 'AGENCIA', 'Agencia', 1, 0, 30
    UNION ALL SELECT 'VENDEDOR', 'Vendedor externo', 1, 0, 40
    UNION ALL SELECT 'REFERIDO', 'Referido', 1, 0, 50
) AS t
WHERE NOT EXISTS (SELECT 1 FROM llx_c_exc_soc_type WHERE code = t.code);
