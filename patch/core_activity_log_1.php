<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

$statement = AilabCore\Connection::getConnection()
    ->prepare(" CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_action` varchar(255) DEFAULT NULL,
  `user_id` varchar(60) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `viewing` varchar(60) DEFAULT NULL,
  `fp` varchar(255) DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `device` varchar(255) DEFAULT NULL,
  `session` varchar(255) DEFAULT NULL,
  `email` varchar(60) DEFAULT NULL,
  `result` text CHARACTER SET utf8,
  `raw_post` text,
  `raw_get` text,
  `time_added` int(11) DEFAULT NULL,
  `date_time_added` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1; ");
$statement->execute();

$statement = AilabCore\Connection::getConnection()
    ->prepare(" ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`); ");
$statement->execute();

$statement = AilabCore\Connection::getConnection()
    ->prepare(" ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT; ");
$statement->execute();

if(AilabCore\Config::getEnv() == AilabCore\Config::ENV["local"]){
    AilabCore\GeneratorClassPhp::run();
}
