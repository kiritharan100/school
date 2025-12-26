-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2025 at 06:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_account`
--

CREATE TABLE `bank_account` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `bank_name` varchar(150) NOT NULL,
  `opening_balance` decimal(10,2) NOT NULL,
  `balance_as_it` date NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_account`
--

INSERT INTO `bank_account` (`id`, `location_id`, `bank_name`, `opening_balance`, `balance_as_it`, `status`) VALUES
(1, 2, 'SDEC Current Account', 5000.00, '2024-01-01', 1),
(2, 2, 'Petty Cash', 0.00, '2024-01-01', 1),
(3, 2, 'FF Current Account', 15748.00, '2025-01-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `bank_payment`
--

CREATE TABLE `bank_payment` (
  `pay_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `pay_date` date NOT NULL,
  `bank_id` int(11) NOT NULL,
  `record_number` int(11) NOT NULL,
  `voutcher_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `expense_account` int(11) NOT NULL,
  `income_account` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `memo` int(11) NOT NULL,
  `cheque_number` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_payment`
--

INSERT INTO `bank_payment` (`pay_id`, `location_id`, `pay_date`, `bank_id`, `record_number`, `voutcher_number`, `supplier_id`, `expense_account`, `income_account`, `amount`, `memo`, `cheque_number`, `status`) VALUES
(1, 2, '2025-12-24', 2, 1, '25125', 1, 6, 1, 25000, 0, 1254, 1),
(2, 2, '2025-12-24', 2, 2, '25126', 1, 6, 1, 25000, 0, 1255, 1),
(3, 2, '2025-12-24', 3, 3, '202545', 1, 6, 1, 5000, 0, 5455, 1),
(4, 2, '2025-12-23', 3, 4, '202546', 1, 6, 1, 250, 0, 5456, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bank_receipt`
--

CREATE TABLE `bank_receipt` (
  `rec_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `record_no` int(11) NOT NULL,
  `bank_id` int(11) NOT NULL,
  `rec_date` date NOT NULL,
  `receipt_number` varchar(25) NOT NULL,
  `income_account` int(11) NOT NULL,
  `memo` varchar(80) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_receipt`
--

INSERT INTO `bank_receipt` (`rec_id`, `location_id`, `record_no`, `bank_id`, `rec_date`, `receipt_number`, `income_account`, `memo`, `amount`, `status`) VALUES
(1, 2, 1, 3, '2025-12-23', '2548', 1, 'asd', 25000.00, 1),
(2, 2, 2, 3, '2025-12-24', '2545', 17, '12123', 25000.00, 1),
(3, 2, 3, 3, '2025-12-24', '', 2, '300', 250.00, 1),
(4, 2, 4, 2, '2025-12-24', '254', 1, '', 5000.00, 1),
(5, 2, 5, 1, '2025-12-24', '123243', 1, '', 5000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bank_transfer`
--

CREATE TABLE `bank_transfer` (
  `id` int(11) NOT NULL,
  `transaction_number` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `from_account` int(11) NOT NULL,
  `to_account` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_transfer`
--

INSERT INTO `bank_transfer` (`id`, `transaction_number`, `location_id`, `from_account`, `to_account`, `amount`, `status`) VALUES
(5, 5, 2, 3, 2, 6000.00, 1),
(6, 6, 2, 3, 1, 25000.00, 1),
(7, 7, 2, 1, 3, 5000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocation`
--

CREATE TABLE `budget_allocation` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `ex_id` int(11) NOT NULL,
  `r_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_allocation`
--

INSERT INTO `budget_allocation` (`id`, `location_id`, `ex_id`, `r_id`, `year`, `amount`) VALUES
(1, 2, 1, 17, 2026, 30000.00),
(2, 2, 12, 2, 2026, 2500.00),
(3, 2, 2, 3, 2026, 3000.00),
(4, 2, 9, 3, 2026, 25000.00),
(5, 2, 9, 8, 2026, 58000.00),
(6, 2, 8, 9, 2026, 5000.00),
(7, 2, 3, 10, 2026, 6000.00),
(8, 2, 11, 11, 2026, 28000.00),
(9, 2, 10, 8, 2026, 2800.00),
(10, 2, 11, 8, 2026, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `budget_opening_balance`
--

CREATE TABLE `budget_opening_balance` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `r_id` int(11) NOT NULL,
  `op_balance` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_opening_balance`
--

INSERT INTO `budget_opening_balance` (`id`, `location_id`, `year`, `r_id`, `op_balance`) VALUES
(1, 2, 2026, 17, 25000),
(2, 2, 2026, 8, 8000),
(3, 2, 2026, 9, 200),
(4, 2, 2026, 10, 1000);

-- --------------------------------------------------------

--
-- Table structure for table `client_registration`
--

CREATE TABLE `client_registration` (
  `c_id` int(10) NOT NULL,
  `client_name` varchar(300) NOT NULL,
  `coordinates` varchar(5000) NOT NULL,
  `md5_client` varchar(250) NOT NULL,
  `user_license` int(10) NOT NULL,
  `client_id` varchar(100) NOT NULL,
  `client_type` varchar(50) NOT NULL,
  `district` varchar(50) NOT NULL,
  `supply_by` varchar(50) NOT NULL,
  `client_email` varchar(100) NOT NULL,
  `client_phone` varchar(30) NOT NULL,
  `contact_name` varchar(80) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `regNumber` varchar(80) NOT NULL,
  `region` varchar(50) NOT NULL,
  `bank_and_branch` varchar(150) NOT NULL,
  `account_number` varchar(150) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `payment_sms` int(11) NOT NULL DEFAULT 0,
  `remindes_sms` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `client_registration`
--

INSERT INTO `client_registration` (`c_id`, `client_name`, `coordinates`, `md5_client`, `user_license`, `client_id`, `client_type`, `district`, `supply_by`, `client_email`, `client_phone`, `contact_name`, `contact_email`, `contact_phone`, `status`, `regNumber`, `region`, `bank_and_branch`, `account_number`, `account_name`, `payment_sms`, `remindes_sms`) VALUES
(2, 'T/Sampalthivu Tamil Maha Vidyalayam', '', 'c81e728d9d4c2f636f067f89cc14862c', 1, '3', 'DS', 'Trincomalee', '', '', '', '', '', '', 1, 'Trincomalee', 'Trincomalee', '', '', '', 0, 0),
(47, 'T/ Vidyalayam', '', 'c81e728d96f067f89cc1862c', 1, '4', 'DS', 'Trincomalee', '', '', '', '', '', '', 1, 'Trincomalee', 'Trincomalee', '', '', '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_code`
--

CREATE TABLE `expenditure_code` (
  `ex_id` int(11) NOT NULL,
  `ex_code` varchar(30) NOT NULL,
  `location_id` int(11) NOT NULL,
  `ex_english` varchar(150) NOT NULL,
  `ex_tamil` varchar(150) NOT NULL,
  `ex_sinhala` varchar(150) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenditure_code`
--

INSERT INTO `expenditure_code` (`ex_id`, `ex_code`, `location_id`, `ex_english`, `ex_tamil`, `ex_sinhala`, `status`) VALUES
(1, 'REX-1', 2, 'Expenditure on projects implemented for the school', 'பாடசாலைக்கு அமுல்படுத்தப்பட்ட திட்டங்களின் செலவினம்', '', 1),
(2, 'REX-2', 2, 'Office services / Equipment costs / Course overseas costs / Website costs', 'அலுவலகச் சேவை / உபகரணச் செலவுகள் / பாட வெளிநாட்டு செலவுகள் / இணையதள செலவுகள்', '', 1),
(3, 'REX-3', 2, 'Educational/Administrative/Personnel Services and Welfare Services with School Services', 'பாடசாலைக் சேவையுடன் கூடிய கல்வி / நிர்வாக / பணியாளர் சேவைகள் மற்றும் நலன் பயன் பணிகள்', '', 1),
(4, 'REX-4', 2, 'Staff salary', 'ஆளணி ஊதியம்', '', 1),
(5, 'REX-5', 2, 'Capital goods and equipment maintenance, repair', 'மூலதன பொருட்கள் மற்றும் உபகரண பராமரிப்பு, பழுது பார்த்தல்', '', 1),
(6, 'REX-6', 2, 'School building maintenance and repair', 'பாடசாலை கட்டிடப் பராமரிப்பு மற்றும் பழுது பார்த்தல்', '', 1),
(7, 'REX-7', 2, 'Cleaning and sanitation costs', 'தூய்மை மற்றும் சுகாதாரச் செலவுகள்', '', 1),
(8, 'CEX-1', 2, 'Basic facilities –  supply', 'அடிப்படை  வசதிகள் – வழங்கல்', '', 1),
(9, 'CEX-2', 2, 'Capital expenditure on services implemented for the school', 'பாடசாலைக்கு அமுல்படுத்தப்பட்ட சேவையான மூலதன செலவினம்', '', 1),
(10, 'CEX-3', 2, 'School Welfare Fund Allowance', 'பாடசாலை நல நிதி கொடுப்பனவு', '', 1),
(11, 'CEX-4', 2, 'School building new construction development and other capital expenditure', 'பாடசாலை கட்டிட புதிய நிர்மாணிப்பு அபிவிருத்தி மற்றும் ஏனைய மூலதன செலவினம்', '', 1),
(12, 'CEX-5', 2, 'Capital Goods and Equipment Purchase (Furniture and Emergency Equipment)', 'மூலதன பொருட்கள் மற்றும் உபகரணக் கொள்முதல் (தளபாடம் மற்றும் அவசர உபகரணம்)', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `letter_head`
--

CREATE TABLE `letter_head` (
  `id` int(11) NOT NULL,
  `entity` varchar(60) NOT NULL,
  `address` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `telephone` varchar(100) NOT NULL,
  `vat_no` varchar(20) NOT NULL,
  `reg_no` varchar(100) NOT NULL,
  `invoice_prefix` varchar(10) NOT NULL,
  `admin_device_approval` int(11) NOT NULL DEFAULT 0,
  `company_name` varchar(100) NOT NULL,
  `VAT` varchar(50) NOT NULL,
  `gm_mobile` varchar(12) NOT NULL,
  `system_email` varchar(50) NOT NULL,
  `domain` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `letter_head`
--

INSERT INTO `letter_head` (`id`, `entity`, `address`, `email`, `telephone`, `vat_no`, `reg_no`, `invoice_prefix`, `admin_device_approval`, `company_name`, `VAT`, `gm_mobile`, `system_email`, `domain`) VALUES
(1, 'School', '', 'E-Mail: ', 'Phone ', '', '', '', 0, 'School', '', '0', '', ' ');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT NULL,
  `try_for` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`ip_address`, `attempt_time`, `try_for`) VALUES
('::1', '2025-12-02 14:17:20', 'reset'),
('::1', '2025-12-02 14:19:03', 'reset'),
('::1', '2025-12-13 22:16:40', 'setup_password'),
('::1', '2025-12-13 22:16:57', 'setup_password_final');

-- --------------------------------------------------------

--
-- Table structure for table `manage_activities`
--

CREATE TABLE `manage_activities` (
  `act_id` int(11) NOT NULL,
  `activity` varchar(50) NOT NULL,
  `module` varchar(20) NOT NULL,
  `is_menu` int(11) NOT NULL DEFAULT 0,
  `activity_url` varchar(150) NOT NULL,
  `icon_script` varchar(60) NOT NULL,
  `order_no` int(11) NOT NULL DEFAULT 1000,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `manage_activities`
--

INSERT INTO `manage_activities` (`act_id`, `activity`, `module`, `is_menu`, `activity_url`, `icon_script`, `order_no`, `status`) VALUES
(1, 'Manage Users', 'Admin', 1, 'manage_user.php', '', 1, 1),
(2, 'Manage DS Division', 'Admin', 1, '', '', 1000, 1),
(3, 'Manage GN Division', 'Admin', 1, '', '', 1000, 1),
(4, 'Manage SMS Template', 'Admin', 0, '', '', 1000, 1),
(8, 'Long Term Lease > Write-Off Penalty & Premium', 'Land', 0, '', '', 1002, 1),
(9, 'Admin Module', 'Admin', 0, '', '', 1, 1),
(11, 'Divisional Secretariat Module', 'Land', 0, '', '', 1, 1),
(12, 'Long Term Lease > Register (Main Page)', 'Land', 0, '', '', 1, 1),
(13, 'Long Term Lease > Add, Edit Application', 'Land', 0, '', '', 2, 1),
(14, 'Long Term Lease Master (Setup Lease)', 'Admin', 0, 'lease_master.php', '', 2, 1),
(15, 'Admin Report', 'Admin', 0, '', '', 2000, 1),
(16, 'DS Report', 'Land', 0, '', '', 9999, 1),
(17, 'Short-Term Lease Management', 'Land', 0, '', '', 2000, 1),
(18, 'Long Term Lease > Payment Record', 'Land', 0, '', '', 900, 1),
(19, 'Long Term Lease > Payment Cancellation', 'Land', 0, '', '', 901, 1),
(20, 'Long Term Lease > Edit Lease Details ', 'Land', 0, '', '', 902, 1),
(21, 'Long Term Lease > Edit Land Information', 'Land', 0, '', '', 1000, 1),
(22, 'Long Term Lease > Delete Documents', 'Land', 0, '', '', 2000, 1),
(23, 'Long Term Lease > Add document', 'Land', 0, '', '', 1999, 1);

-- --------------------------------------------------------

--
-- Table structure for table `manage_supplier`
--

CREATE TABLE `manage_supplier` (
  `sup_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `income_account` int(11) NOT NULL,
  `expense_account` int(11) NOT NULL,
  `supplier_name` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manage_supplier`
--

INSERT INTO `manage_supplier` (`sup_id`, `location_id`, `income_account`, `expense_account`, `supplier_name`, `status`) VALUES
(1, 2, 1, 6, 'Electricty Board', 1);

-- --------------------------------------------------------

--
-- Table structure for table `manage_user_group`
--

CREATE TABLE `manage_user_group` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `manage_user_group`
--

INSERT INTO `manage_user_group` (`group_id`, `group_name`, `status`) VALUES
(1, 'System Admin', 1);

-- --------------------------------------------------------

--
-- Table structure for table `revinue_category`
--

CREATE TABLE `revinue_category` (
  `rev_cat` int(11) NOT NULL,
  `category_english` varchar(100) NOT NULL,
  `category_sinhala` varchar(100) NOT NULL,
  `category_tamil` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revinue_category`
--

INSERT INTO `revinue_category` (`rev_cat`, `category_english`, `category_sinhala`, `category_tamil`) VALUES
(1, 'A. Funding for School Education Development', 'අ. පාසල් අධ්‍යාපන සංවර්ධනය සඳහා අරමුදල් සැපයීම', 'அ. பாடசாலை கல்வி அபிவிருத்திக்கான நிதி மூலம்'),
(2, 'B. External finance', 'ආ. බාහිර මූල්‍ය', 'ஆ. வெளி நிதி'),
(3, 'C. Funds from other sources', 'ඉ. වෙනත් මූලාශ්‍රවලින් ලැබෙන අරමුදල්', 'இ. ஏனைய வகைகளிலிருந்து கிடைக்கும் நிதியம்');

-- --------------------------------------------------------

--
-- Table structure for table `revinue_code`
--

CREATE TABLE `revinue_code` (
  `r_id` int(11) NOT NULL,
  `locaton_id` int(11) NOT NULL,
  `has_sub_category` int(11) NOT NULL DEFAULT 0,
  `main_cat_id` int(11) NOT NULL DEFAULT 0,
  `revinue_code` varchar(15) NOT NULL,
  `revinue_category_id` int(50) NOT NULL,
  `detail_of_revinue` varchar(250) NOT NULL,
  `revinue_tamil` varchar(250) NOT NULL,
  `revinue_sinhala` varchar(250) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revinue_code`
--

INSERT INTO `revinue_code` (`r_id`, `locaton_id`, `has_sub_category`, `main_cat_id`, `revinue_code`, `revinue_category_id`, `detail_of_revinue`, `revinue_tamil`, `revinue_sinhala`, `status`) VALUES
(1, 2, 1, 0, 'S1', 1, 'Consolidated Fund/Provincial Council Fund', 'இணைய நிதியம் / மாகாண சபை நிதி', '', 1),
(2, 2, 0, 0, 'S2', 1, 'The signing of an agreement between ......', 'பல பகுதிகள் மற்றும் இரு பகுதிகளுக்கிடையிலான உடன்படிக்கையின் கிழ் செய்யப்படுவதும்', '', 1),
(3, 2, 0, 0, 'S3', 1, 'Materials available from other government departments/funds approved by the student department', 'அரசு துறையின் ஏனைய நிறுவனங்களிலிருந்து கிடைக்கும்   /  மாகாண திறைசேரி அங்கீகரிக்கப்பட்ட நிதி', '', 1),
(4, 2, 1, 0, 'S4', 1, 'Funding for learning activities, including learning organization and work-based organization, to develop the school under the Central Government and Provincial Council allocations', 'மத்திய அரசு மற்றும் மாகாண சபை ஒதுக்கீட்டின் கிழ் பாடசாலையை அபிவிருத்தி யாக்க கொண்டு கற்றல் அமைப்பும், பணி சார்ந்த ஒழுங்கமைப்பும் உட்பட கற்றல் செயற்பாடுகளுக்கான நிதி', '', 1),
(5, 2, 1, 0, 'S5', 2, 'Financial assistance   from government-approved and registered non-governmental organizations', 'அரசினால் அங்கீகரிக்கப்பட்ட மற்றும் பதிவு செய்யப்பட்ட அரசு சார்பற்ற அமைப்புகளில் இருந்து கிடைக்கும் நிதி உதவி', '', 1),
(6, 2, 0, 0, 'S6', 2, 'Voluntary donations from parents, well-wishers, and the student union', 'பெற்றோர், நலன் விரும்பிகள், மாணவர் சங்கத்தின் சுய விருப்ப பேரில் வழங்கப்படும் அன்பளிப்புக்கள்', '', 1),
(7, 2, 1, 0, 'S7', 3, 'Income from school land and buildings', 'பாடசாலை காணி மற்றும் கட்டடங்களிலிருந்து கிடைக்கும் வருமானம்', '', 1),
(8, 2, 0, 0, 'S8', 3, 'Membership fees received from members of the School Development Association', 'பாடசாலை அபிவிருத்தி சங்கத்தின் அங்கத்தவர்களிலிருந்து கிடைக்கும் அங்கத்துவப் பணம்', '', 1),
(9, 2, 0, 0, 'S9', 3, 'Income generated from various activities and operations of the school', 'பாடசாலையின் பல்வேறு செயற்பாடுகள் மற்றும் செயற்பாட்டுக்களினால் உண்டாகப்படும் வருமானம்', '', 1),
(10, 2, 0, 0, 'S10', 3, 'Other funds as determined by the School Development Association.', 'பாடசாலை அபிவிருத்தி சங்கத்தினால் தீர்மானிக்கப்படும் ஏனைய நிதிகள்', '', 1),
(11, 2, 0, 0, 'S11', 3, 'Facilities Service Charge', 'வசதிகள்  சேவைக்கட்டணம்', '', 1),
(12, 2, 0, 4, 'S4.1', 1, 'Quality input', 'தர உள்ளீடு', '', 1),
(13, 2, 0, 4, 'S4.2', 1, 'GEM English', 'GEM English', '', 1),
(14, 2, 0, 4, 'S4.3', 1, 'GEM', 'GEM', '', 1),
(15, 2, 0, 4, 'S4.4', 1, 'GEM MATHS', 'GEM MATHS', '', 1),
(16, 2, 0, 4, 'S4.5', 1, 'GEM CG CARRIER GUIDENCE', 'GEM CG CARRIER GUIDENCE', '', 1),
(17, 2, 0, 1, 'S1.1', 1, 'Electricity Telephone', 'மீளநிறம்பல்  நிதி மின்சாரம் தொலைபேசி', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `transaction_type` varchar(10) NOT NULL,
  `voutcher_number` varchar(10) NOT NULL,
  `tr_date` date NOT NULL,
  `bank_account_id` int(11) NOT NULL,
  `income_account` int(11) NOT NULL,
  `expenses_account` int(11) NOT NULL,
  `memo` varchar(500) NOT NULL,
  `debit` decimal(10,2) NOT NULL,
  `credit` decimal(10,2) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id`, `location_id`, `transaction_id`, `transaction_type`, `voutcher_number`, `tr_date`, `bank_account_id`, `income_account`, `expenses_account`, `memo`, `debit`, `credit`, `supplier_id`, `status`) VALUES
(1, 2, 0, 'Transfer', '', '2020-01-01', 2, 0, 0, '', 0.00, 22.00, 0, 1),
(2, 2, 0, 'Transfer', '', '2020-01-01', 1, 0, 0, '', 22.00, 0.00, 0, 1),
(11, 2, 5, 'Transfer', 'BT-5', '2025-11-01', 3, 0, 0, '', 0.00, 6000.00, 0, 1),
(12, 2, 5, 'Transfer', 'BT-5', '2025-11-01', 2, 0, 0, '', 6000.00, 0.00, 0, 1),
(13, 2, 6, 'Transfer', 'BT-6', '2025-12-23', 3, 0, 0, '12', 0.00, 25000.00, 0, 1),
(14, 2, 6, 'Transfer', 'BT-6', '2025-12-23', 1, 0, 0, '12', 25000.00, 0.00, 0, 1),
(15, 2, 7, 'Transfer', 'BT-7', '2025-12-23', 1, 0, 0, '2', 0.00, 5000.00, 0, 1),
(16, 2, 7, 'Transfer', 'BT-7', '2025-12-23', 3, 0, 0, '2', 5000.00, 0.00, 0, 1),
(19, 2, 1, 'Receipt', 'BR-1', '2025-12-23', 3, 0, 0, 'asd', 25000.00, 0.00, 0, 1),
(20, 2, 1, 'Receipt', 'BR-1', '2025-12-23', 0, 1, 0, 'asd', 0.00, 25000.00, 0, 1),
(21, 2, 2, 'Receipt', 'BR-2', '2025-12-24', 3, 0, 0, '12123', 25000.00, 0.00, 0, 1),
(22, 2, 2, 'Receipt', 'BR-2', '2025-12-24', 0, 17, 0, '12123', 0.00, 25000.00, 0, 1),
(23, 2, 3, 'Receipt', 'BR-3', '2025-12-24', 3, 0, 0, '300', 250.00, 0.00, 0, 1),
(24, 2, 3, 'Receipt', 'BR-3', '2025-12-24', 0, 2, 0, '300', 0.00, 250.00, 0, 1),
(25, 2, 1, 'Payment', '25125', '2025-12-24', 0, 0, 6, '', 25000.00, 0.00, 1, 1),
(26, 2, 1, 'Payment', '25125', '2025-12-24', 2, 0, 0, '', 0.00, 25000.00, 1, 1),
(27, 2, 2, 'Payment', '25126', '2025-12-24', 0, 0, 6, '', 25000.00, 0.00, 1, 1),
(28, 2, 2, 'Payment', '25126', '2025-12-24', 2, 0, 0, '', 0.00, 25000.00, 1, 1),
(29, 2, 4, 'Receipt', 'BR-4', '2025-12-24', 2, 0, 0, '', 5000.00, 0.00, 0, 1),
(30, 2, 4, 'Receipt', 'BR-4', '2025-12-24', 0, 1, 0, '', 0.00, 5000.00, 0, 1),
(31, 2, 3, 'Payment', '202545', '2025-12-24', 0, 0, 6, '', 5000.00, 0.00, 1, 1),
(32, 2, 3, 'Payment', '202545', '2025-12-24', 3, 0, 0, '', 0.00, 5000.00, 1, 1),
(33, 2, 5, 'Receipt', 'BR-5', '2025-12-24', 1, 0, 0, '', 5000.00, 0.00, 0, 1),
(34, 2, 5, 'Receipt', 'BR-5', '2025-12-24', 0, 1, 0, '', 0.00, 5000.00, 0, 1),
(35, 2, 4, 'Payment', '202546', '2025-12-23', 0, 0, 6, '', 250.00, 0.00, 1, 1),
(36, 2, 4, 'Payment', '202546', '2025-12-23', 3, 0, 0, '', 0.00, 250.00, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_device`
--

CREATE TABLE `user_device` (
  `d_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pf_no` varchar(100) NOT NULL,
  `token` varchar(200) NOT NULL,
  `v_from` datetime NOT NULL DEFAULT current_timestamp(),
  `IP` varchar(200) NOT NULL,
  `last_used` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_device`
--

INSERT INTO `user_device` (`d_id`, `user_id`, `pf_no`, `token`, `v_from`, `IP`, `last_used`) VALUES
(1, 0, '0740888501', '1f71e393b3809197ed66df836fe833e5', '2025-07-24 05:32:08', '', ''),
(2, 0, '0740888501', '4079016d940210b4ae9ae7d41c4a2065', '2025-07-25 09:20:25', '', ''),
(3, 0, '0753289502', '68148596109e38cf9367d27875e185be', '2025-07-25 09:27:08', '', ''),
(4, 0, '0740888501', 'a0872cc5b5ca4cc25076f3d868e1bdf8', '2025-07-25 13:10:57', '', ''),
(5, 0, '0740888501', 'd6723e7cd6735df68d1ce4c704c29a04', '2025-07-31 20:19:25', '', ''),
(6, 0, '0711986868', '55c567fd4395ecef6d936cf77b8d5b2b', '2025-08-01 10:36:04', '', ''),
(7, 0, '0740888501', 'f63f65b503e22cb970527f23c9ad7db1', '2025-08-03 20:40:02', '', ''),
(8, 0, '0740888501', 'dca5672ff3444c7e997aa9a2c4eb2094', '2025-08-06 15:55:03', '', ''),
(9, 0, '0740888501', '571e0f7e2d992e738adff8b1bd43a521', '2025-08-22 18:33:35', '', ''),
(10, 0, '0740888501', 'e3251075554389fe91d17a794861d47b', '2025-09-14 08:15:33', '', ''),
(11, 0, '0740888501', 'e8dfff4676a47048d6f0c4ef899593dd', '2025-10-11 13:07:01', '', ''),
(12, 0, '0740888501', '61b1fb3f59e28c67f3925f3c79be81a1', '2025-10-18 13:16:51', '', ''),
(13, 0, '0740888501', '7e9e346dc5fd268b49bf418523af8679', '2025-10-21 13:47:20', '', ''),
(14, 0, '0740888501', 'b710915795b9e9c02cf10d6d2bdb688c', '2025-10-21 14:32:19', '', ''),
(15, 0, '0740888501', '19de10adbaa1b2ee13f77f679fa1483a', '2025-10-25 17:50:58', '', ''),
(16, 0, '0740888501', '4da04049a062f5adfe81b67dd755cecc', '2025-10-29 08:55:43', '', ''),
(17, 0, '0740888501', '90599c8fdd2f6e7a03ad173e2f535751', '2025-11-02 17:45:33', '', ''),
(18, 0, '0740888501', 'f60bb6bb4c96d4df93c51bd69dcc15a0', '2025-11-02 19:01:53', '', ''),
(19, 0, '0740888501', 'f442d33fa06832082290ad8544a8da27', '2025-11-19 10:25:23', '', ''),
(20, 0, '0740888501', '15231a7ce4ba789d13b722cc5c955834', '2025-11-19 21:09:32', '', ''),
(21, 0, '0740888501', 'a223c6b3710f85df22e9377d6c4f7553', '2025-11-26 09:48:32', '', ''),
(22, 0, '0740888501', 'c45008212f7bdf6eab6050c2a564435a', '2025-11-29 07:41:26', '', ''),
(23, 0, '0740888501', '36a16a2505369e0c922b6ea7a23a56d2', '2025-12-02 09:03:21', '', ''),
(24, 0, '0740888501', '4921f95baf824205e1b13f22d60357a1', '2025-12-02 14:48:31', '', ''),
(25, 0, '0740888501', 'd8d31bd778da8bdd536187c36e48892b', '2025-12-03 09:04:59', '', ''),
(26, 0, '0740888501', '05311655a15b75fab86956663e1819cd', '2025-12-06 09:20:26', '', ''),
(27, 0, '0740888501', 'db1915052d15f7815c8b88e879465a1e', '2025-12-09 08:28:35', '', ''),
(28, 0, '0740888501', '97af4fb322bb5c8973ade16764156bed', '2025-12-09 15:04:10', '', ''),
(29, 0, '0740888501', 'e00406144c1e7e35240afed70f34166a', '2025-12-13 21:29:02', '', ''),
(30, 0, '0740888501', 'e53a0a2978c28872a4505bdb51db06dc', '2025-12-13 21:33:48', '', ''),
(31, 0, '0740888501', '883e881bb4d22a7add958f2d6b052c9f', '2025-12-13 21:37:25', '', ''),
(32, 0, '0740888501', '97af4fb322bb5c8973ade16764156bed', '2025-12-13 21:40:16', '', ''),
(33, 0, '0740888501', 'ae614c557843b1df326cb29c57225459', '2025-12-13 21:42:00', '', ''),
(34, 0, '0740888501', '90e1357833654983612fb05e3ec9148c', '2025-12-13 21:43:46', '', ''),
(35, 0, '0770888501', '15d185eaa7c954e77f5343d941e25fbd', '2025-12-13 22:17:31', '', ''),
(36, 0, '0740888501', '1587965fb4d4b5afe8428a4a024feb0d', '2025-12-23 20:30:38', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `user_license`
--

CREATE TABLE `user_license` (
  `usr_id` int(11) NOT NULL,
  `customer` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `mobile_no` varchar(12) NOT NULL,
  `role_id` int(11) NOT NULL,
  `password` varchar(500) NOT NULL,
  `user_rights` varchar(100) NOT NULL,
  `account_status` int(5) NOT NULL DEFAULT 2,
  `i_name` varchar(50) NOT NULL,
  `nic` varchar(30) NOT NULL,
  `company` varchar(500) NOT NULL,
  `token` varchar(112) NOT NULL,
  `dr_token` varchar(112) NOT NULL,
  `last_log_in` varchar(50) NOT NULL,
  `last_token` varchar(180) NOT NULL,
  `material` int(11) NOT NULL,
  `accounts` int(11) NOT NULL,
  `store` int(11) NOT NULL,
  `admin` int(11) NOT NULL,
  `report` int(11) NOT NULL,
  `opd` int(11) NOT NULL,
  `ip` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_license`
--

INSERT INTO `user_license` (`usr_id`, `customer`, `username`, `mobile_no`, `role_id`, `password`, `user_rights`, `account_status`, `i_name`, `nic`, `company`, `token`, `dr_token`, `last_log_in`, `last_token`, `material`, `accounts`, `store`, `admin`, `report`, `opd`, `ip`) VALUES
(1, 0, '0740888501', '', 1, '$2y$10$nY48qthOlivF5rw0cWRZ2.PgsKMHmkFzKmaDjueMVuqcRBS8cu4X.', '', 1, 'Sys Admin', '', '', 'Expired', '8361', '2025-12-26 08:27:17', 'c4371362b388a1953403395d43a20f8467dd60a1ea1e18765e7e28c2dbce4273', 0, 1, 1, 1, 0, 0, 0),
(109, 0, '0770888501', '0770888501', 1, '$2y$10$fZMpMcCgrFXiQHb16FDVZurTdN.PEKCiuOU80Nyx79XSzbpPLrFfy', '', 1, 'Kiritharan', '893121', '', 'Expired', '6987', '2025-12-14 06:24:56', '0b3e9202c49c6faff69a500fabd43fd6657e5d8cdaa1c6dbf9dc7a6adc896ff9', 0, 1, 1, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_location`
--

CREATE TABLE `user_location` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_location`
--

INSERT INTO `user_location` (`id`, `usr_id`, `location_id`) VALUES
(36, 95, 4),
(37, 94, 4),
(38, 94, 3),
(39, 96, 3),
(40, 97, 3),
(41, 98, 3),
(85, 100, 35),
(179, 101, 38),
(180, 101, 42),
(181, 104, 38),
(182, 104, 39),
(183, 104, 41),
(184, 104, 40),
(189, 109, 47),
(190, 109, 2),
(191, 1, 47),
(192, 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `user_log`
--

CREATE TABLE `user_log` (
  `id` int(11) NOT NULL,
  `ben_id` int(11) DEFAULT NULL,
  `usr_id` int(11) NOT NULL,
  `module` int(11) NOT NULL,
  `location` int(11) NOT NULL,
  `action` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `detail` varchar(2500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `log_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_log`
--

INSERT INTO `user_log` (`id`, `ben_id`, `usr_id`, `module`, `location`, `action`, `detail`, `log_date`) VALUES
(1, NULL, 1, 1, 45, 'New User Created', 'User kiti created by admin', '2025-11-28 14:00:12'),
(2, NULL, 1, 1, 45, 'New User Created', 'User Kiritharan created by admin', '2025-11-28 15:51:34'),
(3, NULL, 1, 0, 45, 'Updated Profile', 'User Kiritharan updated by admin', '2025-11-28 16:00:48'),
(4, 27, 1, 2, 40, 'LTL Add Land', 'Land ID=1 | Ben ID=27  | Address=1212 | Extent= hectares', '2025-11-28 16:24:43'),
(5, 27, 1, 2, 40, 'LTL Edit Land', 'Land ID=1 | Extent:  > 2 | Extent ha:  > 2.000000', '2025-11-28 16:24:47'),
(6, 27, 1, 2, 40, 'LTL Create Lease', 'Created lease: Pending File No: DS/TR/LS/01', '2025-11-28 16:25:16'),
(7, 27, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=1 | Lease No=Pending | Changes: start_date: 2020-01-01 > 2020-01-02 | end_date: 2050-01-01 > 2050-01-02', '2025-11-28 16:25:38'),
(8, 27, 1, 2, 40, 'LTL New Payment', 'Lease ID=1 | Amount=5,000.00 | Rec No=50 | Method=cash | Date=2025-11-28 | Discount=NO', '2025-11-28 16:30:28'),
(9, 27, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: 50, Amount: 5000.00, Lease_file: DS/TR/LS/01', '2025-11-28 16:30:36'),
(10, 27, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=1 | Lease No=Pending | Changes: valuation_date: null > 2021-01-01', '2025-11-28 16:31:05'),
(11, 27, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=1, Schedule ID=92, Amount=1000.00, Old Penalty=1000.00, New Penalty=0.00', '2025-11-28 16:31:43'),
(12, 27, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=1, Lease ID=1, Schedule ID=92, Amount=1000.00', '2025-11-28 16:32:38'),
(13, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Name tamil:  > Karunakaran Sivalingam. | Address: 999 School road , Trincomalee ssadsadsad > 999 School road , Trincomalee ssadsadsad\\n', '2025-11-28 19:02:37'),
(14, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Name sinhala:  > Karunakaran Sivalingam. | Address: 999 School road , Trincomalee ssadsadsad > 999 School road , Trincomalee ssadsadsad\\n', '2025-11-28 19:02:45'),
(15, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Name: Karunakaran Sivalingam. > Karunakaran Sivalingam | Name tamil: Karunakaran Sivalingam. > ????????? ?????????? | Name sinhala: Karunakaran Sivalingam. > ????????? ????????? | Address: 999 School road , Trincomalee ssadsadsad > 999 School road , Trincomalee \\n', '2025-11-28 19:11:38'),
(16, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Address: 999 School road , Trincomalee > 999 School road , Trincomalee \\n', '2025-11-28 19:25:34'),
(17, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Address: 999 School road , Trincomalee > 999 School road , Trincomalee \\n | Address tamil:  > ????????? ?????????? | Address sinhala:  > ????????? ?????????', '2025-11-28 19:27:39'),
(18, 27, 1, 2, 40, 'LTL Document Uploaded', 'Ben ID=27 | File Type=1 | File URL=/files/40/1_24d7a0b831b6e6cac3efa75151e0d12b_20251128211629.pdf', '2025-11-28 21:16:29'),
(19, 27, 1, 2, 40, 'LTL Document Uploaded', 'Ben ID=27 | File Type=2 | File URL=/files/40/2_24d7a0b831b6e6cac3efa75151e0d12b_20251128211708.pdf', '2025-11-28 21:17:08'),
(20, NULL, 1, 2, 40, 'LTL Document Updated', 'Ben ID=27 | File Type=2 | File URL=/files/40/2_24d7a0b831b6e6cac3efa75151e0d12b_20251128211708.pdf', '2025-11-28 21:17:08'),
(21, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-11-29 06:47:51'),
(22, 27, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=1 | Lease No=Pending | Changes: valuation_date: null > 2021-01-01 | end_date: 2050-01-02 > 2080-01-02 | duration_years: 30.00 > 60.00', '2025-11-29 06:49:21'),
(23, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-11-29 07:32:44'),
(24, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-11-29 07:40:59'),
(25, NULL, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Address: 999 School road , Trincomalee > 999 School road , Trincomalee \\n | Address tamil: கருணாகரன் சிவலிங்கம் > கருணாகரன் சிவலிங்கம்.', '2025-11-29 07:44:43'),
(26, 27, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Address: 999 School road , Trincomalee > 999 School road , Trincomalee \\n | Address tamil: கருணாகரன் சிவலிங்கம். > கருணாகரன் சிவலிங்கம்', '2025-11-29 07:46:54'),
(27, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 09:00:18'),
(28, 27, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=27 | Address: 999 School road , Trincomalee > 999 School road , Trincomalee \\n | Address sinhala: කරුණාකරන් සිවලිංගම් > 898 ප්රධාන වීදිය, ත්රිකුණාමලය', '2025-12-02 09:41:22'),
(29, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:19:58'),
(30, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:23:17'),
(31, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:23:55'),
(32, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:26:25'),
(33, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:28:55'),
(34, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:29:33'),
(35, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:32:27'),
(36, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-02 14:48:09'),
(37, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-03 09:04:42'),
(38, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-03 10:02:42'),
(39, 27, 1, 2, 40, 'LTL New Payment', 'Lease ID=1 | Amount=46,000.00 | Rec No=sss | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 11:11:15'),
(40, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-03 17:57:31'),
(41, 28, 1, 2, 40, 'LTL Add Land', 'Land ID=2 | Ben ID=28  | Address=50 | Extent= hectares', '2025-12-03 20:03:05'),
(42, 28, 1, 2, 40, 'LTL Create Lease', 'Created lease: 12/asdsad File No: DS/TR/LS/45', '2025-12-03 20:05:20'),
(43, 28, 1, 2, 40, 'LTL New Payment', 'Lease ID=2 | Amount=1,372,000.00 | Rec No=s | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 20:05:34'),
(44, 28, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 1372000.00, Lease_file: DS/TR/LS/45', '2025-12-03 20:28:00'),
(45, 28, 1, 2, 40, 'LTL New Payment', 'Lease ID=2 | Amount=1,372,000.00 | Rec No=s | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 20:28:04'),
(46, 28, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 1372000.00, Lease_file: DS/TR/LS/45', '2025-12-03 20:28:13'),
(47, 28, 1, 2, 40, 'LTL New Payment', 'Lease ID=2 | Amount=1,372,000.00 | Rec No=sw | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 20:29:44'),
(48, 28, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sw, Amount: 1372000.00, Lease_file: DS/TR/LS/45', '2025-12-03 20:30:01'),
(49, 28, 1, 2, 40, 'LTL New Payment', 'Lease ID=2 | Amount=1,372,000.00 | Rec No=s | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 20:32:28'),
(50, 28, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 1372000.00, Lease_file: DS/TR/LS/45', '2025-12-03 20:32:45'),
(51, 28, 1, 2, 40, 'LTL New Payment', 'Lease ID=2 | Amount=1,446,000.00 | Rec No=12 | Method=cash | Date=2025-12-03 | Discount=NO', '2025-12-03 20:46:15'),
(52, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-04 20:47:00'),
(53, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-05 08:33:42'),
(54, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-05 08:56:21'),
(55, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-06 09:20:01'),
(56, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-06 16:56:16'),
(57, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-09 08:28:07'),
(58, 27, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=1 | Lease No=Pending | Changes: valuation_date: null > 2021-01-01 | end_date: 2080-01-02 > 2050-01-02 | duration_years: 60.00 > 30.00', '2025-12-09 09:09:47'),
(59, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-09 15:03:24'),
(60, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-09 15:04:18'),
(61, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-09 15:07:20'),
(62, 47, 1, 2, 40, 'LTL Beneficiary Created', 'ID=47 Name=Krunakaran K', '2025-12-09 15:11:57'),
(63, 47, 1, 2, 40, 'LTL Add Land', 'Land ID=3 | Ben ID=47  | Address=454 AASDSADSAD | Extent=50 perch', '2025-12-09 15:15:12'),
(64, 47, 1, 2, 40, 'LTL Create Lease', 'Created lease: Pending File No: DS/TR/LS/5/501', '2025-12-09 15:17:52'),
(65, 47, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=3 | Lease No=Pending | Changes: valuation_date: null > 2022-01-10', '2025-12-09 15:19:03'),
(66, 47, 1, 2, 40, 'LTL New Payment', 'Lease ID=3 | Amount=100,000.00 | Rec No=AAS11111 | Method=cash | Date=2023-12-09 | Discount=NO', '2025-12-09 15:21:29'),
(67, 47, 1, 2, 40, 'LTL Add Field Visits', 'Added field visit: id=1  | date=3 | officers=2025-12-10 | status=asd', '2025-12-09 15:22:14'),
(68, 47, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=3, Schedule ID=273, Amount=48000.00, Old Penalty=48000.00, New Penalty=0.00', '2025-12-09 15:23:01'),
(69, 47, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=2, Lease ID=3, Schedule ID=273, Amount=48000.00', '2025-12-09 15:33:03'),
(70, 47, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=3 | Lease No=45645/154 | Changes: valuation_date: null > 2022-01-10 | lease_number: Pending > 45645/154', '2025-12-09 15:33:17'),
(71, 47, 1, 2, 40, 'LTL Beneficiary Edited', 'ID=47 | Name sinhala: கருணாகரன் கே auto_awesome Translate from: Hindi 12 / 5,000 කරුණාකරන් කේ > කරුණාකරන් කේ', '2025-12-09 15:38:21'),
(72, 47, 1, 2, 40, 'LTL Add Reminders', 'Added reminder: lease_id=3 | type=Annexure 09 | sent_date=2025-11-09 ', '2025-12-09 15:41:19'),
(73, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-09 19:40:58'),
(74, 35, 1, 2, 40, 'LTL Add Land', 'Land ID=4 | Ben ID=35  | Address=Wilgama | Extent=0.1012 hectares', '2025-12-09 19:43:29'),
(75, 35, 1, 2, 40, 'LTL Create Lease', 'Created lease: Pending File No: DS/TR/LS/', '2025-12-09 19:45:35'),
(76, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=test | Method=cash | Date=2025-11-16 | Discount=NO', '2025-12-09 20:46:04'),
(77, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: test, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-09 20:47:15'),
(78, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=12 | Method=cash | Date=2025-06-11 | Discount=NO', '2025-12-09 20:47:55'),
(79, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: 12, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-09 20:50:34'),
(80, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=104,000.00 | Rec No=sadsad | Method=cash | Date=2024-06-11 | Discount=NO', '2025-12-09 20:51:12'),
(81, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sadsad, Amount: 104000.00, Lease_file: DS/TR/LS/', '2025-12-09 21:57:12'),
(82, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=dsf | Method=cash | Date=2025-06-11 | Discount=NO', '2025-12-09 21:57:41'),
(83, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: dsf, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-09 22:14:32'),
(84, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=sd | Method=cash | Date=2025-06-11 | Discount=NO', '2025-12-09 22:15:07'),
(85, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sd, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-09 22:50:15'),
(86, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=sss | Method=cash | Date=2025-06-11 | Discount=NO', '2025-12-09 22:50:36'),
(87, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-10 07:06:18'),
(88, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sss, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-10 08:03:18'),
(89, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=48,000.00 | Rec No=ss | Method=cash | Date=2025-09-08 | Discount=NO', '2025-12-10 08:04:11'),
(90, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=16,000.00 | Rec No=s | Method=cash | Date=2025-09-10 | Discount=NO', '2025-12-10 08:11:23'),
(91, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 16000.00, Lease_file: DS/TR/LS/', '2025-12-10 08:11:50'),
(92, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=16,000.00 | Rec No=dsd | Method=cash | Date=2025-10-09 | Discount=YES', '2025-12-10 08:12:20'),
(93, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: ss, Amount: 48000.00, Lease_file: DS/TR/LS/', '2025-12-10 08:15:03'),
(94, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: dsd, Amount: 16000.00, Lease_file: DS/TR/LS/', '2025-12-10 08:15:07'),
(95, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=32,000.00 | Rec No=s | Method=cash | Date=2023-09-21 | Discount=NO', '2025-12-10 08:16:06'),
(96, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=16,000.00 | Rec No=xs | Method=cash | Date=2024-09-21 | Discount=YES', '2025-12-10 08:17:39'),
(97, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: xs, Amount: 16000.00, Lease_file: DS/TR/LS/', '2025-12-10 10:15:36'),
(98, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 32000.00, Lease_file: DS/TR/LS/', '2025-12-10 10:15:39'),
(99, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=40,000.00 | Rec No=12 | Method=cash | Date=2024-08-19 | Discount=YES', '2025-12-10 10:21:00'),
(100, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: 12, Amount: 40000.00, Lease_file: DS/TR/LS/', '2025-12-10 10:32:31'),
(101, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=32,000.00 | Rec No=12 | Method=cash | Date=2023-09-24 | Discount=YES', '2025-12-10 10:33:41'),
(102, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=14,222.22 | Rec No=aaa | Method=cash | Date=2025-09-10 | Discount=NO', '2025-12-10 11:11:14'),
(103, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: aaa, Amount: 14222.22, Lease_file: DS/TR/LS/', '2025-12-10 12:08:20'),
(104, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=105,422.22 | Rec No=s | Method=cash | Date=2025-12-10 | Discount=YES', '2025-12-10 12:15:35'),
(105, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: 12, Amount: 32000.00, Lease_file: DS/TR/LS/', '2025-12-10 12:16:34'),
(106, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 105422.22, Lease_file: DS/TR/LS/', '2025-12-10 13:39:26'),
(107, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 105422.22, Lease_file: DS/TR/LS/', '2025-12-10 13:39:29'),
(108, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 105422.22, Lease_file: DS/TR/LS/', '2025-12-10 13:49:57'),
(109, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: valuation_date: null > 2023-12-19', '2025-12-10 13:50:30'),
(110, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=128,000.00 | Rec No=as | Method=cash | Date=2023-12-10 | Discount=NO', '2025-12-10 14:39:48'),
(111, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: as, Amount: 128000.00, Lease_file: DS/TR/LS/', '2025-12-10 14:41:10'),
(112, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=200,000.00 | Rec No=ss | Method=cash | Date=2020-12-07 | Discount=NO', '2025-12-10 14:41:34'),
(113, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: ss, Amount: 200000.00, Lease_file: DS/TR/LS/', '2025-12-10 14:44:13'),
(114, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=185,600.00 | Rec No=sas | Method=cash | Date=2018-09-08 | Discount=NO', '2025-12-10 14:45:08'),
(115, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=431,680.00 | Rec No=as | Method=cash | Date=2025-12-10 | Discount=YES', '2025-12-10 14:54:40'),
(116, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: as, Amount: 431680.00, Lease_file: DS/TR/LS/', '2025-12-10 14:58:03'),
(117, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sas, Amount: 185600.00, Lease_file: DS/TR/LS/', '2025-12-10 14:59:49'),
(118, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=185,600.00 | Rec No=s | Method=cash | Date=2018-12-09 | Discount=NO', '2025-12-10 15:00:24'),
(119, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: valuation_date: null > 2014-12-19 | start_date: 2012-09-20 > 2009-09-20 | end_date: 2042-09-20 > 2039-09-20 | premium: 48000.00 > null', '2025-12-10 15:15:27'),
(120, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 185600.00, Lease_file: DS/TR/LS/', '2025-12-10 15:15:51'),
(121, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: valuation_date: null > 2011-12-19 | premium: 48000.00 > null', '2025-12-10 15:16:14'),
(122, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=310,400.00 | Rec No=h | Method=cash | Date=2018-12-08 | Discount=NO', '2025-12-10 15:17:25'),
(123, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: h, Amount: 310400.00, Lease_file: DS/TR/LS/', '2025-12-10 15:56:48'),
(124, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=224,800.00 | Rec No=a | Method=cash | Date=2016-08-10 | Discount=NO', '2025-12-10 15:57:25'),
(125, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: a, Amount: 224800.00, Lease_file: DS/TR/LS/', '2025-12-10 16:11:46'),
(126, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=224,800.00 | Rec No=s | Method=cash | Date=2016-09-18 | Discount=NO', '2025-12-10 17:02:35'),
(127, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 224800.00, Lease_file: DS/TR/LS/', '2025-12-10 17:18:23'),
(128, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=224,800.00 | Rec No=s | Method=cash | Date=2016-06-18 | Discount=YES', '2025-12-10 17:19:35'),
(129, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 224800.00, Lease_file: DS/TR/LS/', '2025-12-10 17:25:04'),
(130, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=636, Amount=12800.00, Old Penalty=12800.00, New Penalty=0.00', '2025-12-10 17:25:23'),
(131, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=637, Amount=15200.00, Old Penalty=15200.00, New Penalty=0.00', '2025-12-10 17:25:35'),
(132, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=4, Lease ID=4, Schedule ID=637, Amount=15200.00', '2025-12-10 17:25:54'),
(133, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=3, Lease ID=4, Schedule ID=636, Amount=12800.00', '2025-12-10 17:25:58'),
(134, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=64,000.00 | Rec No=ss | Method=cash | Date=2009-09-30 | Discount=YES', '2025-12-10 17:39:33'),
(135, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: ss, Amount: 64000.00, Lease_file: DS/TR/LS/', '2025-12-10 17:58:41'),
(136, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=762,000.00 | Rec No=s | Method=cash | Date=2025-12-10 | Discount=NO', '2025-12-10 18:05:47'),
(137, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 762000.00, Lease_file: DS/TR/LS/', '2025-12-10 18:16:29'),
(138, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=50,000.00 | Rec No=sad | Method=cash | Date=2025-12-10 | Discount=NO', '2025-12-10 18:46:20'),
(139, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: sad, Amount: 50000.00, Lease_file: DS/TR/LS/', '2025-12-10 19:24:03'),
(140, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=231,200.00 | Rec No=fdfg | Method=cash | Date=2017-12-08 | Discount=NO', '2025-12-10 19:25:43'),
(141, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: valuation_date: null > 2010-12-19 | premium: 48000.00 > null', '2025-12-10 19:35:16'),
(142, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: fdfg, Amount: 231200.00, Lease_file: DS/TR/LS/', '2025-12-10 19:36:24'),
(143, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: premium: 48000.00 > null', '2025-12-10 19:49:08'),
(144, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=5,000.00 | Rec No=s | Method=cash | Date=2025-12-10 | Discount=NO', '2025-12-10 20:14:08'),
(145, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 5000.00, Lease_file: DS/TR/LS/', '2025-12-10 20:15:21'),
(146, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 5000.00, Lease_file: DS/TR/LS/', '2025-12-10 20:15:28'),
(147, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 5000.00, Lease_file: DS/TR/LS/', '2025-12-10 20:15:40'),
(148, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: valuation_date: null > 2010-12-19 | premium: 48000.00 > null', '2025-12-10 20:24:04'),
(149, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=Pending | Changes: premium: 48000.00 > null', '2025-12-10 20:24:35'),
(150, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: premium: 48000.00 > null | lease_number: Pending > test penalty', '2025-12-10 20:25:27'),
(151, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2025-12-19 | premium: 48000.00 > null', '2025-12-10 20:25:41'),
(152, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2012-12-19 | premium: 48000.00 > null', '2025-12-10 20:53:30'),
(153, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=863,520.00 | Rec No=s | Method=cash | Date=2025-12-10 | Discount=NO', '2025-12-10 21:29:18'),
(154, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2012-12-19 | start_date: 2009-09-20 > 2009-09-19 | end_date: 2039-09-20 > 2039-09-19 | premium: 48000.00 > null', '2025-12-10 21:31:36'),
(155, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=96,000.00 | Rec No=dasd | Method=cash | Date=2012-08-10 | Discount=NO', '2025-12-10 21:32:58'),
(156, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2014-12-19 | premium: 48000.00 > null', '2025-12-10 22:23:43'),
(157, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2017-12-19 | premium: 48000.00 > null', '2025-12-10 22:26:03'),
(158, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2014-12-19 | premium: 48000.00 > null', '2025-12-10 22:32:46'),
(159, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2009-12-19 | premium: 48000.00 > null', '2025-12-10 22:33:09'),
(160, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: dasd, Amount: 96000.00, Lease_file: DS/TR/LS/', '2025-12-10 22:36:15'),
(161, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2023-12-19 | start_date: 2009-09-19 > 2021-09-19 | end_date: 2039-09-19 > 2051-09-19 | premium: 48000.00 > null', '2025-12-10 22:37:15'),
(162, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=32,000.00 | Rec No=discount  | Method=cash | Date=2022-09-20 | Discount=YES', '2025-12-10 22:39:50'),
(163, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2020-12-19 | start_date: 2021-09-19 > 2020-09-19 | end_date: 2051-09-19 > 2050-09-19', '2025-12-10 22:48:11'),
(164, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=82,080.00 | Rec No=s | Method=cash | Date=2025-12-10 | Discount=NO', '2025-12-10 22:49:55'),
(165, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 82080.00, Lease_file: DS/TR/LS/', '2025-12-10 22:50:13'),
(166, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=82,080.00 | Rec No=s | Method=cash | Date=2025-10-10 | Discount=YES', '2025-12-10 22:50:50'),
(167, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-11 05:23:09'),
(168, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 82080.00, Lease_file: DS/TR/LS/', '2025-12-11 05:43:20'),
(169, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: discount , Amount: 32000.00, Lease_file: DS/TR/LS/', '2025-12-11 05:43:23'),
(170, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=902, Amount=1600.00, Old Penalty=1600.00, New Penalty=0.00', '2025-12-11 05:43:29'),
(171, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=5, Lease ID=4, Schedule ID=902, Amount=1600.00', '2025-12-11 05:43:42'),
(172, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=902, Amount=1600.00, Old Penalty=1600.00, New Penalty=0.00', '2025-12-11 05:43:51'),
(173, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=123,200.00 | Rec No=s | Method=cash | Date=2025-12-11 | Discount=NO', '2025-12-11 06:17:45'),
(174, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=6, Lease ID=4, Schedule ID=902, Amount=1600.00', '2025-12-11 06:18:33'),
(175, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: s, Amount: 123200.00, Lease_file: DS/TR/LS/', '2025-12-11 06:19:36'),
(176, 35, 1, 2, 40, 'LTL New Payment', 'Lease ID=4 | Amount=123,200.00 | Rec No=asas | Method=cash | Date=2025-12-11 | Discount=NO', '2025-12-11 06:22:53'),
(177, 35, 1, 2, 40, 'LTL Cancel Payment', 'Cancelled payment: asas, Amount: 123200.00, Lease_file: DS/TR/LS/', '2025-12-11 06:23:05'),
(178, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=902, Amount=600.00, Old Penalty=1600.00, New Penalty=1000.00', '2025-12-11 06:23:18'),
(179, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=902, Amount=1000.00, Old Penalty=1000.00, New Penalty=0.00', '2025-12-11 06:23:27'),
(180, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=905, Amount=400.00, Old Penalty=6400.00, New Penalty=6000.00', '2025-12-11 06:37:48'),
(181, 35, 1, 2, 40, 'LTL Write Off Penalty', 'Written off penalty: Lease ID=4, Schedule ID=903, Amount=3200.00, Old Penalty=3200.00, New Penalty=0.00', '2025-12-11 06:40:54'),
(182, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=7, Lease ID=4, Schedule ID=902, Amount=600.00', '2025-12-11 06:42:51'),
(183, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=8, Lease ID=4, Schedule ID=902, Amount=1000.00', '2025-12-11 06:42:55'),
(184, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=9, Lease ID=4, Schedule ID=905, Amount=400.00', '2025-12-11 06:42:58'),
(185, 35, 1, 2, 40, 'LTL Cancel Write Off', 'Cancelled write-off: ID=10, Lease ID=4, Schedule ID=903, Amount=3200.00', '2025-12-11 06:43:01'),
(186, 35, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=4 | Lease No=test penalty | Changes: valuation_date: null > 2011-12-19 | start_date: 2020-09-19 > 2007-09-19 | end_date: 2050-09-19 > 2037-09-19', '2025-12-11 06:43:30'),
(187, 35, 1, 2, 40, 'LTL Edit Premium', 'Premium amount changed: Lease ID=4, Schedule ID=931, Old Amount=48000.00, New Amount=8000.00', '2025-12-11 06:43:40'),
(188, 35, 1, 2, 40, 'LTL Cancel Premium Change', 'Cancelled premium change: ID=1, Lease ID=4, Schedule ID=931, Old Amount=48000.00, New Amount=8000.00', '2025-12-11 06:44:44'),
(189, 35, 1, 2, 40, 'LTL Edit Premium', 'Premium amount changed: Lease ID=4, Schedule ID=931, Old Amount=48000.00, New Amount=8000.00', '2025-12-11 06:44:57'),
(190, 35, 1, 2, 40, 'LTL Edit Land', 'Land ID=4 | LandBoundary: [{\"lat\":\"\",\"lng\":\"\"},{\"lat\":\"\",\"lng\":\"\"},{\"lat\":\"\",\"lng\":\"\"},{\"lat\":\"\",\"lng\":\"\"}] > [{\"lat\":8.606742,\"lng\":81.206251},{\"lat\":8.606858,\"lng\":81.206412},{\"lat\":8.606742,\"lng\":81.20652},{\"lat\":8.606604,\"lng\":81.206412}]', '2025-12-11 08:14:20'),
(191, 35, 1, 2, 40, 'LTL Edit Premium', 'Premium amount changed: Lease ID=4, Schedule ID=931, Old Amount=8000.00, New Amount=0.00', '2025-12-11 08:14:58'),
(192, 40, 1, 2, 40, 'LTL Add Land', 'Land ID=5 | Ben ID=40  | Address=test | Extent=45 hectares', '2025-12-11 08:31:22'),
(193, 40, 1, 2, 40, 'LTL Create Lease', 'Created lease: Pending File No: DS/TR/LS/', '2025-12-11 08:50:33'),
(194, 40, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=5 | Lease No=Pending | Changes: annual_rent_percentage: 2.00 > 4.00 | premium: 30000.00 > null', '2025-12-11 12:06:54'),
(195, 40, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=5 | Lease No=Pending | Changes: annual_rent_percentage: 2.00 > 4.00 | premium: 30000.00 > null', '2025-12-11 14:53:25'),
(196, 29, 1, 2, 40, 'LTL Add Land', 'Land ID=6 | Ben ID=29  | Address=12 | Extent= hectares', '2025-12-11 17:33:29'),
(197, 29, 1, 2, 40, 'LTL Create Lease', 'Created lease: 45678/45 File No: DS/TR/LS/1234', '2025-12-11 17:34:23'),
(198, 30, 1, 2, 40, 'LTL Add Land', 'Land ID=7 | Ben ID=30  | Address=23 | Extent= hectares', '2025-12-11 17:44:10'),
(199, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-12 09:57:23'),
(200, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-12 17:39:21'),
(201, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 12:02:21'),
(202, 29, 1, 2, 40, 'LTL Add Reminders', 'Added reminder: lease_id=6 | type=Annexure 09 | sent_date=2025-12-13 ', '2025-12-13 13:31:56'),
(203, 27, 1, 2, 40, 'LTL Lease Updated', 'Lease ID=1 | Lease No=Pending | Changes: valuation_date: null > 2021-01-01 | revision_period: 5 > null | revision_percentage: 20.00 > null', '2025-12-13 18:45:19'),
(204, 27, 1, 2, 40, 'LTL Add Reminders', 'Added reminder: lease_id=1 | type=Annexure 09 | sent_date=2025-12-13 ', '2025-12-13 19:35:05'),
(205, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:24:35'),
(206, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:26:28'),
(207, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:27:19'),
(208, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:33:34'),
(209, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:37:12'),
(210, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:39:59'),
(211, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:40:25'),
(212, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:41:49'),
(213, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:43:27'),
(214, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:43:53'),
(215, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 21:45:19'),
(216, NULL, 1, 1, 2, 'New User Created', 'User Kiritharan created by admin', '2025-12-13 22:14:37'),
(217, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:17:10'),
(218, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:19:11'),
(219, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:19:20'),
(220, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:19:51'),
(221, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:20:04'),
(222, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:21:25'),
(223, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:21:30'),
(224, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:22:12'),
(225, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:23:36'),
(226, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:23:59'),
(227, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:25:38'),
(228, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:25:42'),
(229, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:26:05'),
(230, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-13 22:26:52'),
(231, NULL, 109, 0, 0, 'Login', 'Login from ::1', '2025-12-14 06:24:56'),
(232, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-23 20:30:01'),
(233, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-23 20:30:23'),
(234, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-24 08:08:33'),
(235, NULL, 1, 0, 2, 'Created', 'Code: S1', '2025-12-24 09:20:59'),
(236, NULL, 1, 0, 2, 'Updated', 'Code: S1', '2025-12-24 09:21:22'),
(237, NULL, 1, 0, 2, 'Updated', 'Code: S1', '2025-12-24 09:34:46'),
(238, NULL, 1, 0, 2, 'Updated', 'Code: S1', '2025-12-24 09:47:00'),
(239, NULL, 1, 0, 2, 'Updated', 'Code: S1', '2025-12-24 09:47:37'),
(240, NULL, 1, 0, 2, 'Created', 'Code: S2', '2025-12-24 09:54:47'),
(241, NULL, 1, 0, 2, 'Created', 'Code: S3', '2025-12-24 09:56:41'),
(242, NULL, 1, 0, 2, 'Created', 'Code: REX-1 | Name: REX-1', '2025-12-24 11:03:06'),
(243, NULL, 1, 0, 2, 'Created', 'Code: REX-2 | Name: REX-2', '2025-12-24 11:03:29'),
(244, NULL, 1, 0, 2, 'Created', 'Code: REX-3 | Name: REX-3', '2025-12-24 11:03:45'),
(245, NULL, 1, 0, 2, 'Created', 'Code: REX-4 | Name: REX-4', '2025-12-24 11:04:08'),
(246, NULL, 1, 0, 2, 'Created', 'Code: REX-5 | Name: REX-5', '2025-12-24 11:04:22'),
(247, NULL, 1, 0, 2, 'Created', 'Code: REX-6 | Name: REX-6', '2025-12-24 11:04:35'),
(248, NULL, 1, 0, 2, 'Created', 'Code: REX-7 | Name: REX-7', '2025-12-24 11:05:03'),
(249, NULL, 1, 0, 2, 'Created', 'Code: CEX-1 | Name: CEX-1', '2025-12-24 11:05:19'),
(250, NULL, 1, 0, 2, 'Created', 'Code: CEX-2 | Name: CEX-2', '2025-12-24 11:05:31'),
(251, NULL, 1, 0, 2, 'Created', 'Code: CEX-3 | Name: CEX-3', '2025-12-24 11:05:44'),
(252, NULL, 1, 0, 2, 'Created', 'Code: CEX-4 | Name: CEX-4', '2025-12-24 11:05:57'),
(253, NULL, 1, 0, 2, 'Created', 'Code: CEX-5 | Name: CEX-5', '2025-12-24 11:06:13'),
(254, NULL, 1, 0, 2, 'Updated', 'Code: REX-1 | Name: Expenditure on projects implemented for the school', '2025-12-24 11:06:42'),
(255, NULL, 1, 0, 2, 'Updated', 'Code: REX-2 | Name: Office services / Equipment costs / Course overseas costs / Website costs', '2025-12-24 11:06:58'),
(256, NULL, 1, 0, 2, 'Updated', 'Code: REX-3 | Name: Educational/Administrative/Personnel Services and Welfare Services with School Services', '2025-12-24 11:07:15'),
(257, NULL, 1, 0, 2, 'Updated', 'Code: REX-4 | Name: Staff salary', '2025-12-24 11:10:50'),
(258, NULL, 1, 0, 2, 'Updated', 'Code: REX-5 | Name: Capital goods and equipment maintenance, repair', '2025-12-24 11:11:09'),
(259, NULL, 1, 0, 2, 'Updated', 'Code: REX-6 | Name: School building maintenance and repair', '2025-12-24 11:11:38'),
(260, NULL, 1, 0, 2, 'Updated', 'Code: REX-7 | Name: Cleaning and sanitation costs', '2025-12-24 11:11:55'),
(261, NULL, 1, 0, 2, 'Updated', 'Code: CEX-1 | Name: Basic facilities –  supply', '2025-12-24 11:12:38'),
(262, NULL, 1, 0, 2, 'Updated', 'Code: CEX-2 | Name: Capital expenditure on services implemented for the school', '2025-12-24 11:13:02'),
(263, NULL, 1, 0, 2, 'Updated', 'Code: CEX-3 | Name: School Welfare Fund Allowance', '2025-12-24 11:13:19'),
(264, NULL, 1, 0, 2, 'Updated', 'Code: CEX-4 | Name: School building new construction development and other capital expenditure', '2025-12-24 11:18:06'),
(265, NULL, 1, 0, 2, 'Updated', 'Code: CEX-5 | Name: Capital Goods and Equipment Purchase (Furniture and Emergency Equipment)', '2025-12-24 11:18:31'),
(266, NULL, 1, 0, 2, 'Created', 'Code: S4', '2025-12-24 11:34:32'),
(267, NULL, 1, 0, 2, 'Created', 'Code: S5', '2025-12-24 11:35:02'),
(268, NULL, 1, 0, 2, 'Created', 'Code: S6', '2025-12-24 11:35:22'),
(269, NULL, 1, 0, 2, 'Created', 'Code: S7', '2025-12-24 11:52:12'),
(270, NULL, 1, 0, 2, 'Created', 'Code: S8', '2025-12-24 11:55:44'),
(271, NULL, 1, 0, 2, 'Created', 'Code: S9', '2025-12-24 11:56:44'),
(272, NULL, 1, 0, 2, 'Created', 'Code: S10', '2025-12-24 11:57:30'),
(273, NULL, 1, 0, 2, 'Created', 'Code: S11', '2025-12-24 11:58:16'),
(274, NULL, 1, 0, 2, 'Updated', 'Code: S11', '2025-12-24 11:58:59'),
(275, NULL, 1, 0, 2, 'Updated', 'Code: S10', '2025-12-24 11:59:06'),
(276, NULL, 1, 0, 2, 'Updated', 'Code: S9', '2025-12-24 11:59:14'),
(277, NULL, 1, 0, 2, 'Updated', 'Code: S8', '2025-12-24 11:59:23'),
(278, NULL, 1, 0, 2, 'Updated', 'Code: S7', '2025-12-24 11:59:31'),
(279, NULL, 1, 0, 2, 'Created', 'Code: S4.1', '2025-12-24 14:32:38'),
(280, NULL, 1, 0, 2, 'Created', 'Code: S4.2', '2025-12-24 14:38:38'),
(281, NULL, 1, 0, 2, 'Updated', 'Code: S4.1', '2025-12-24 14:43:38'),
(282, NULL, 1, 0, 2, 'Created', 'Code: S4.3', '2025-12-24 14:44:14'),
(283, NULL, 1, 0, 2, 'Created', 'Code: S4.4', '2025-12-24 14:44:37'),
(284, NULL, 1, 0, 2, 'Created', 'Code: S4.5', '2025-12-24 14:45:23'),
(285, NULL, 1, 0, 2, 'Created', 'Code: S1.1', '2025-12-24 14:49:00'),
(286, NULL, 1, 0, 2, 'Updated', 'Code: S5', '2025-12-24 14:58:59'),
(287, NULL, 1, 0, 2, 'Updated', 'Code: S6', '2025-12-24 14:59:15'),
(288, NULL, 1, 0, 2, 'Updated', 'Code: S7', '2025-12-24 15:11:27'),
(289, NULL, 1, 0, 2, 'Updated', 'Code: S10', '2025-12-24 15:15:19'),
(290, NULL, 1, 0, 2, 'Updated', 'Code: S4', '2025-12-24 15:16:39'),
(291, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-25 07:42:10'),
(292, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-25 09:49:03'),
(293, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-25 15:11:22'),
(294, NULL, 1, 0, 0, 'Login', 'Login from ::1', '2025-12-26 08:27:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_account`
--
ALTER TABLE `bank_account`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_payment`
--
ALTER TABLE `bank_payment`
  ADD PRIMARY KEY (`pay_id`);

--
-- Indexes for table `bank_receipt`
--
ALTER TABLE `bank_receipt`
  ADD PRIMARY KEY (`rec_id`);

--
-- Indexes for table `bank_transfer`
--
ALTER TABLE `bank_transfer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_allocation`
--
ALTER TABLE `budget_allocation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_opening_balance`
--
ALTER TABLE `budget_opening_balance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_registration`
--
ALTER TABLE `client_registration`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `expenditure_code`
--
ALTER TABLE `expenditure_code`
  ADD PRIMARY KEY (`ex_id`);

--
-- Indexes for table `letter_head`
--
ALTER TABLE `letter_head`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `manage_activities`
--
ALTER TABLE `manage_activities`
  ADD PRIMARY KEY (`act_id`);

--
-- Indexes for table `manage_supplier`
--
ALTER TABLE `manage_supplier`
  ADD PRIMARY KEY (`sup_id`);

--
-- Indexes for table `manage_user_group`
--
ALTER TABLE `manage_user_group`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `revinue_category`
--
ALTER TABLE `revinue_category`
  ADD PRIMARY KEY (`rev_cat`);

--
-- Indexes for table `revinue_code`
--
ALTER TABLE `revinue_code`
  ADD PRIMARY KEY (`r_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_device`
--
ALTER TABLE `user_device`
  ADD PRIMARY KEY (`d_id`);

--
-- Indexes for table `user_license`
--
ALTER TABLE `user_license`
  ADD PRIMARY KEY (`usr_id`);

--
-- Indexes for table `user_location`
--
ALTER TABLE `user_location`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_log`
--
ALTER TABLE `user_log`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_account`
--
ALTER TABLE `bank_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bank_payment`
--
ALTER TABLE `bank_payment`
  MODIFY `pay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank_receipt`
--
ALTER TABLE `bank_receipt`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bank_transfer`
--
ALTER TABLE `bank_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `budget_allocation`
--
ALTER TABLE `budget_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `budget_opening_balance`
--
ALTER TABLE `budget_opening_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `client_registration`
--
ALTER TABLE `client_registration`
  MODIFY `c_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `expenditure_code`
--
ALTER TABLE `expenditure_code`
  MODIFY `ex_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `letter_head`
--
ALTER TABLE `letter_head`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `manage_activities`
--
ALTER TABLE `manage_activities`
  MODIFY `act_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `manage_supplier`
--
ALTER TABLE `manage_supplier`
  MODIFY `sup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `manage_user_group`
--
ALTER TABLE `manage_user_group`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `revinue_category`
--
ALTER TABLE `revinue_category`
  MODIFY `rev_cat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `revinue_code`
--
ALTER TABLE `revinue_code`
  MODIFY `r_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `user_device`
--
ALTER TABLE `user_device`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `user_license`
--
ALTER TABLE `user_license`
  MODIFY `usr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `user_location`
--
ALTER TABLE `user_location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=193;

--
-- AUTO_INCREMENT for table `user_log`
--
ALTER TABLE `user_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=295;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
