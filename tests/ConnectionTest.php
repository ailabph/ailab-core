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
        $connection = AilabCore\Connection::getPrimaryConnection();
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
        $connection = AilabCore\Connection::getPrimaryConnection();
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
        $last_id = AilabCore\Connection::getPrimaryConnection()->lastInsertId();
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
        $last_id = AilabCore\Connection::getPrimaryConnection()->lastInsertId();
        AilabCore\Connection::rollback();

        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE id=:id",[":id"=>$last_id]);
        self::assertEquals(0,$result->rowCount());

        $result = Ailabph\AilabCore\Connection::executeQuery(
            "SELECT * FROM meta_options WHERE tag=:tag",[":tag"=>$tag]);
        self::assertEquals(0,$result->rowCount());
    }

    public function testBypassTransaction(){
        AilabCore\Connection::startTransaction();
        $tag_1 = "tag_".AilabCore\Random::getRandomStr();
        $query_1 = AilabCore\Connection::executeQuery(
            " INSERT INTO `meta_options` "
            ." (`type`,`tag`) VALUES "
            ." (:type, :tag ) ",
            [":type"=>"type_test",":tag"=>$tag_1]
        );
        $last_id_1 = AilabCore\Connection::getPrimaryConnection()->lastInsertId();

        $tag_2 = "tag_".AilabCore\Random::getRandomStr();
        $query_2 = AilabCore\Connection::executeQuery(
            query:" INSERT INTO `meta_options` "
            ." (`type`,`tag`) VALUES "
            ." (:type, :tag ) ",
            param: [":type"=>"type_test",":tag"=>$tag_2],
            use_secondary: true
        );
        $last_id_2 = AilabCore\Connection::getSecondaryConnection()->lastInsertId();
        AilabCore\Connection::rollback();

        $query_1 = Ailabph\AilabCore\Connection::executeQuery(
            query:"SELECT * FROM meta_options WHERE id=:id",param:[":id"=>$last_id_1]);
        self::assertEquals(0,$query_1->rowCount());

        $query_2 = Ailabph\AilabCore\Connection::executeQuery(
            query:"SELECT * FROM meta_options WHERE id=:id",
            param:[":id"=>$last_id_2],
            use_secondary: true);
        self::assertEquals(1,$query_2->rowCount());
        self::assertEquals($tag_2,$query_2->fetchObject()->tag);
    }
}