# Video Chat Application

A Video-Chat widget that can be setup modified & adapted to work on your website or app.
The Video Component enables peer assisted networking using the Real Time Media Flow Protocol (RTMFP) within the Adobe Flash® Platform.

## Requirements

1. PHP
2. MYSQL
2. FLASH/FLEX EDITOR OF YOUR CHOUCE(I strongly suggest Flash Builder)
3. WEB SERVER

## Installation

* go to http://labs.adobe.com/technologies/cirrus/  and sign up for a developer key
* Specify your developer key in DeveloperKey constant in VideoChatByEddyF.mxml
* Specify the URL of your web service in WebServiceUrl constant in VideoChatByEddyF.mxml
* Create a database and the registration table 

CREATE TABLE IF NOT EXISTS `registrations` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `appid` BIGINT(20) NOT NULL DEFAULT ’0′,
  `username` VARCHAR(60) NOT NULL DEFAULT ”,
  `identity` VARCHAR(120) NOT NULL DEFAULT ”,
  `updated` DATETIME NOT NULL DEFAULT ’0000-00-00 00:00:00′,
  PRIMARY KEY  (`id`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

* edit the database details under bin-debug/reg.php
* Run the application from two different browsers. 

Any problems feel free to contact me.

https://www.facebook.com/TheEddyFerreira
https://twitter.com/MyNameIsEddyF

Enjoy!