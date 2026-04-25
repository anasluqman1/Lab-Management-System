CREATE DATABASE IF NOT EXISTS lab_system;
USE lab_system;

-- USERS TABLE (admin, technician, doctor)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'technician', 'doctor') NOT NULL DEFAULT 'technician',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- PATIENTS TABLE 
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    date_of_birth DATE,
    age INT,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    blood_group ENUM('O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- TEST CATALOG 
CREATE TABLE IF NOT EXISTS test_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_code VARCHAR(20) UNIQUE NOT NULL,
    test_name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    unit VARCHAR(30),
    normal_range_min DECIMAL(10,2),
    normal_range_max DECIMAL(10,2),
    normal_range_text VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PATIENT TESTS 
CREATE TABLE IF NOT EXISTS patient_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    test_id INT NOT NULL,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    referred_doctor VARCHAR(100) DEFAULT NULL,
    ordered_by INT,
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES test_catalog(id) ON DELETE CASCADE,
    FOREIGN KEY (ordered_by) REFERENCES users(id) ON DELETE SET NULL
);

-- TEST RESULTS
CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_test_id INT NOT NULL,
    result_value VARCHAR(100),
    result_numeric DECIMAL(10,2),
    is_abnormal TINYINT(1) DEFAULT 0,
    notes TEXT,
    entered_by INT,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_test_id) REFERENCES patient_tests(id) ON DELETE CASCADE,
    FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL
);

-- SHARED REPORTS (Email.Whatsapp,Viber,QR Code,PDF,Print)
CREATE TABLE IF NOT EXISTS shared_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    test_ids TEXT NOT NULL,
    shared_by INT,
    shared_via VARCHAR(20),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE SET NULL
);

-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type VARCHAR(30) NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$8K1p/a0dL1pwKdd7R0Y5SObyBvIOqiat0gRFKrRuRch1xJSvHPnWu', 'System Administrator', 'admin@DRlab.com', 'admin'),
('tech1', '$2y$10$8K1p/a0dL1pwKdd7R0Y5SObyBvIOqiat0gRFKrRuRch1xJSvHPnWu', 'Lab Technician', 'tech@DRLab.com', 'technician'),
('doctor1', '$2y$10$8K1p/a0dL1pwKdd7R0Y5SObyBvIOqiat0gRFKrRuRch1xJSvHPnWu', 'Dr. Rebwar Khalid ', 'doctor@DRLab.com', 'doctor');


INSERT INTO test_catalog (test_code, test_name, category, unit, normal_range_min, normal_range_max, normal_range_text, price) VALUES

-- Hematology
('CBC-WBC', 'White Blood Cell Count', 'Hematology', '×10³/µL', 4.50, 11.00, '4.5 - 11.0 ×10³/µL', 5000),
('CBC-RBC', 'Red Blood Cell Count', 'Hematology', '×10⁶/µL', 4.50, 5.50, '4.5 - 5.5 ×10⁶/µL', 5000),
('CBC-HGB', 'Hemoglobin', 'Hematology', 'g/dL', 13.50, 17.50, '13.5 - 17.5 g/dL (Male) | 12.0 - 16.0 g/dL (Female)', 5000),
('CBC-HCT', 'Hematocrit', 'Hematology', '%', 38.00, 50.00, '38 - 50% (Male) | 36 - 44% (Female)', 5000),
('CBC-PLT', 'Platelet Count', 'Hematology', '×10³/µL', 150.00, 400.00, '150 - 400 ×10³/µL', 5000),
('CBC-MCV', 'Mean Corpuscular Volume', 'Hematology', 'fL', 80.00, 100.00, '80 - 100 fL', 8000),
('CBC-MCH', 'Mean Corpuscular Hemoglobin', 'Hematology', 'pg', 27.00, 33.00, '27 - 33 pg', 8000),
('ESR', 'Erythrocyte Sedimentation Rate', 'Hematology', 'mm/hr', 0.00, 20.00, '0 - 20 mm/hr (Male) | 0 - 30 mm/hr (Female)', 10000),

-- Chemistry 
('GLU-F', 'Fasting Blood Glucose', 'Chemistry', 'mg/dL', 70.00, 100.00, '70 - 100 mg/dL', 8000),
('GLU-R', 'Random Blood Glucose', 'Chemistry', 'mg/dL', 70.00, 140.00, '70 - 140 mg/dL', 8000),
('HBA1C', 'Hemoglobin A1c', 'Chemistry', '%', 4.00, 5.60, '4.0 - 5.6% (Normal) | 5.7 - 6.4% (Pre-diabetic)', 12000),
('BUN', 'Blood Urea Nitrogen', 'Chemistry', 'mg/dL', 7.00, 20.00, '7 - 20 mg/dL', 10000),
('CREAT', 'Creatinine', 'Chemistry', 'mg/dL', 0.70, 1.30, '0.7 - 1.3 mg/dL (Male) | 0.6 - 1.1 mg/dL (Female)', 10000),
('URIC', 'Uric Acid', 'Chemistry', 'mg/dL', 3.50, 7.20, '3.5 - 7.2 mg/dL (Male) | 2.6 - 6.0 mg/dL (Female)', 10000),
('CHOL-T', 'Total Cholesterol', 'Chemistry', 'mg/dL', 0.00, 200.00, 'Desirable: < 200 mg/dL', 15000),
('CHOL-HDL', 'HDL Cholesterol', 'Chemistry', 'mg/dL', 40.00, 60.00, '> 40 mg/dL (desirable)', 15000),
('CHOL-LDL', 'LDL Cholesterol', 'Chemistry', 'mg/dL', 0.00, 100.00, 'Optimal: < 100 mg/dL', 15000),
('TG', 'Triglycerides', 'Chemistry', 'mg/dL', 0.00, 150.00, 'Normal: < 150 mg/dL', 15000),

-- Liver Function
('ALT', 'Alanine Aminotransferase (SGPT)', 'Liver Function', 'U/L', 7.00, 56.00, '7 - 56 U/L', 10000),
('AST', 'Aspartate Aminotransferase (SGOT)', 'Liver Function', 'U/L', 10.00, 40.00, '10 - 40 U/L', 10000),
('ALP', 'Alkaline Phosphatase', 'Liver Function', 'U/L', 44.00, 147.00, '44 - 147 U/L', 10000),
('TBIL', 'Total Bilirubin', 'Liver Function', 'mg/dL', 0.10, 1.20, '0.1 - 1.2 mg/dL', 10000),
('DBIL', 'Direct Bilirubin', 'Liver Function', 'mg/dL', 0.00, 0.30, '0.0 - 0.3 mg/dL', 10000),
('ALB', 'Albumin', 'Liver Function', 'g/dL', 3.50, 5.00, '3.5 - 5.0 g/dL', 10000),
('TP', 'Total Protein', 'Liver Function', 'g/dL', 6.00, 8.30, '6.0 - 8.3 g/dL', 10000),

-- Thyroid
('TSH', 'Thyroid Stimulating Hormone', 'Thyroid', 'mIU/L', 0.40, 4.00, '0.4 - 4.0 mIU/L', 20000),
('T3', 'Triiodothyronine', 'Thyroid', 'ng/dL', 80.00, 200.00, '80 - 200 ng/dL', 20000),
('T4', 'Thyroxine', 'Thyroid', 'µg/dL', 5.10, 14.10, '5.1 - 14.1 µg/dL', 20000),
('FT4', 'Free Thyroxine', 'Thyroid', 'ng/dL', 0.93, 1.70, '0.93 - 1.70 ng/dL', 20000),

-- Electrolytes
('NA', 'Sodium', 'Electrolytes', 'mEq/L', 136.00, 145.00, '136 - 145 mEq/L', 10000),
('K', 'Potassium', 'Electrolytes', 'mEq/L', 3.50, 5.00, '3.5 - 5.0 mEq/L', 10000),
('CL', 'Chloride', 'Electrolytes', 'mEq/L', 98.00, 106.00, '98 - 106 mEq/L', 10000),
('CA', 'Calcium', 'Electrolytes', 'mg/dL', 8.50, 10.50, '8.5 - 10.5 mg/dL', 10000),
('MG', 'Magnesium', 'Electrolytes', 'mg/dL', 1.70, 2.20, '1.7 - 2.2 mg/dL', 12000),
('PO4', 'Phosphorus', 'Electrolytes', 'mg/dL', 2.50, 4.50, '2.5 - 4.5 mg/dL', 12000),

-- Coagulation
('PT', 'Prothrombin Time', 'Coagulation', 'seconds', 11.00, 13.50, '11.0 - 13.5 seconds', 15000),
('INR', 'International Normalized Ratio', 'Coagulation', '', 0.80, 1.10, '0.8 - 1.1', 15000),
('PTT', 'Partial Thromboplastin Time', 'Coagulation', 'seconds', 25.00, 35.00, '25 - 35 seconds', 15000),

-- Urinalysis
('UA-PH', 'Urine pH', 'Urinalysis', '', 4.50, 8.00, '4.5 - 8.0', 8000),
('UA-SG', 'Urine Specific Gravity', 'Urinalysis', '', 1.005, 1.030, '1.005 - 1.030', 8000),
('UA-GLU', 'Urine Glucose', 'Urinalysis', '', NULL, NULL, 'Negative', 8000),
('UA-PRO', 'Urine Protein', 'Urinalysis', '', NULL, NULL, 'Negative', 8000),

-- Immunology
('CRP', 'C-Reactive Protein', 'Immunology', 'mg/L', 0.00, 10.00, '< 10 mg/L', 8000),
('RF', 'Rheumatoid Factor', 'Immunology', 'IU/mL', 0.00, 14.00, '< 14 IU/mL', 8000),
('HIV', 'HIV 1/2 Antibody', 'Immunology', '', NULL, NULL, 'Non-Reactive', 8000),
('HBSAG', 'Hepatitis B Surface Antigen', 'Immunology', '', NULL, NULL, 'Non-Reactive',8000),
('HCV', 'Hepatitis C Antibody', 'Immunology', '', NULL, NULL, 'Non-Reactive', 8000),

-- Hormones
('TESTO', 'Testosterone', 'Hormones', 'ng/dL', 270.00, 1070.00, '270 - 1070 ng/dL (Male) | 15 - 70 ng/dL (Female)', 35000),
('PSA', 'Prostate Specific Antigen', 'Hormones', 'ng/mL', 0.00, 4.00, '0 - 4.0 ng/mL', 30000),
('VITD', 'Vitamin D (25-OH)', 'Hormones', 'ng/mL', 30.00, 100.00, '30 - 100 ng/mL (Sufficient)', 20000),
('B12', 'Vitamin B12', 'Hormones', 'pg/mL', 200.00, 900.00, '200 - 900 pg/mL', 20000),
('IRON', 'Serum Iron', 'Chemistry', 'µg/dL', 60.00, 170.00, '60 - 170 µg/dL (Male) | 37 - 145 µg/dL (Female)', 12000),
('FERR', 'Ferritin', 'Chemistry', 'ng/mL', 20.00, 250.00, '20 - 250 ng/mL (Male) | 10 - 120 ng/mL (Female)', 20000);
