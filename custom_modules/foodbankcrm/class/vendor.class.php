<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class VendorFB extends CommonObject
{
    public $element       = 'foodbank_vendor';
    public $table_element = 'foodbank_vendors';
    public $picto         = 'company';

    public $id;
    public $ref;
    public $name;
    public $category;
    public $contact_person;
    public $contact_email;
    public $contact_phone;
    public $phone;
    public $email;
    public $address;
    public $description;
    public $status;
    
    // NEW EXTENDED FIELDS
    public $registration_number;
    public $tax_id;
    public $website;
    public $bank_name;
    public $bank_account_number;
    public $payment_terms;

    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($GLOBALS['conf']->entity) ? (int) $GLOBALS['conf']->entity : 1;
        $this->status = 'Active'; // Default
    }

    // CREATE
    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        // Standard
        $sql .= "ref, name, category, contact_person, contact_email, contact_phone, ";
        $sql .= "phone, email, address, description, status, ";
        // Extended
        $sql .= "registration_number, tax_id, website, bank_name, bank_account_number, payment_terms, ";
        // System (entity omitted — uses DB DEFAULT 1)
        $sql .= "date_creation";
        $sql .= ") VALUES (";
        
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->name) . "',";
        $sql .= "'" . $this->db->escape($this->category) . "',";
        $sql .= "'" . $this->db->escape($this->contact_person) . "',";
        $sql .= "'" . $this->db->escape($this->contact_email) . "',";
        $sql .= "'" . $this->db->escape($this->contact_phone) . "',";
        $sql .= "'" . $this->db->escape($this->phone) . "',";
        $sql .= "'" . $this->db->escape($this->email) . "',";
        $sql .= "'" . $this->db->escape($this->address) . "',";
        $sql .= "'" . $this->db->escape($this->description) . "',";
        $sql .= "'" . $this->db->escape($this->status) . "',";

        // Extended Values
        $sql .= "'" . $this->db->escape($this->registration_number) . "',";
        $sql .= "'" . $this->db->escape($this->tax_id) . "',";
        $sql .= "'" . $this->db->escape($this->website) . "',";
        $sql .= "'" . $this->db->escape($this->bank_name) . "',";
        $sql .= "'" . $this->db->escape($this->bank_account_number) . "',";
        $sql .= "'" . $this->db->escape($this->payment_terms) . "',";

        $sql .= "NOW()";
        $sql .= ")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    // FETCH
    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$id;
        $res = $this->db->query($sql);
        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->name = $obj->name;
            $this->category = $obj->category;
            $this->contact_person = $obj->contact_person;
            $this->contact_email = $obj->contact_email;
            $this->contact_phone = $obj->contact_phone;
            $this->phone = $obj->phone;
            $this->email = $obj->email;
            $this->address = $obj->address;
            $this->description = $obj->description;
            $this->status = $obj->status;
            
            // Extended
            $this->registration_number = $obj->registration_number;
            $this->tax_id = $obj->tax_id;
            $this->website = $obj->website;
            $this->bank_name = $obj->bank_name;
            $this->bank_account_number = $obj->bank_account_number;
            $this->payment_terms = $obj->payment_terms;
            
            return 1;
        }
        return 0;
    }

    // UPDATE
    public function update($user = null, $notrigger = false)
    {
        $this->db->begin();
        
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "name = '" . $this->db->escape($this->name) . "',";
        $sql .= "category = '" . $this->db->escape($this->category) . "',";
        $sql .= "contact_person = '" . $this->db->escape($this->contact_person) . "',";
        $sql .= "contact_email = '" . $this->db->escape($this->contact_email) . "',";
        $sql .= "contact_phone = '" . $this->db->escape($this->contact_phone) . "',";
        $sql .= "phone = '" . $this->db->escape($this->phone) . "',";
        $sql .= "email = '" . $this->db->escape($this->email) . "',";
        $sql .= "address = '" . $this->db->escape($this->address) . "',";
        $sql .= "description = '" . $this->db->escape($this->description) . "',";
        $sql .= "status = '" . $this->db->escape($this->status) . "',";

        // Extended
        $sql .= "registration_number = '" . $this->db->escape($this->registration_number) . "',";
        $sql .= "tax_id = '" . $this->db->escape($this->tax_id) . "',";
        $sql .= "website = '" . $this->db->escape($this->website) . "',";
        $sql .= "bank_name = '" . $this->db->escape($this->bank_name) . "',";
        $sql .= "bank_account_number = '" . $this->db->escape($this->bank_account_number) . "',";
        $sql .= "payment_terms = '" . $this->db->escape($this->payment_terms) . "'";
        
        $sql .= " WHERE rowid = " . (int)$this->id;

        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function delete($user = null, $notrigger = false)
    {
        $this->db->begin();
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$this->id;
        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM " . MAIN_DB_PREFIX . $this->table_element;
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $nextId = ($obj->maxid ?? 0) + 1;
        return 'VEN' . date('Y') . '-' . sprintf('%04d', $nextId);
    }
}
?>