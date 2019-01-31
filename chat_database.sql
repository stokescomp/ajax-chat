-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 14, 2012 at 02:51 PM
-- Server version: 5.5.22
-- PHP Version: 5.4.1

--
-- Database: `ajaxchat`
--

CREATE DATABASE IF NOT EXISTS `ajaxchat`;
USE `ajaxchat`;

-- --------------------------------------------------------

--
-- Table structure for table `chat`
--

CREATE TABLE IF NOT EXISTS `chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_user_id` int(10) unsigned NOT NULL,
  `session_id` int(10) unsigned NOT NULL,
  `chat_text` varchar(500) NOT NULL,
  `chat_blob_file` longblob,
  `chat_blob_name` varchar(100) DEFAULT NULL,
  `chat_date_sent` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `session_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_request_to` int(10) unsigned NOT NULL,
  `session_request_from` int(10) unsigned NOT NULL,
  `session_accepted` tinyint(1) NOT NULL DEFAULT '0',
  `session_stopped` tinyint(1) NOT NULL DEFAULT '0',
  `creation_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(30) CHARACTER SET utf8 NOT NULL,
  `user_first` varchar(30) CHARACTER SET utf8 NOT NULL,
  `user_last` varchar(30) CHARACTER SET utf8 NOT NULL,
  `salt` varchar(64) CHARACTER SET utf8 NOT NULL,
  `user_password` varchar(40) CHARACTER SET utf8 NOT NULL,
  `user_level` enum('user','admin') CHARACTER SET utf8 NOT NULL,
  `user_logged_in` tinyint(1) NOT NULL DEFAULT '0',
  `user_createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_lastlogin` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_last_activity` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_name`, `user_first`, `user_last`, `salt`, `user_password`, `user_level`, `user_logged_in`, `user_createdate`, `user_lastlogin`, `user_last_activity`) VALUES
(1, 'mike', 'Mike', 'Abbott', 'bVFEWiXRJgk2-op6CJ8CmRbMq6N-M2dBB8UWBxFL6BjaURpRfF2bIm6KK6Qpv4CT', 'a709e1791442a4f2ed461c16a7046cb52243da07', 'user', 0, '2009-12-15 22:25:30', '2012-06-14 20:16:33', '2012-06-14 20:27:20'),
(2, 'test', 'Test', 'Test', 'B3TcHsiWF7TUZ.rWcAcfeSDpPudqsRKV-MwHKH1MAgEr7u6a.IIhWr5ZPaSxUiR0', '9ae3b9cb4244bc17ac16597afcfa6f531f5afffc', 'user', 0, '2010-03-26 00:17:19', '2012-06-14 18:53:58', '2012-06-14 19:09:19');