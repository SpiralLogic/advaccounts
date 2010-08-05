-- phpMyAdmin SQL Dump
-- version 2.7.0-pl2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Created: 01 oktober 2007 kl 10:53
-- Serverversion: 4.1.11
-- PHP-version: 4.4.1
-- 

-- --------------------------------------------------------

-- 
-- Strukture for table `xx_reports`
-- 

DROP TABLE IF EXISTS `xx_reports`;
CREATE TABLE IF NOT EXISTS `xx_reports` (
  `id` varchar(10) NOT NULL default '',
  `typ` varchar(10) NOT NULL default '',
  `attrib` text NOT NULL
) TYPE=MyISAM;

--
-- Data in table `xx_reports`
--

INSERT INTO `xx_reports` VALUES('3', 'item', 'String|PF|Helvetica|8|10l|300|10|printed at||||||||');
INSERT INTO `xx_reports` VALUES('3', 'item', 'DB|DE|Helvetica|12|6l|100|0|typ||||');
INSERT INTO `xx_reports` VALUES('3', 'item', 'Term|PF|Helvetica|8|20l|460|10|PageNo||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Line|PH|1|1|1|1|1');
INSERT INTO `xx_reports` VALUES('3', 'item', 'String|PH|Helvetica|14|40l|200|0|Stored Report|||1|40');
INSERT INTO `xx_reports` VALUES('3', 'item', 'DB|DE|Helvetica|12|40l|150|0|attrib||||||||');
INSERT INTO `xx_reports` VALUES('3', 'item', 'String|GH|Helvetica-Bold|14|10l|200|0|New Group||||||||');
INSERT INTO `xx_reports` VALUES('3', 'item', 'DB|GH|Helvetica-Bold|12|6l|285|0|id||||||||');
INSERT INTO `xx_reports` VALUES('3', 'info', 'Reports|2007-10-01|Bauer|List of all stored Reports|portrait|a4|classgrid');
INSERT INTO `xx_reports` VALUES('3', 'group', 'id|newpage');
INSERT INTO `xx_reports` VALUES('F2', 'funct', 'atime|2007-09-21|Bauer|Actual Time|function atime() {return date("h:i a");}');
INSERT INTO `xx_reports` VALUES('F1', 'funct', 'RepDate|2007-09-26|Joe|Date|function RepDate() {return today()." ".now();}');
INSERT INTO `xx_reports` VALUES('3', 'item', 'Term|PF|Helvetica|8|20l|335|10|RepDate||||||||');
INSERT INTO `xx_reports` VALUES('F4', 'funct', 'oldgroup|2007-09-26|bauer|Value of old group|function oldgroup($it){return $it->group_old;}');
INSERT INTO `xx_reports` VALUES('F5', 'funct', 'Newgroup|2007-09-26|Hunt|New group value|function newgroup($it){return $it->group_new;}');
INSERT INTO `xx_reports` VALUES('B1', 'block', 'block1|2007-09-27|Bauer|Block 1');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Line|GF|1|1|1|400|1');
INSERT INTO `xx_reports` VALUES('2', 'item', 'DB|DE|Helvetica-Bold|12||100|600|attrib||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'DB|DE|Helvetica|9|6l|50|0|account_code||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|GH|Helvetica|9|50l|50|0|Newgroup||||||||');
INSERT INTO `xx_reports` VALUES('F6', 'funct', 'rec_count|2007-09-26|bauer|total number of records|function rec_count($it) {return $it->count;}');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|RF|Helvetica|9|20l|50|0|Gran Total||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|GF|Helvetica|9|20l|50|0|Sum:||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'DB|DE|Helvetica|9|30l|300|0|name||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'DB|DE|Helvetica|9|20l|400|0|class_name||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'DB|DE|Helvetica|9|50l|100|0|account_name||||||||');
INSERT INTO `xx_reports` VALUES('F7', 'funct', 'subcount|2007-09-26|bauer|total number of records of a group|function subcount($it) {return $it->subcount;}');
INSERT INTO `xx_reports` VALUES('6', 'info', 'Accounts|2009-02-08|Hunt|Accounts List|portrait|a4|class');
INSERT INTO `xx_reports` VALUES('6', 'select', 'select * from 0_chart_master,0_chart_types,0_chart_class where account_type=id and class_id=cid order by account_code');
INSERT INTO `xx_reports` VALUES('2', 'info', 'single|2007-09-26|Bauer|Single page per Record|portrait|a4|single');
INSERT INTO `xx_reports` VALUES('2', 'select', 'select * from xx_reports');
INSERT INTO `xx_reports` VALUES('2', 'group', '|nopage');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Line|GH|1|1|1|1|1');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Line|RH|1|1|1|1|1');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Line|RF|1|1|1|1|1');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica-Bold|16|50l|50|0|RepTitle||||||||');
INSERT INTO `xx_reports` VALUES('F8', 'funct', 'RepTitle|2007-09-26|Joe|Report Title|function RepTitle($it) \r\n{ return $it->long_name; }');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|RH|Helvetica|8|30l|50|-12|Print Out Date:||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|8|30l|120|-12|RepDate||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|RH|Helvetica|8|30l|50|-24|Fiscal Year:||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|RH|Helvetica|8|30l|50|-36|Select:||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|GF|Helvetica|9|4r|200|0|subcount||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RF|Helvetica|9|4r|200|0|rec_count||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|PH|Helvetica-Bold|9|20l|50|0|Account||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|PH|Helvetica-Bold|9|50l|100|0|Account Name||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|PH|Helvetica-Bold|9|20l|300|0|Type||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'String|PH|Helvetica-Bold|9|20l|400|0|Class||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|9|50l|400|0|Company||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|9|50l|400|-12|Username||||||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|9|50l|400|-36|PageNo||||||||');
INSERT INTO `xx_reports` VALUES('F9', 'funct', 'PageNo|2007-09-26|Joe|Page Number|function PageNo($it){return "Page   ".$it->pdf->numPages;}');
INSERT INTO `xx_reports` VALUES('B1', 'item', 'String|PH|Helvetica|7|20l|100|0|Stringitem||||||||');
INSERT INTO `xx_reports` VALUES('3', 'select', 'select * from xx_reports order by id');
INSERT INTO `xx_reports` VALUES('3', 'item', 'DB|PH|Helvetica|14|6l|360|0|id|||1|6||||');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|8|50l|400|-24|Host||||||||');
INSERT INTO `xx_reports` VALUES('F13', 'funct', 'Host|2007-09-26|Hunt|Host name|function Host(){return $_SERVER[''SERVER_NAME''];}');
INSERT INTO `xx_reports` VALUES('6', 'group', 'name|nopage');
INSERT INTO `xx_reports` VALUES('6', 'item', 'Term|RH|Helvetica|8|50l|120|-24|FiscalYear||||||||');
INSERT INTO `xx_reports` VALUES('F12', 'funct', 'FiscalYear|2007-09-26|Hunt|Get current Fiscal Year|function FiscalYear(){$y=get_current_fiscalyear();return sql2date($y[''begin'']) . " - " . sql2date($y[''end'']);}');
INSERT INTO `xx_reports` VALUES('F11', 'funct', 'Username|2007-09-26|Hunt|Get Username|function Username(){return $_SESSION["wa_current_user"]->name;}');
INSERT INTO `xx_reports` VALUES('F10', 'funct', 'Company|2007-09-26|Hunt|Company Name|function Company(){ return get_company_pref(''coy_name''); }');
