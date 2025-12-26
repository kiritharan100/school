-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 25, 2025 at 11:37 AM
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

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` datetime DEFAULT NULL,
  `try_for` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `manage_user_group`
--

CREATE TABLE `manage_user_group` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `user_location`
--

CREATE TABLE `user_location` (
  `id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_payment`
--
ALTER TABLE `bank_payment`
  MODIFY `pay_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_receipt`
--
ALTER TABLE `bank_receipt`
  MODIFY `rec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_transfer`
--
ALTER TABLE `bank_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_allocation`
--
ALTER TABLE `budget_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_opening_balance`
--
ALTER TABLE `budget_opening_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_registration`
--
ALTER TABLE `client_registration`
  MODIFY `c_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditure_code`
--
ALTER TABLE `expenditure_code`
  MODIFY `ex_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `letter_head`
--
ALTER TABLE `letter_head`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manage_activities`
--
ALTER TABLE `manage_activities`
  MODIFY `act_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manage_supplier`
--
ALTER TABLE `manage_supplier`
  MODIFY `sup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manage_user_group`
--
ALTER TABLE `manage_user_group`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revinue_category`
--
ALTER TABLE `revinue_category`
  MODIFY `rev_cat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `revinue_code`
--
ALTER TABLE `revinue_code`
  MODIFY `r_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_device`
--
ALTER TABLE `user_device`
  MODIFY `d_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_license`
--
ALTER TABLE `user_license`
  MODIFY `usr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_location`
--
ALTER TABLE `user_location`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_log`
--
ALTER TABLE `user_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
