<?php

use Ailabph\AilabCore;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        AilabCore\Connection::reset();
    }

    public function testGetConnection(){
        $connection = AilabCore\Connection::getConnection();
        self::assertInstanceOf(PDO::class,$connection,"connection");
        self::assertFalse($connection->inTransaction());
    }

    public function testQuery(){
        $result = AilabCore\Connection::executeQuery(" SELECT (1 + 1) AS answer ",[]);
        self::assertInstanceOf(PDOStatement::class,$result);
        self::assertEquals(2,$result->fetchObject()->answer);
    }

    public function testStartTransaction(){
        AilabCore\Connection::startTransaction();
        $connection = AilabCore\Connection::getConnection();
        self::assertTrue($connection->inTransaction());
    }

    public function testCommit(){
        AilabCore\Connection::startTransaction();
        $tag = "tag_".AilabCore\Random::getRandomStr();
        $result = AilabCore\Connection::executeQuery(
            " INSERT INTO `meta_options` "
            ." (`type`,`tag`) VALUES "
            ." (:type, :tag ) ",
            [":type"=>"type_test",":tag"=>$tag]
        );
        $last_id = AilabCore\Connection::getConnection()->lastInsertId();
        AilabCore\Connection::commit();
        self::assertEquals(1,$result->rowCount());
        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE id=:id",[":id"=>$last_id]);
        self::assertEquals(1,$result->rowCount());
        self::assertEquals($tag,$result->fetchObject()->tag);

        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE tag=:tag",[":tag"=>$tag]);
        self::assertEquals(1,$result->rowCount());
    }

    public function testRollBack(){
        AilabCore\Connection::startTransaction();
        $tag = "tag_".AilabCore\Random::getRandomStr();
        $result = AilabCore\Connection::executeQuery(
            " INSERT INTO `meta_options` "
            ." (`type`,`tag`) VALUES "
            ." (:type, :tag ) ",
            [":type"=>"type_test",":tag"=>$tag]
        );
        $last_id = AilabCore\Connection::getConnection()->lastInsertId();
        AilabCore\Connection::rollback();

        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE id=:id",[":id"=>$last_id]);
        self::assertEquals(0,$result->rowCount());

        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE tag=:tag",[":tag"=>$tag]);
        self::assertEquals(0,$result->rowCount());
    }
}