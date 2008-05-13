<?php
class Doctrine_Relation_Access_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() {
        $o1 = new File_Owner();
        $o1->name = "owner1";
        $o2 = new File_Owner();
        $o2->name = "owner2";

        $f1 = new Data_File();
        $f1->filename = 'file1';
        $f2 = new Data_File();
        $f2->filename = 'file2';
        $f3 = new Data_File();
        $f3->filename = 'file3';

        $o1->Data_File->filename = 'file4';

        // multiple left join branches test
        $us = array();
        $us[1] = new MyUser();
        $us[1]->name = "user1";
        $this->connection->unitOfWork->saveAll();
        // OneThings
        $onethings_gs = array(
            array(6,1)
        );
        $count = 1;
        foreach($onethings_gs as $onething_g) {
            for($i=$count;$i<$count+$onething_g[0];$i++) {
                $d = new MyOneThing();
                $d->name = "onething".$i;
                if($onething_g[1]) {
                    $us[$onething_g[1]]->MyOneThing->add($d);
                }
            }
            $count += $onething_g[0];
        }
        // OtherThings
        for($i=0;$i<6;$i++) {
            $o = new MyOtherThing();
            $o->name = "otherthing".$i;
            $us[1]->MyOtherThing->add($o);
        }
        // UserOneThings
        /* Doctrine assigns the foreign keys automatically
        $one_id_gs = array(
                    array(array(2,3,6,5,1), 1)
                    );
        foreach($one_id_gs as $one_ids) {
            foreach($one_ids[0] as $oid) {
                $od = new MyUserOneThing();
                $od->one_thing_id = $oid;
                $od->user_id = $one_ids[1];
            }
        }
        // UserOtherThings
        $oth_id_gs = array(
                    array(array(5,4), 1)
                    );
        foreach($oth_id_gs as $oth_ids) {
            foreach($oth_ids[0] as $oid) {
                $uo = new MyUserOtherThing();
                $uo->other_thing_id = $oid;
                $uo->user_id = $oth_ids[1];
            }
        }
         */
        $this->connection->unitOfWork->saveAll();
        $this->connection->clear();
    }

    public function prepareTables() {
        $this->tables += array("MyUser",
            "MyOneThing",
            "MyUserOneThing",
            "MyOtherThing",
            "MyUserOtherThing"); 
        parent::prepareTables();
    }

    public function testOneToOneAggregateRelationFetching() {
        $coll = $this->connection->query("FROM File_Owner.Data_File WHERE File_Owner.name = 'owner1'");
        $this->assertTrue(count($coll) == 1);
        $this->assertTrue($coll[0] instanceof Doctrine_Entity);

        $this->assertEqual($coll[0]->id, 1);
    }
    public function testAccessOneToOneFromForeignSide() {

        $check = $this->connection->query("FROM File_Owner WHERE File_Owner.name = 'owner1'");
        $owner1 = $this->connection->query("FROM File_Owner.Data_File WHERE File_Owner.name = 'owner1'");
        $owner2 = $this->connection->query("FROM File_Owner.Data_File WHERE File_Owner.name = 'owner2'");
        $this->assertTrue(count($check) == 1);

        $this->assertTrue(count($owner2) == 1);

        $check = $check[0];
        $owner1 = $owner1[0];
        $owner2 = $owner2[0];
        $this->assertEqual($owner1->name, 'owner1');
        $this->assertEqual($owner1->id, 1);

        $check2 = $this->connection->query("FROM File_Owner WHERE File_Owner.id = ".$owner1->get('id'));
        $this->assertEqual(1, count($check2));
        $check2 = $check2[0];
        $this->assertEqual('owner1', $check2->get('name'));

        $this->assertTrue(isset($owner1->Data_File));
        $this->assertFalse(isset($owner2->Data_File));
        $this->assertEqual(1, $check->get('id'));
        $this->assertEqual(1, $owner1->get('id'));
        $this->assertEqual($owner1->get('id'), $check->get('id'));
        $this->assertEqual(2, $owner2->get('id'));

    }

    public function testAccessOneToOneFromLocalSide() {
        $check = $this->connection->query("FROM Data_File WHERE Data_File.filename = 'file4'");
        $file1 = $this->connection->query("FROM Data_File.File_Owner WHERE Data_File.filename = 'file4'");
        $file2 = $this->connection->query("FROM Data_File.File_Owner WHERE Data_File.filename = 'file1'");
        $this->assertTrue(count($check) == 1);
        $this->assertTrue(count($file1) == 1);
        $this->assertTrue(count($file2) == 1);

        $check = $check[0];
        $file1 = $file1[0];
        $file2 = $file2[0];

        $check2 = $this->connection->query("FROM Data_File WHERE Data_File.id = ".$file1->get('id'));
        $this->assertEqual(1, count($check2));
        $check2 = $check2[0];
        $this->assertEqual('file4', $check2->get('filename'));

        $this->assertTrue(isset($file1->File_Owner));
        $this->assertFalse(isset($file2->File_Owner));
        $this->assertEqual(4, $check->get('id'));
        $this->assertEqual(4, $file1->get('id'));
        $this->assertEqual($file1->get('id'), $check->get('id'));
        $this->assertEqual(1, $file2->get('id'));

    }

    public function testMultipleLeftJoinBranches() {
        $query = "FROM MyUserOtherThing";
        $other = $this->connection->query($query);
        $check1 = array();
        foreach($other as $oth) {
            if( ! isset($check1[$oth->other_thing_id])) {
                $check1[$oth->other_thing_id] = array();
            }
            $check1[$oth->other_thing_id][$oth->id] = $oth;
        }
        $query = "FROM MyUserOneThing";
        $ones = $this->connection->query($query);
        $check2 = array();
        foreach($ones as $one) {
            if( ! isset($check2[$one->one_thing_id])) {
                $check2[$one->one_thing_id] = array();
            }
            $check2[$one->one_thing_id][$one->id] = $one;
        }

        $query = "FROM MyUser,
            MyUser.MyOneThing,
            MyUser.MyOneThing.MyUserOneThing,
            MyUser.MyOtherThing,
            MyUser.MyOtherThing.MyUserOtherThing";
        $users = $this->connection->query($query);
        foreach($users as $u) {
            $this->assertEqual($u->MyOtherThing->count(), 6, "incorrect count of MyOtherThing");
            foreach($u->MyOtherThing as $o) {
                $in_check = array_key_exists($o->id, $check1);
                $wanted_user_thing_count = $in_check ? count($check1[$o->id]) : 0;
                $this->assertEqual($o->MyUserOtherThing->count(), $wanted_user_thing_count, "incorrect count of MyUserOtherThing on MyOtherThing");
                foreach($o->MyUserOtherThing as $uo) {
                    $this->assertEqual($uo->other_thing_id, $o->id, "incorrectly assigned MyOtherThing.id on MyUserOtherThing");
                    if($in_check) {
                        $wanted_user_thing_exists = array_key_exists($uo->id, $check1[$o->id]);
                        $this->assertTrue($wanted_user_thing_exists, "MyUserOtherThing incorrectly assigned to MyOtherThing.");
                        if($wanted_user_thing_exists) {
                            $this->assertEqual($uo->other_thing_id, $check1[$o->id][$uo->id]->user_id, "incorrect value of MyUserOtherThing.user_id");
                            $this->assertEqual($uo->other_thing_id, $check1[$o->id][$uo->id]->other_thing_id, "incorrect value of MyUserOtherThing.other_thing_id");
                        }
                    }
                }
            }
        }

        $query = "FROM MyUser,
            MyUser.MyOtherThing,
            MyUser.MyOtherThing.MyUserOtherThing,
            MyUser.MyOneThing,
            MyUser.MyOneThing.MyUserOneThing";
        $users = $this->connection->query($query);
        foreach($users as $u) {
            $this->assertEqual($u->MyOneThing->count(), 6, "incorrect count of MyOneThing");
            foreach($u->MyOneThing as $o) {
                $in_check = array_key_exists($o->id, $check2);
                $wanted_user_thing_count = $in_check ? count($check2[$o->id]) : 0;
                $this->assertEqual($o->MyUserOneThing->count(), $wanted_user_thing_count, "incorrect count of MyUserOneThing on MyOneThing");
                foreach($o->MyUserOneThing as $uo) {
                    $this->assertEqual($uo->one_thing_id, $o->id, "incorrectly assigned MyOneThing.id on MyUserOneThing");
                    if($in_check) {
                        $wanted_user_thing_exists = array_key_exists($uo->id, $check2[$o->id]);
                        $this->assertTrue($wanted_user_thing_exists, "MyUserOneThing incorrectly assigned to MyOneThing.");
                        if($wanted_user_thing_exists) {
                            $this->assertEqual($uo->one_thing_id, $check2[$o->id][$uo->id]->user_id, "incorrect value of MyUserOneThing.user_id");
                            $this->assertEqual($uo->one_thing_id, $check2[$o->id][$uo->id]->one_thing_id, "incorrect value of MyUserOneThing.one_thing_id");
                        }
                    }
                }
            }
        }
    }
}
