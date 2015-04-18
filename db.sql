CREATE TABLE IF NOT EXISTS `site_logs` (
  `auto_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_identifer` varchar(5000) NOT NULL,
  `log_date` datetime NOT NULL,
  `log_details` longtext NOT NULL,
  PRIMARY KEY (`auto_id`)
)