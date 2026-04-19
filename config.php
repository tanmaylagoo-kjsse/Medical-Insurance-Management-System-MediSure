<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect
$conn = new mysqli("localhost", "root", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create DB
$conn->query("CREATE DATABASE IF NOT EXISTS medisur_db");
$conn->select_db("medisur_db");


// ================= USERS =================
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100),
    password VARCHAR(255)
)");


// ================= PROVIDERS =================
$conn->query("CREATE TABLE IF NOT EXISTS providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE,
    contact_email VARCHAR(100),
    phone VARCHAR(20)
)");


// ================= PLANS =================
$conn->query("CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    provider_id INT,
    type VARCHAR(50),
    price VARCHAR(50),
    description TEXT,
    FOREIGN KEY (provider_id) REFERENCES providers(id)
)");

// ✅ Add claim_amount safely
$check = $conn->query("SHOW COLUMNS FROM plans LIKE 'claim_amount'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE plans ADD claim_amount VARCHAR(50)");
}


// ================= PURCHASES =================
$conn->query("CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    plan_id INT,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
)");

// ✅ Add purchase_amount safely
$check = $conn->query("SHOW COLUMNS FROM purchases LIKE 'purchase_amount'");
if ($check->num_rows == 0) {
    $conn->query("ALTER TABLE purchases ADD purchase_amount DECIMAL(10,2)");
}


// ================= FEEDBACK =================
$conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rating INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");


// ================= CONTACT =================
$conn->query("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100),
    email VARCHAR(100),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB");


// ================= SEED DATA =================

// Providers
$conn->query("INSERT IGNORE INTO providers (id, name, contact_email, phone) VALUES
(1, 'Star Health', 'support@starhealth.com', '9876543210'),
(2, 'HDFC ERGO', 'help@hdfcergo.com', '9123456780'),
(3, 'ICICI Lombard', 'care@icicilombard.com', '9988776655')
");

// Plans (✅ FIXED with claim_amount)
$conn->query("INSERT IGNORE INTO plans (id, name, provider_id, type, price, claim_amount, description) VALUES
(1, 'Basic Health Plan', 1, 'Individual', '3000', '100000', 'Covers basic hospitalization expenses'),
(2, 'Family Care Plan', 2, 'Family', '8000', '300000', 'Covers entire family with maternity benefits'),
(3, 'Premium Plus Plan', 3, 'Individual', '12000', '500000', 'High coverage with critical illness benefits')
");

// Purchases
$conn->query("INSERT IGNORE INTO purchases (id, user_id, plan_id, purchase_amount, purchase_date) VALUES
(1, 1, 1, 3000.00, '2026-03-20 10:30:00'),
(2, 2, 2, 8000.00, '2026-03-21 14:15:00'),
(3, 3, 3, 12000.00, '2026-03-22 18:45:00')
");

// Feedback
$conn->query("INSERT IGNORE INTO feedback (id, user_id, rating, message) VALUES
(1, 1, 5, 'Excellent plans and smooth experience'),
(2, 2, 4, 'Good coverage but premium is slightly high'),
(3, 3, 5, 'Very reliable insurance service')
");

// Contact
$conn->query("INSERT IGNORE INTO contact_messages (id, user_id, name, email, message) VALUES
(1, 1, 'Malhar Kausadikar', 'malhar@gmail.com', 'I want more details about family plans'),
(2, 2, 'Anuj Vajha', 'anuj@gmail.com', 'How do I claim insurance?'),
(3, 3, 'Tanmay Lagoo', 'tanmay@gmail.com', 'Do you cover pre-existing diseases?')
");


// ================= ADDITIONAL PROVIDERS =================
$conn->query("INSERT IGNORE INTO providers (name, contact_email, phone) VALUES
('Aditya Birla Health', 'support@adityabirlahealth.com', '9000100001'),
('HDFC ERGO', 'help@hdfcergo.com', '9000100002'),
('Care Health', 'care@carehealth.com', '9000100003'),
('Niva Bupa', 'support@nivabupa.com', '9000100004'),
('Bajaj Allianz', 'help@bajajallianz.com', '9000100007'),
('Tata AIG', 'support@tataaig.com', '9000100008'),
('Religare Care', 'care@religarecare.com', '9000100009'),
('Max Bupa', 'support@maxbupa.com', '9000100010'),
('New India Assurance', 'info@newindia.co.in', '9000100011'),
('Reliance General', 'help@reliancegeneral.com', '9000100012'),
('Care Insurance', 'support@careinsurance.com', '9000100013'),
('ManipalCigna', 'care@manipalcigna.com', '9000100014'),
('PetSure India', 'support@petsureindia.com', '9000100015'),
('Future Generali', 'help@futuregenerali.in', '9000100016'),
('Oriental Insurance', 'info@orientalinsurance.org.in', '9000100017')
");


// ================= BIG INSURANCE DATA =================
$conn->query("INSERT IGNORE INTO plans (name, provider_id, type, price, claim_amount, description) VALUES

-- COMPREHENSIVE
('All-in-One Coverage', (SELECT id FROM providers WHERE name='Aditya Birla Health'), 'Comprehensive', '100000', '1000000', 'Full coverage plan'),
('Premium Full Coverage', (SELECT id FROM providers WHERE name='HDFC ERGO'), 'Comprehensive', '115000', '5000000', 'Premium health coverage'),
('Unlimited Benefits Plan', (SELECT id FROM providers WHERE name='Care Health'), 'Comprehensive', '120000', '10000000', 'Unlimited recharge benefits'),
('ReAssure Plan', (SELECT id FROM providers WHERE name='Niva Bupa'), 'Comprehensive', '110000', '2500000', 'Auto reset coverage'),
('Complete Health Protect', (SELECT id FROM providers WHERE name='ICICI Lombard'), 'Comprehensive', '108000', '2000000', 'Complete protection'),
('Comprehensive Care', (SELECT id FROM providers WHERE name='Star Health'), 'Comprehensive', '105000', '1500000', 'Covers pre-existing diseases'),
('Health Guard', (SELECT id FROM providers WHERE name='Bajaj Allianz'), 'Comprehensive', '112000', '2000000', 'AYUSH + hospital cover'),
('MediCare Premier', (SELECT id FROM providers WHERE name='Tata AIG'), 'Comprehensive', '125000', '3000000', 'Premium services'),
('Care Plus Plan', (SELECT id FROM providers WHERE name='Religare Care'), 'Comprehensive', '102000', '1500000', 'No sub-limits'),

-- CRITICAL ILLNESS
('Cancer & Heart Cover', (SELECT id FROM providers WHERE name='Max Bupa'), 'Critical', '80000', '2000000', 'Critical illness cover'),
('High Payout Plan', (SELECT id FROM providers WHERE name='Tata AIG'), 'Critical', '92000', '5000000', 'High payout'),
('Wide Illness Plan', (SELECT id FROM providers WHERE name='Aditya Birla Health'), 'Critical', '85000', '2500000', '64 illnesses covered'),
('40+ Illness Plan', (SELECT id FROM providers WHERE name='HDFC ERGO'), 'Critical', '90000', '3000000', '40+ illnesses'),
('Lump Sum Plan', (SELECT id FROM providers WHERE name='ICICI Lombard'), 'Critical', '88000', '2500000', 'One-time payout'),
('Critical Care', (SELECT id FROM providers WHERE name='Bajaj Allianz'), 'Critical', '83000', '2000000', 'Tiered payout'),
('Cardiac Cover', (SELECT id FROM providers WHERE name='Star Health'), 'Critical', '79000', '1500000', 'Cardiac focused'),
('Multi Illness Plan', (SELECT id FROM providers WHERE name='Religare Care'), 'Critical', '86000', '2500000', 'Multiple claims'),
('Gov Critical Cover', (SELECT id FROM providers WHERE name='New India Assurance'), 'Critical', '75000', '1000000', 'Affordable'),

-- FAMILY
('Family Floater', (SELECT id FROM providers WHERE name='Bajaj Allianz'), 'Family', '120000', '1000000', 'Family shared cover'),
('Affordable Family', (SELECT id FROM providers WHERE name='Care Insurance'), 'Family', '105000', '700000', 'Budget plan'),
('High Family Plan', (SELECT id FROM providers WHERE name='HDFC ERGO'), 'Family', '118000', '2500000', 'High coverage'),
('Family Optima', (SELECT id FROM providers WHERE name='Star Health'), 'Family', '110000', '1500000', 'Auto restore'),
('ReAssure Family', (SELECT id FROM providers WHERE name='Niva Bupa'), 'Family', '125000', '3000000', 'Unlimited resets'),

-- INDIVIDUAL
('Basic Individual', (SELECT id FROM providers WHERE name='HDFC ERGO'), 'Individual', '50000', '500000', 'Entry plan'),
('Affordable Individual', (SELECT id FROM providers WHERE name='ICICI Lombard'), 'Individual', '45000', '300000', 'Low cost'),
('Wide Individual', (SELECT id FROM providers WHERE name='Star Health'), 'Individual', '58000', '700000', 'Wide cover'),
('Cashless Plan', (SELECT id FROM providers WHERE name='Niva Bupa'), 'Individual', '60000', '500000', 'Cashless hospitals'),

-- TRAVEL
('Worldwide Travel', (SELECT id FROM providers WHERE name='Reliance General'), 'Travel', '20000', '37500000', 'Global coverage'),
('Emergency Travel', (SELECT id FROM providers WHERE name='ICICI Lombard'), 'Travel', '25000', '18750000', 'Emergency cover'),
('Student Travel', (SELECT id FROM providers WHERE name='Tata AIG'), 'Travel', '22000', '7500000', 'Student plan'),
('Trip Cancellation', (SELECT id FROM providers WHERE name='Bajaj Allianz'), 'Travel', '18000', '150000', 'Trip refund')
");


$conn->query("INSERT IGNORE INTO providers (name, contact_email, phone) VALUES
('PetSure India', 'support@petsureindia.com', '9000100015'),
('Future Generali', 'help@futuregenerali.in', '9000100016'),
('Oriental Insurance', 'info@orientalinsurance.org.in', '9000100017'),
('Religare Care', 'care@religarecare.com', '9000100009'),
('Care Health', 'care@carehealth.com', '9000100003')
");

// ================= CORPORATE PLANS =================
$conn->query("INSERT IGNORE INTO plans (name, provider_id, type, price, claim_amount, description) VALUES

-- CORPORATE
('Group Health Basic',        (SELECT id FROM providers WHERE name='ICICI Lombard'),    'Corporate', '200000', '300000',  'Tailored group policy covering all employees for hospitalization, accidents, and critical illness benefits.'),
('Flexible Corporate Cover',  (SELECT id FROM providers WHERE name='Tata AIG'),         'Corporate', '250000', '500000',  'Customizable corporate cover with maternity, OPD, and mental wellness add-ons for your workforce.'),
('Business Protection Plan',  (SELECT id FROM providers WHERE name='Bajaj Allianz'),    'Corporate', '180000', '300000',  'End-to-end business protection including employee health, liability, and property damage coverage.'),
('Corporate Health Shield',   (SELECT id FROM providers WHERE name='HDFC ERGO'),        'Corporate', '300000', '500000',  'Comprehensive corporate health and liability policy with dedicated relationship manager and cashless claims.'),
('Group Mediclaim Plan',      (SELECT id FROM providers WHERE name='Niva Bupa'),        'Corporate', '220000', '300000',  'Group mediclaim plan covering in-patient hospitalization, day-care, and pre-existing conditions from day one.'),
('SME Enterprise Cover',      (SELECT id FROM providers WHERE name='Star Health'),      'Corporate', '260000', '400000',  'Scalable health cover for SMEs and enterprises with flexible sum insured and family floater add-ons.'),
('Workforce Protection Plan', (SELECT id FROM providers WHERE name='Reliance General'), 'Corporate', '280000', '500000',  'Workforce protection plan covering accidental death, disability, and medical expenses for all employees.'),
('Government Group Plan',     (SELECT id FROM providers WHERE name='New India Assurance'),'Corporate','150000','200000', 'Government-backed group mediclaim with transparent claim processing and pan-India hospital network.'),
('Flexi Benefit Plan',        (SELECT id FROM providers WHERE name='Aditya Birla Health'),'Corporate','270000','400000', 'Flexible benefit plan letting employees choose coverage modules — health, wellness, dental, and vision.'),

-- PET
('Dogs & Cats Cover',         (SELECT id FROM providers WHERE name='PetSure India'),    'Pet', '8000',  '50000', 'Covers vet consultation, surgery, hospitalization, and medication for dogs and cats up to age 10.'),
('Vet Bills & Treatments',    (SELECT id FROM providers WHERE name='Future Generali'),  'Pet', '10000', '75000', 'Reimburses vet bills for illness, injury, diagnostics, and prescribed medicines for your pet.'),
('Pet Health Coverage',       (SELECT id FROM providers WHERE name='Bajaj Allianz'),    'Pet', '9000',  '60000', 'Comprehensive pet health plan covering accidental injuries, illness treatment, and third-party liability.'),
('Accident & Illness Cover',  (SELECT id FROM providers WHERE name='Tata AIG'),         'Pet', '8000',  '50000', 'Covers accidental injuries and sudden illnesses with fast cashless claims at empanelled vet clinics.'),
('Government Pet Plan',       (SELECT id FROM providers WHERE name='New India Assurance'),'Pet','5000', '40000', 'Affordable government-backed plan covering dogs, cats, and livestock against illness and accidents.'),
('Surgery & Hospitalisation', (SELECT id FROM providers WHERE name='HDFC ERGO'),        'Pet', '12000', '80000', 'Covers surgical procedures, hospitalization, and post-operative care for insured pets.'),
('Wellness & Vaccination',    (SELECT id FROM providers WHERE name='Reliance General'), 'Pet', '6000',  '40000', 'Wellness-focused plan covering routine check-ups, vaccinations, de-worming, and dental cleaning.'),
('Livestock & Pet Cover',     (SELECT id FROM providers WHERE name='Oriental Insurance'),'Pet', '4000', '30000', 'Covers livestock and domestic pets against death due to accident, illness, or surgical complications.'),
('Comprehensive Pet Care',    (SELECT id FROM providers WHERE name='Star Health'),      'Pet', '11000', '75000', 'All-in-one pet plan covering preventive care, hospitalization, surgeries, and end-of-life expenses.'),

-- SENIOR
('Special Senior Healthcare', (SELECT id FROM providers WHERE name='Star Health'),       'Senior', '90000',  '2500000', 'Designed for 60–75 age group covering pre-existing diseases, domiciliary care, and annual check-ups.'),
('Low Waiting Period Plan',   (SELECT id FROM providers WHERE name='Religare Care'),     'Senior', '82000',  '2000000', 'Minimal waiting period for pre-existing conditions; covers hospitalization, OPD, and mental health.'),
('Comprehensive Senior Cover',(SELECT id FROM providers WHERE name='ICICI Lombard'),     'Senior', '88000',  '2500000', 'Senior-focused plan with no pre-policy medical test up to 65, covering chronic illness and home care.'),
('Senior First Plan',         (SELECT id FROM providers WHERE name='Niva Bupa'),         'Senior', '95000',  '3000000', 'Senior-first plan with guaranteed renewability, coverage for cataract, joint replacement, and dialysis.'),
('Secure Senior Plan',        (SELECT id FROM providers WHERE name='HDFC ERGO'),         'Senior', '85000',  '2000000', 'Secure plan for senior citizens with dedicated claims desk, personal health manager, and 24/7 support.'),
('Active Health Senior Plan', (SELECT id FROM providers WHERE name='Aditya Birla Health'),'Senior','78000',  '1500000', 'Rewards active seniors with premium discounts; covers hospitalization, OPD, and wellness programs.'),
('Silver Health Plan',        (SELECT id FROM providers WHERE name='Bajaj Allianz'),     'Senior', '83000',  '2000000', 'Silver plan with comprehensive cover for age-related illnesses, home nursing, and physiotherapy.'),
('Senior Care Plan',          (SELECT id FROM providers WHERE name='Care Health'),       'Senior', '76000',  '1500000', 'Senior care plan with monthly health monitoring, teleconsultation, and priority claim processing.'),
('Government Senior Cover',   (SELECT id FROM providers WHERE name='New India Assurance'),'Senior','65000', '1000000', 'Affordable government-backed senior cover with transparent terms and wide hospital network.')
");







?>