CREATE TABLE IF NOT EXISTS `Account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oid` varchar(64) NOT NULL,
  `provider` varchar(16) NOT NULL,
  `name` varchar(32) NOT NULL,
  `image` varchar(128) NOT NULL,
  `statusId` int(11) NOT NULL DEFAULT '0',
  `roleId` int(11) NOT NULL DEFAULT '1',
  `joinAt` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Operation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  `path` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

INSERT INTO `Operation` (`id`, `name`, `path`) VALUES
(1, '全部管理权限', '*');



CREATE TABLE IF NOT EXISTS `Permission` (
  `roleId` int(11) NOT NULL,
  `operationId` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `Permission` (`roleId`, `operationId`) VALUES
(2, 1);



CREATE TABLE IF NOT EXISTS `Role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `Role` (`id`, `name`) VALUES
(1, '普通用户'),
(2, '系统管理员');



CREATE TABLE IF NOT EXISTS `Session` (
  `id` varchar(40) NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL DEFAULT '0',
  `userAgent` varchar(120) NOT NULL,
  `lastActivity` int(10) unsigned NOT NULL DEFAULT '0',
  `userData` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

INSERT INTO `Status` (`id`, `name`) VALUES
(0, '正常'),
(1, '未完善资料');



CREATE TABLE IF NOT EXISTS `Cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(64) NOT NULL,
  `schedule` varchar(16) NOT NULL,
  `once` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `Upload` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accountId` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `url` varchar(255) NOT NULL,
  `mime` varchar(32) DEFAULT NULL,
  `isImage` tinyint(4) DEFAULT '0',
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
