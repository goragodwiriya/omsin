-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 01, 2024 at 06:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(10) NOT NULL,
  `member_id` int(11) NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `topic` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `{prefix}_emailtemplate`
--

CREATE TABLE `{prefix}_emailtemplate` (
  `id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `email_id` int(11) NOT NULL,
  `language` varchar(2) NOT NULL,
  `from_email` text NOT NULL,
  `copy_to` text NOT NULL,
  `name` text NOT NULL,
  `subject` text NOT NULL,
  `detail` text NOT NULL,
  `last_update` int(11) UNSIGNED NOT NULL,
  `last_send` datetime NOT NULL,
  `email_count` int(11) NOT NULL,
  `email_limit` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `{prefix}_emailtemplate`
--

INSERT INTO `{prefix}_emailtemplate` (`id`, `module`, `email_id`, `language`, `from_email`, `copy_to`, `name`, `subject`, `detail`, `last_update`, `last_send`, `email_count`, `email_limit`) VALUES
(1, 'member', 2, 'th', '', '', 'ตอบรับการสมัครสมาชิกใหม่ (ไม่ต้องยืนยันสมาชิก)', 'ตอบรับการสมัครสมาชิก %WEBTITLE%', '<div style=\"padding: 10px; background-color: rgb(247, 247, 247);\">\n<table style=\"border-collapse: collapse;\">\n	<tbody>\n		<tr>\n			<th style=\"border-width: 1px; border-style: none solid; border-color: rgb(59, 89, 152); padding: 5px; text-align: left; color: rgb(255, 255, 255); font-family: tahoma; font-size: 9pt; background-color: rgb(59, 89, 152);\">ยินดีต้อนรับสมาชิกใหม่ %WEBTITLE%</th>\n		</tr>\n		<tr>\n			<td style=\"border-width: 1px; border-style: none solid solid; border-color: rgb(204, 204, 204) rgb(204, 204, 204) rgb(59, 89, 152); padding: 15px; line-height: 1.8em; font-family: tahoma; font-size: 9pt;\">ขอขอบคุณสำหรับการลงทะเบียนกับเรา บัญชีใหม่ของคุณได้รับการติดตั้งเรียบร้อยแล้วและคุณสามารถเข้าระบบได้โดยใช้รายละเอียดด้านล่างนี้<br />\n			<br />\n			ที่อยู่อีเมล์ : <strong>%EMAIL%</strong><br />\n			รหัสผ่าน&nbsp; : <strong>%PASSWORD%</strong><br />\n			<br />\n			คุณสามารถกลับไปเข้าระบบได้ที่ <a href=\"%WEBURL%\">%WEBURL%</a></td>\n		</tr>\n		<tr>\n			<td style=\"padding: 15px; color: rgb(153, 153, 153); font-family: tahoma; font-size: 8pt;\">ด้วยความขอบคุณ <a href=\"mailto:%ADMINEMAIL%\">เว็บมาสเตอร์</a></td>\n		</tr>\n	</tbody>\n</table>\n</div>\n', 1378257485, '0000-00-00 00:00:00', 0, 0),
(2, 'member', 3, 'th', '', '', 'ขอรหัสผ่านใหม่', 'รหัสผ่านของคุณใน %WEBTITLE%', '<div style=\"padding: 10px;  background-color: rgb(247, 247, 247);\">\r\n<table style=\" border-collapse: collapse;\">\r\n	<tbody>\r\n		<tr>\r\n			<th style=\"border-width: 1px; border-style: none solid; border-color: rgb(59, 89, 152); padding: 5px; text-align: left; color: rgb(255, 255, 255); font-family: tahoma; font-size: 9pt; background-color: rgb(59, 89, 152);\">รหัสผ่านของคุณใน %WEBTITLE%</th>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"border-width: 1px; border-style: none solid solid; border-color: rgb(204, 204, 204) rgb(204, 204, 204) rgb(59, 89, 152); padding: 15px; line-height: 1.8em; font-family: tahoma; font-size: 9pt;\">รหัสผ่านใหม่ของคุณถูกส่งมาจากระบบอัตโนมัติ เมื่อ %TIME%<br />\r\n			ไม่ว่าคุณจะได้ทำการขอรหัสผ่านใหม่หรือไม่ก็ตาม โปรดใช้รหัสผ่านใหม่นี้กับบัญชีของคุณ<br />\r\n			(ถ้าคุณไม่ได้ดำเนินการนี้ด้วยตัวเอง อาจมีผู้พยายามเข้าไปเปลี่ยนแปลงข้อมูลส่วนตัวของคุณ)<br />\r\n			<br />\r\n			ที่อยู่อีเมล์ : <strong>%EMAIL%</strong><br />\r\n			รหัสผ่าน : <strong>%PASSWORD%</strong><br />\r\n			<br />\r\n			คุณสามารถกลับไปเข้าระบบและแก้ไขข้อมูลส่วนตัวของคุณใหม่ได้ที่ <a href=\"%WEBURL%\">%WEBURL%</a></td>\r\n		</tr>\r\n		<tr>\r\n			<td style=\"padding: 15px; color: rgb(153, 153, 153); font-family: tahoma; font-size: 8pt;\">ด้วยความขอบคุณ <a href=\"mailto:%ADMINEMAIL%\">เว็บมาสเตอร์</a></td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n</div>\r\n', 1377480357, '0000-00-00 00:00:00', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_ierecord`
--

CREATE TABLE `{prefix}_ierecord` (
  `account_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `status` enum('IN','OUT','TRANSFER','INIT') NOT NULL,
  `category_id` int(11) NOT NULL,
  `wallet` int(11) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `create_date` datetime NOT NULL,
  `income` decimal(10,2) NOT NULL,
  `expense` decimal(10,2) NOT NULL,
  `transfer_to` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `js` tinyint(1) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text NOT NULL,
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `create_date` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` tinyint(1) DEFAULT 0,
  `fax` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `type` tinyint(1) DEFAULT 0,
  `line_uid` varchar(33) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(10) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `{prefix}_emailtemplate`
--
ALTER TABLE `{prefix}_emailtemplate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_ierecord`
--
ALTER TABLE `{prefix}_ierecord`
  ADD PRIMARY KEY (`account_id`,`id`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `username` (`username`),
  ADD KEY `token` (`token`),
  ADD KEY `phone` (`phone`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `activatecode` (`activatecode`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for table `{prefix}_emailtemplate`
--
ALTER TABLE `{prefix}_emailtemplate`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
