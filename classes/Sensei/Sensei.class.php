<?php
class Sensei_Group extends Doctrine_Record { }
//class Sensei_Company extends Sensei_Group { }

class Sensei_User extends Doctrine_Record { }
//class Sensei_Customer extends Sensei_User { }

class Sensei_Entity extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("loginname","string",32,"unique");
        $this->hasColumn("password","string",32);
    }
}
class Sensei_Variable extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name","string",50);
        $this->hasColumn("value","string",10000);
        $this->hasColumn("session_id","integer");
    }
}

class Sensei_Entity_Var extends Sensei_Variable { }
class Sensei_Session_Var extends Doctrine_Record { }
class Sensei_Session extends Doctrine_Record {
    public function setUp() {
        $this->ownsMany("Sensei_variable","Sensei_variable.session_id");
        $this->hasOne("Sensei_entity","Sensei_session.entity_id");
    }
    public function setTableDefinition() {
        $this->hasColumn("session_id","string",32);
        $this->hasColumn("logged_in","integer",1);
        $this->hasColumn("entity_id","integer");
        $this->hasColumn("user_agent","string",200);
        $this->hasColumn("updated","integer");
        $this->hasColumn("created","integer");
    }
}

class Sensei_Exception extends Exception { }


class Sensei extends Doctrine_Access {
    const ATTR_LIFESPAN = 0;
    /**
     * @var Sensei_Session $record
     */
    private $record;
    /**
     * @var Doctrine_Session $session
     */
    private $session;
    /**
     * @var Doctrine_Table $table
     */
    private $table;
    /**
     * @var array $attributes
     */
    private $attributes = array();
    /**
     * @var Doctrine_Collection $vars
     */
    private $vars;


    public function __construct() {
        if(headers_sent())
            throw new Sensei_Exception("Headers already sent. Couldn't initialize session.");


        $this->session = Doctrine_Manager::getInstance()->getCurrentSession();
        $this->table   = $this->session->getTable("Sensei_session");
        $this->init();


        $this->gc(1);

        if( ! isset($_SESSION))
            session_start();
    }
    /**
     * getRecord
     */
    public function getRecord() {
        return $this->record;
    }
    /**
     * init
     */
    private function init() {
        session_set_save_handler(
            array($this,"open"),
            array($this,"close"),
            array($this,"read"),
            array($this,"write"),
            array($this,"destroy"),
            array($this,"gc")
        );
    }
    /**
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function login($username,$password) {
        $coll = $this->session->query("FROM Sensei_Entity WHERE Sensei_Entity.loginname = ? && Sensei_Entity.password = ?",array($username,$password));
        if(count($coll) > 0) {
            $this->record->logged_in = 1;
            $this->record->entity_id = $coll[0]->getID();
            $this->record->save();
            return true;
        }
        return false;
    }
    /**
     * logout
     * @return boolean
     */
    public function logout() {
        if( $this->record->logged_in == true) {
            $this->record->logged_in = 0;
            $this->record->entity_id = 0;
            return true;
        } else {
            return false;
        }
    }
    public function get($name) {
        foreach($this->vars as $var) {
            if($var->name == $name) {
                return $var->value;
            }
        }
    }
    public function set($name,$value) {
        foreach($this->vars as $var) {
            if($var->name == $name) {
                $var->value = $value;
                return true;
            }
        }
        return false;
    }
    public function setAttribute($attr, $value) {
        switch($attr):
            case Sensei::ATTR_LIFESPAN:

            break;
            default:
                throw new Sensei_Exception("Unknown attribute");
        endswitch;
        
        $this->attributes[$attr] = $value;
    }
    private function open($save_path,$session_name) {
        return true;
    }
    public function close() {
        return true;
    }
    private function read($id) {
        $coll = $this->session->query("FROM Sensei_Session WHERE Sensei_Session.session_id = ?",array($id));
        $this->record = $coll[0];
        $this->record->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $this->record->updated    = time();
        $this->record->session_id = $id;

        if($this->record->getState() == Doctrine_Record::STATE_TDIRTY) {
            $this->record->created = time();
            $this->record->save();
        } 
        $this->vars = $this->record->Sensei_variable;
        return "";
    }
    public function write($id,$sess_data) {
        return true;
    }
    private function destroy($id) {
        $this->record->delete();
        return $r;
    }
    private function gc($maxlifetime) {
        return true;
    }
    public function flush() {
        $this->record->save();
    }
    public function __destruct() {
        $this->flush();
    }
}
?>
