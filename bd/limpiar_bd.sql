-- ══════════════════════════════════════════════════════════════
-- LIMPIAR DATOS — usando DELETE para respetar FK en phpMyAdmin
-- ══════════════════════════════════════════════════════════════

-- 1. Primero la tabla que referencia actuaciones
DELETE FROM `notificaciones_log`;

-- 2. Tablas que referencian procesos
DELETE FROM `actuaciones`;
DELETE FROM `actuaciones_backup`;
DELETE FROM `honorarios`;
DELETE FROM `anexos`;

-- 3. Procesos (referenciado por las anteriores)
DELETE FROM `procesos`;

-- 4. Clientes
DELETE FROM `clientes`;

-- 5. Resetear auto_increment
ALTER TABLE `notificaciones_log`   AUTO_INCREMENT = 1;
ALTER TABLE `actuaciones`          AUTO_INCREMENT = 1;
ALTER TABLE `actuaciones_backup`   AUTO_INCREMENT = 1;
ALTER TABLE `honorarios`           AUTO_INCREMENT = 1;
ALTER TABLE `anexos`               AUTO_INCREMENT = 1;
ALTER TABLE `procesos`             AUTO_INCREMENT = 1;
ALTER TABLE `clientes`             AUTO_INCREMENT = 1;

-- ── Verificación ─────────────────────────────────────────────
SELECT 'clientes'           AS tabla, COUNT(*) AS filas FROM clientes           UNION ALL
SELECT 'procesos',           COUNT(*) FROM procesos                              UNION ALL
SELECT 'actuaciones',        COUNT(*) FROM actuaciones                          UNION ALL
SELECT 'honorarios',         COUNT(*) FROM honorarios                           UNION ALL
SELECT 'anexos',             COUNT(*) FROM anexos                               UNION ALL
SELECT 'notificaciones_log', COUNT(*) FROM notificaciones_log                   UNION ALL
SELECT '--- CONFIG OK ---',  0                                                   UNION ALL
SELECT 'despachos',          COUNT(*) FROM despachos                            UNION ALL
SELECT 'departamentos',      COUNT(*) FROM departamentos                        UNION ALL
SELECT 'tipos_proceso',      COUNT(*) FROM tipos_proceso                        UNION ALL
SELECT 'estados_proceso',    COUNT(*) FROM estados_proceso                      UNION ALL
SELECT 'usuarios',           COUNT(*) FROM usuarios;