<?php

use App\DBClassGenerator\DB;
use PHPUnit\Framework\TestCase;
use Ailabph\AilabCore;

class TableClassTest extends TestCase
{
    protected function setUp(): void
    {
        AilabCore\Connection::startTransaction();
    }

    protected function tearDown(): void
    {
        if(AilabCore\Connection::getPrimaryConnection()->inTransaction()){
            AilabCore\Connection::rollback();
        }
    }

    public function testInsert(){
        $meta = new DB\meta_options();
        self::assertTrue($meta->isNew());
        $meta->tag = "new_tag_123";
        $meta->value = "value_123";
        $meta->save();
        self::assertFalse($meta->isNew());
        $check = new DB\meta_options(["id"=>$meta->id]);
        self::assertFalse($check->isNew(),"is new");
        self::assertEquals($meta->tag,$check->tag,"tag");
    }

    public function testQuery(){
        $meta = new DB\meta_options();
        $meta->tag = "new_tag_123";
        $meta->value = "value_123";
        $meta->save();

        $check = new DB\meta_options(["tag"=>"new_tag_123"]);
        self::assertTrue($check->recordExists(),"is not new");
        self::assertEquals($meta->id,$check->id,"id match");
    }

    public function testUpdate(){
        $meta = new DB\meta_options();
        $meta->tag = "new_tag_123";
        $meta->value = "value_123";
        $meta->save();

        $meta->tag = "new_tag_456";
        $meta->save();

        $meta->refresh();
        self::assertEquals("new_tag_456",$meta->tag);

        $check = new DB\meta_options(["id"=>$meta->id]);
        self::assertEquals("new_tag_456",$check->tag);
    }

    public function testDelete(){
        $meta = new DB\meta_options();
        $meta->tag = "new_tag_123";
        $meta->value = "value_123";
        $meta->save();
        $original_id = $meta->id;
        self::assertGreaterThan(0,$original_id);
        $meta->delete();
        $check = new DB\meta_options(["id"=>$original_id]);
        self::assertTrue($check->isNew());
    }

    public function testBypassRollback(){
        $meta = new DB\meta_options();
        $meta->tag = "new_tag_123";
        $meta->value = "value_123";
        $meta->save();

        $meta2 = new DB\meta_options(secondary_connection: true);
        $meta2->tag = "tag_".time();
        $meta2->value = "value_here";
        $meta2->save();
        AilabCore\Connection::getPrimaryConnection()->rollBack();

        $check1 = new DB\meta_options(["id"=>$meta->id]);
        self::assertTrue($check1->isNew(),"check 1");

        $check2 = new DB\meta_options(["id"=>$meta2->id]);
        self::assertTrue($check2->recordExists(),"check 2 record still exists");
    }

    public function testList(){
        $meta1 = new DB\meta_options();
        $meta1->tag = "new_tag_123";
        $meta1->value = "value_123";
        $meta1->save();

        $meta2 = new DB\meta_options();
        $meta2->tag = "new_tag_456";
        $meta2->value = "value_456";
        $meta2->save();

        $meta3 = new DB\meta_options();
        $meta3->tag = "new_tag_789";
        $meta3->value = "value_789";
        $meta3->save();

        $list = new DB\meta_optionsList(where:" WHERE 1 ",param:[],order:" ORDER BY id DESC LIMIT 3 ");
        self::assertCount(3,$list);

        $item = $list->fetch();
        self::assertEquals("new_tag_789",$item->tag,"tag");
        self::assertEquals($meta3->id,$item->id,"tag");

        $list->rewind();

        $collection = [];
        foreach ($list as $meta){
            if(!in_array($meta->id,$collection)){
                $collection[] = $meta->id;
            }
        }
        self::assertCount(3,$collection);

        $list->rewind();
        $collection = [];
        while($meta = $list->fetch()){
            if(!in_array($meta->id,$collection)){
                $collection[] = $meta->id;
            }
        }
        self::assertCount(3,$collection);
    }

    public function testJoinViaList(){
        $user1 = new DB\user();
        $user1->firstname = "john";
        $user1->lastname = "doe";
        $user1->username = "johndoe";
        $user1->password = "abc123";
        $user1->usergroup = "admin";
        $user1->save();

        $meta = new DB\meta_options();
        $meta->type = "user_type";
        $meta->tag = "user_".$user1->id;
        $meta->value = $user1->id;
        $meta->save();

        $metas = new DB\meta_optionsList(
            where:" WHERE meta_options.id=:id ",param:[":id"=>$meta->id]
            ,select:" meta_options.*, user.firstname,user.lastname "
            ,join: " JOIN user ON user.id = meta_options.value "
        );

        self::assertCount(1,$metas);
        /** @var DB\meta_optionsX $meta */
        $meta = $metas->fetch();
        self::assertEquals("john doe",$meta->fullName);
    }
}