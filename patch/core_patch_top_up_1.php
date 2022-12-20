<?php

use App\DBClassGenerator\DB;
use Ailabph\AilabCore;

try{
    $statement = AilabCore\Connection::getConnection()->prepare(" CREATE TABLE `top_up_credits_header` ( `id` INT NOT NULL AUTO_INCREMENT , `total_credits_in` DECIMAL(16,2) NOT NULL DEFAULT '0' , `total_credits_out` DECIMAL(16,2) NOT NULL DEFAULT '0' , `total_credits_balance` DECIMAL(16,2) NOT NULL DEFAULT '0' , `status` VARCHAR(10) NOT NULL DEFAULT 'o' , PRIMARY KEY (`id`)) ENGINE = InnoDB; ");
    $statement->execute();
}catch (Exception $e){}

try{
    $statement = AilabCore\Connection::getConnection()->prepare(" CREATE TABLE `top_up_credits_detail` ( `id` INT NOT NULL AUTO_INCREMENT , `credits_in` DECIMAL(16,2) NULL , `request_detail_in` INT NULL , `credits_out` DECIMAL(16,2) NULL , `payment_id` INT NULL , `running_credit_balance` DECIMAL(16,2) NULL , `time_added` INT NULL , `status` VARCHAR(10) NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB; ");
    $statement->execute();
}catch (Exception $e){}

try{
    $statement = AilabCore\Connection::getConnection()->prepare(" CREATE TABLE `top_up_request_header` ( `id` INT NOT NULL AUTO_INCREMENT , `percentage_rate` DECIMAL(16,2) NULL , `total_top_up_approved` DECIMAL(16,2) NULL , `total_top_up_denied` DECIMAL(16,2) NULL , `total_credits_in` DECIMAL(16,2) NULL , `total_credits_used` DECIMAL(16,2) NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB; ");
    $statement->execute();
}catch (Exception $e){}


try{
    $statement = AilabCore\Connection::getConnection()->prepare(" CREATE TABLE `top_up_request_detail` ( `id` INT NOT NULL AUTO_INCREMENT , `top_up_amount` DECIMAL(16,2) NULL , `percentage_rate` DECIMAL(16,2) NULL , `amount_to_credit` DECIMAL(16,2) NULL , `top_up_by` INT NULL , `time_top_up` INT NULL , `top_up_notes` TEXT NULL , `top_up_screenshot` VARCHAR(255) NULL , `processed_by` INT NULL , `time_processed` INT NULL , `process_remarks` TEXT NULL , `process_status` VARCHAR(10) NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB; ");
    $statement->execute();
}catch (Exception $e){}

try{
    $statement = AilabCore\Connection::getConnection()->prepare(" ALTER TABLE `top_up_request_header` CHANGE `percentage_rate` `percentage_rate` DECIMAL(16,4) NULL DEFAULT NULL; ");
    $statement->execute();
}catch (Exception $e){}
