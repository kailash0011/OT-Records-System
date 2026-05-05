-- ============================================================
-- OT Records Management System - Database Schema
-- Version: 1.0.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ot_management_system`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ot_management_system`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin','surgeon','staff','viewer') DEFAULT 'staff',
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    remember_token VARCHAR(64),
    token_expires DATETIME,
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: departments
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    description TEXT,
    head_surgeon_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: surgeons
-- ============================================================
CREATE TABLE IF NOT EXISTS surgeons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    surgeon_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    department_id INT,
    phone VARCHAR(20),
    email VARCHAR(100),
    registration_number VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: patients
-- ============================================================
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    gender ENUM('Male','Female','Other') NOT NULL,
    blood_group VARCHAR(5),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    allergies TEXT,
    medical_history TEXT,
    insurance_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ot_rooms
-- ============================================================
CREATE TABLE IF NOT EXISTS ot_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(50) UNIQUE NOT NULL,
    room_number VARCHAR(10),
    capacity INT DEFAULT 1,
    equipment TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: case_bookings
-- ============================================================
CREATE TABLE IF NOT EXISTS case_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_number VARCHAR(20) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    surgeon_id INT NOT NULL,
    department_id INT NOT NULL,
    ot_room_id INT,
    procedure_name VARCHAR(255) NOT NULL,
    procedure_type ENUM('Elective','Emergency','Semi-Elective') DEFAULT 'Elective',
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NOT NULL,
    estimated_duration INT DEFAULT 60,
    priority ENUM('Routine','Urgent','Emergency') DEFAULT 'Routine',
    status ENUM('Scheduled','In Progress','Completed','Cancelled','Postponed') DEFAULT 'Scheduled',
    anaesthesia_type ENUM('General','Spinal','Epidural','Local','Sedation') DEFAULT 'General',
    pre_op_diagnosis TEXT,
    special_requirements TEXT,
    booked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (surgeon_id) REFERENCES surgeons(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (booked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: ot_records
-- ============================================================
CREATE TABLE IF NOT EXISTS ot_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_number VARCHAR(20) UNIQUE NOT NULL,
    booking_id INT,
    patient_id INT NOT NULL,
    surgeon_id INT NOT NULL,
    department_id INT NOT NULL,
    ot_room_id INT,
    operation_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    procedure_performed TEXT NOT NULL,
    post_op_diagnosis TEXT,
    anaesthesia_type ENUM('General','Spinal','Epidural','Local','Sedation'),
    anaesthetist_name VARCHAR(100),
    scrub_nurse VARCHAR(100),
    circulating_nurse VARCHAR(100),
    assistant_surgeon VARCHAR(100),
    blood_loss_ml INT DEFAULT 0,
    urine_output_ml INT DEFAULT 0,
    fluid_input_ml INT DEFAULT 0,
    complications TEXT,
    post_op_instructions TEXT,
    wound_classification ENUM('Clean','Clean-Contaminated','Contaminated','Dirty') DEFAULT 'Clean',
    outcome ENUM('Satisfactory','Guarded','Critical','Deceased') DEFAULT 'Satisfactory',
    icu_required TINYINT(1) DEFAULT 0,
    case_type_echs TINYINT(1) NOT NULL DEFAULT 0,
    case_type_ssf TINYINT(1) NOT NULL DEFAULT 0,
    case_type_mlc TINYINT(1) NOT NULL DEFAULT 0,
    case_type_other TINYINT(1) NOT NULL DEFAULT 0,
    case_type_other_text VARCHAR(200) DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (surgeon_id) REFERENCES surgeons(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (booking_id) REFERENCES case_bookings(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: blood_transfusions
-- ============================================================
CREATE TABLE IF NOT EXISTS blood_transfusions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    blood_type VARCHAR(10),
    units_transfused DECIMAL(4,1),
    transfusion_time TIME,
    reaction TINYINT(1) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (record_id) REFERENCES ot_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: catheters
-- ============================================================
CREATE TABLE IF NOT EXISTS catheters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    catheter_type VARCHAR(100),
    size VARCHAR(20),
    insertion_time TIME,
    removal_time TIME,
    site VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (record_id) REFERENCES ot_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: specimens
-- ============================================================
CREATE TABLE IF NOT EXISTS specimens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    specimen_type VARCHAR(100),
    specimen_site VARCHAR(100),
    sent_to_lab TINYINT(1) DEFAULT 0,
    lab_reference VARCHAR(50),
    notes TEXT,
    FOREIGN KEY (record_id) REFERENCES ot_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: implants
-- ============================================================
CREATE TABLE IF NOT EXISTS implants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    implant_name VARCHAR(100),
    brand VARCHAR(100),
    serial_number VARCHAR(100),
    lot_number VARCHAR(100),
    expiry_date DATE,
    notes TEXT,
    FOREIGN KEY (record_id) REFERENCES ot_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: audit_logs
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: system_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: schedules_holidays
-- ============================================================
CREATE TABLE IF NOT EXISTS schedules_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL,
    description VARCHAR(255),
    is_recurring TINYINT(1) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Add deferred FK on departments.head_surgeon_id
-- ============================================================
ALTER TABLE departments
    ADD CONSTRAINT fk_dept_head_surgeon
    FOREIGN KEY (head_surgeon_id) REFERENCES surgeons(id) ON DELETE SET NULL;

-- ============================================================
-- DEFAULT DATA: Departments
-- ============================================================
INSERT INTO departments (name, code, description, is_active) VALUES
('General Surgery',  'GS',   'General surgical procedures',             1),
('Orthopaedics',     'ORTHO','Bone, joint and musculoskeletal surgery', 1),
('Gynaecology',      'GYN',  'Female reproductive health surgery',      1),
('Cardiology',       'CARD', 'Cardiac and vascular surgery',            1),
('Neurosurgery',     'NEURO','Brain, spine and nervous system surgery', 1),
('ENT',              'ENT',  'Ear, nose and throat surgery',            1),
('Ophthalmology',    'OPHT', 'Eye surgery and procedures',              1),
('Urology',          'URO',  'Urinary tract and renal surgery',         1);

-- ============================================================
-- DEFAULT DATA: OT Rooms
-- ============================================================
INSERT INTO ot_rooms (room_name, room_number, capacity, equipment, is_active) VALUES
('OT-1',         '01', 1, 'Anaesthesia machine, Surgical lights, C-Arm, Patient monitor', 1),
('OT-2',         '02', 1, 'Anaesthesia machine, Surgical lights, Laparoscopic tower, Patient monitor', 1),
('OT-3',         '03', 1, 'Anaesthesia machine, Surgical lights, Orthopaedic table, Image intensifier', 1),
('OT-4',         '04', 1, 'Anaesthesia machine, Surgical lights, Microscope, Neuro navigation', 1),
('Emergency OT', 'E1', 1, 'Anaesthesia machine, Crash cart, Surgical lights, Patient monitor, Defibrillator', 1);

-- ============================================================
-- DEFAULT DATA: Surgeons
-- ============================================================
INSERT INTO surgeons (surgeon_id, full_name, specialization, department_id, phone, email, registration_number, is_active) VALUES
('SRG-0001', 'Dr. John Smith',       'General Surgery',       1, '9800001001', 'john.smith@hospital.local',    'MCI-GS-1001', 1),
('SRG-0002', 'Dr. Priya Sharma',     'Orthopaedic Surgery',   2, '9800001002', 'priya.sharma@hospital.local',  'MCI-OR-1002', 1),
('SRG-0003', 'Dr. Anita Verma',      'Gynaecology',           3, '9800001003', 'anita.verma@hospital.local',   'MCI-GY-1003', 1),
('SRG-0004', 'Dr. Ramesh Patel',     'Cardiac Surgery',       4, '9800001004', 'ramesh.patel@hospital.local',  'MCI-CA-1004', 1),
('SRG-0005', 'Dr. Suresh Kumar',     'Neurosurgery',          5, '9800001005', 'suresh.kumar@hospital.local',  'MCI-NE-1005', 1),
('SRG-0006', 'Dr. Kavitha Nair',     'ENT Surgery',           6, '9800001006', 'kavitha.nair@hospital.local',  'MCI-EN-1006', 1),
('SRG-0007', 'Dr. Arun Menon',       'Ophthalmology',         7, '9800001007', 'arun.menon@hospital.local',    'MCI-OP-1007', 1),
('SRG-0008', 'Dr. Deepak Joshi',     'Urology',               8, '9800001008', 'deepak.joshi@hospital.local',  'MCI-UR-1008', 1);

-- Set department heads
UPDATE departments SET head_surgeon_id = 1 WHERE code = 'GS';
UPDATE departments SET head_surgeon_id = 2 WHERE code = 'ORTHO';
UPDATE departments SET head_surgeon_id = 3 WHERE code = 'GYN';
UPDATE departments SET head_surgeon_id = 4 WHERE code = 'CARD';
UPDATE departments SET head_surgeon_id = 5 WHERE code = 'NEURO';
UPDATE departments SET head_surgeon_id = 6 WHERE code = 'ENT';
UPDATE departments SET head_surgeon_id = 7 WHERE code = 'OPHT';
UPDATE departments SET head_surgeon_id = 8 WHERE code = 'URO';

-- ============================================================
-- DEFAULT DATA: Users
-- Password for all = 'Admin@123'
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.usfutxeVS
-- ============================================================
INSERT INTO users (username, email, password, full_name, role, phone, is_active) VALUES
('admin',   'admin@otms.local',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.usfutxeVS', 'System Administrator', 'admin',   '9900000001', 1),
('surgeon', 'surgeon@otms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.usfutxeVS', 'Dr. John Smith',       'surgeon', '9800001001', 1),
('staff',   'staff@otms.local',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.usfutxeVS', 'OT Staff',             'staff',   '9900000002', 1);

-- ============================================================
-- DEFAULT DATA: Patients
-- ============================================================
INSERT INTO patients (patient_id, full_name, date_of_birth, gender, blood_group, phone, email, address, emergency_contact, emergency_phone, allergies, medical_history, insurance_number) VALUES
('PT-20240001', 'Rajesh Kumar',    '1978-04-15', 'Male',   'B+',  '9811001001', 'rajesh.kumar@email.com',    '12 MG Road, Bangalore 560001',        'Meena Kumar',    '9811001002', 'Penicillin',              'Hypertension, Type 2 Diabetes',           'INS-2024-0001'),
('PT-20240002', 'Sunita Devi',     '1990-09-22', 'Female', 'A+',  '9811002001', 'sunita.devi@email.com',     '45 Park Street, Kolkata 700016',      'Ramesh Devi',    '9811002002', 'None',                    'None',                                    'INS-2024-0002'),
('PT-20240003', 'Anil Mehta',      '1965-12-05', 'Male',   'O-',  '9811003001', 'anil.mehta@email.com',      '78 Linking Road, Mumbai 400050',      'Pooja Mehta',    '9811003002', 'Sulpha drugs',            'Chronic obstructive pulmonary disease',   'INS-2024-0003'),
('PT-20240004', 'Lakshmi Pillai',  '1985-07-30', 'Female', 'AB+', '9811004001', 'lakshmi.pillai@email.com',  '22 Anna Salai, Chennai 600002',       'Suresh Pillai',  '9811004002', 'Latex',                   'Fibroid uterus',                          'INS-2024-0004'),
('PT-20240005', 'Mohammed Irfan',  '1955-03-18', 'Male',   'B-',  '9811005001', 'irfan.m@email.com',         '10 Residency Road, Hyderabad 500001', 'Fathima Irfan',  '9811005002', 'Aspirin, NSAIDs',         'Coronary artery disease, prior MI 2020', 'INS-2024-0005');

-- ============================================================
-- DEFAULT DATA: Case Bookings
-- ============================================================
INSERT INTO case_bookings (booking_number, patient_id, surgeon_id, department_id, ot_room_id, procedure_name, procedure_type, scheduled_date, scheduled_time, estimated_duration, priority, status, anaesthesia_type, pre_op_diagnosis, booked_by) VALUES
('BK-20240501-0001', 1, 1, 1, 1, 'Laparoscopic Cholecystectomy',         'Elective',      CURDATE(),      '08:00:00', 90,  'Routine',   'Scheduled',   'General',  'Cholelithiasis with chronic cholecystitis',               1),
('BK-20240501-0002', 2, 3, 3, 2, 'Total Abdominal Hysterectomy',         'Elective',      CURDATE(),      '10:00:00', 120, 'Routine',   'Scheduled',   'Spinal',   'Fibroid uterus with menorrhagia',                         1),
('BK-20240501-0003', 3, 2, 2, 3, 'Total Knee Replacement - Right',       'Elective',      CURDATE(),      '12:00:00', 150, 'Routine',   'In Progress', 'Spinal',   'Severe osteoarthritis right knee',                        1),
('BK-20240501-0004', 4, 3, 3, 2, 'Diagnostic Laparoscopy',               'Elective',      DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 60,  'Routine',   'Scheduled',   'General',  'Pelvic inflammatory disease - rule out ectopic pregnancy', 1),
('BK-20240501-0005', 5, 4, 4, 5, 'Coronary Artery Bypass Graft x3',      'Semi-Elective', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '07:30:00', 240, 'Urgent',    'Scheduled',   'General',  'Triple vessel coronary artery disease',                   1),
('BK-20240501-0006', 1, 5, 5, 4, 'Lumbar Discectomy L4-L5',              'Elective',      DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 120, 'Routine',   'Scheduled',   'General',  'Lumbar disc herniation with radiculopathy',                1),
('BK-20240501-0007', 2, 6, 6, 1, 'Tonsillectomy and Adenoidectomy',      'Elective',      DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', 45,  'Routine',   'Scheduled',   'General',  'Recurrent tonsillitis',                                   1),
('BK-20240501-0008', 3, 7, 7, 1, 'Phacoemulsification with IOL - Right', 'Elective',      DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 30,  'Routine',   'Scheduled',   'Local',    'Cataract right eye',                                      1),
('BK-20240501-0009', 4, 8, 8, 2, 'Transurethral Resection of Prostate',  'Elective',      DATE_ADD(CURDATE(), INTERVAL 5 DAY), '08:00:00', 90,  'Routine',   'Scheduled',   'Spinal',   'Benign prostatic hyperplasia',                            1),
('BK-20240501-0010', 5, 1, 1, 5, 'Emergency Appendicectomy',             'Emergency',     CURDATE(),      '06:00:00', 60,  'Emergency', 'Completed',   'General',  'Acute appendicitis with peritonitis',                     1);

-- ============================================================
-- DEFAULT DATA: System Settings
-- ============================================================
INSERT INTO system_settings (setting_key, setting_value, setting_group, description) VALUES
('hospital_name',        'City General Hospital',        'general',       'Name of the hospital'),
('hospital_address',     '1 Hospital Road, City 000001', 'general',       'Hospital address'),
('hospital_phone',       '+91-000-0000000',              'general',       'Hospital contact number'),
('hospital_email',       'info@citygeneralhospital.com', 'general',       'Hospital email address'),
('hospital_logo',        '',                             'general',       'Path to hospital logo file'),
('app_version',          '1.0.0',                        'general',       'Application version'),
('date_format',          'd/m/Y',                        'general',       'Display date format'),
('time_format',          'H:i',                          'general',       'Display time format'),
('session_timeout',      '30',                           'security',      'Session timeout in minutes'),
('max_login_attempts',   '5',                            'security',      'Max failed login attempts before lockout'),
('lockout_duration',     '15',                           'security',      'Account lockout duration in minutes'),
('items_per_page',       '25',                           'display',       'Default items per page in lists'),
('enable_audit_log',     '1',                            'security',      'Enable audit logging'),
('smtp_host',            '',                             'email',         'SMTP server host'),
('smtp_port',            '587',                          'email',         'SMTP server port'),
('smtp_user',            '',                             'email',         'SMTP username'),
('smtp_pass',            '',                             'email',         'SMTP password (encrypted)'),
('smtp_from',            '',                             'email',         'From email address'),
('backup_enabled',       '0',                            'system',        'Enable automatic database backup'),
('backup_frequency',     'daily',                        'system',        'Backup frequency: daily/weekly');
