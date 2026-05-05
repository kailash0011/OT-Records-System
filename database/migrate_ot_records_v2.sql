-- ============================================================
-- OT Records Management System – Migration v2
-- Adds case-type flags and other-specify text to ot_records
-- Run once against ot_management_system database
-- ============================================================

USE `ot_management_system`;

-- Case type flags (multiple may be set simultaneously)
ALTER TABLE `ot_records`
    ADD COLUMN IF NOT EXISTS `case_type_echs`        TINYINT(1)   NOT NULL DEFAULT 0  AFTER `icu_required`,
    ADD COLUMN IF NOT EXISTS `case_type_ssf`         TINYINT(1)   NOT NULL DEFAULT 0  AFTER `case_type_echs`,
    ADD COLUMN IF NOT EXISTS `case_type_mlc`         TINYINT(1)   NOT NULL DEFAULT 0  AFTER `case_type_ssf`,
    ADD COLUMN IF NOT EXISTS `case_type_other`       TINYINT(1)   NOT NULL DEFAULT 0  AFTER `case_type_mlc`,
    ADD COLUMN IF NOT EXISTS `case_type_other_text`  VARCHAR(200)          DEFAULT NULL AFTER `case_type_other`;
