<?php
// Minimal Beneficiary class
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Beneficiary extends CommonObject
{
    public $table_element = 'foodbank_beneficiaries';
    public $element = 'beneficiary';
    public $table = 'llx_foodbank_beneficiaries';

    public $id;
    public $ref;
    public $firstname;
    public $lastname;
    public $phone;
    public $email;
    public $address;
    public $note;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create()
    {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "foodbank_beneficiaries (ref, firstname, lastname, phone, email, address, note, datec, entity)
                VALUES ('".$this->db->escape($this->ref)."', '".$this->db->escape($this->firstname)."', '".$this->db->escape($this->lastname)."', '".$this->db->escape($this->phone)."', '".$this->db->escape($this->email)."', '".$this->db->escape($this->address)."', '".$this->db->escape($this->note)."', '".$now."', ".(int)$GLOBALS['conf']->entity.")";
        $res = $this->db->query($sql);
        if ($res) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "foodbank_beneficiaries");
            return $this->id;
        }
        $this->errors[] = $this->db->lasterror();
        return false;
    }

    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries WHERE rowid=" . (int)$id;
        $res = $this->db->query($sql);
        if ($res && $this->db->num_rows($res) > 0) {
            $obj = $this->db->fetch_object($res);
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->firstname = $obj->firstname;
            $this->lastname = $obj->lastname;
            $this->phone = $obj->phone;
            $this->email = $obj->email;
            $this->address = $obj->address;
            $this->note = $obj->note;
            return true;
        }
        return false;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "foodbank_beneficiaries WHERE rowid=" . (int)$id;
        return $this->db->query($sql);
    }
}
