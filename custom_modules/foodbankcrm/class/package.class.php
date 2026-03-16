<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Package extends CommonObject
{
    public $element = 'foodbank_package';
    public $table_element = 'foodbank_packages';
    public $picto = 'box';

    public $id;
    public $ref;
    public $name;
    public $description;
    public $status;
    public $image_url;
    public $date_creation;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = isset($GLOBALS['conf']->entity) ? (int) $GLOBALS['conf']->entity : 1;
        $this->status = 'Active';
    }

    public function create($user = null, $notrigger = false)
    {
        if (empty($this->ref)) {
            $this->ref = $this->getNextRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
        $sql .= "ref, name, description, status, image_url, entity, datec";
        $sql .= ") VALUES (";
        $sql .= "'" . $this->db->escape($this->ref) . "',";
        $sql .= "'" . $this->db->escape($this->name) . "',";
        $sql .= "'" . $this->db->escape($this->description) . "',";
        $sql .= "'" . $this->db->escape($this->status) . "',";
        $sql .= "'" . $this->db->escape($this->image_url ?? '') . "',";
        $sql .= (int)$this->entity . ",";
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

    public function fetch($id)
    {
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$id;
        $res = $this->db->query($sql);
        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->name = $obj->name;
            $this->description = $obj->description;
            $this->status = $obj->status;
            $this->image_url = $obj->image_url ?? '';
            $this->date_creation = $obj->datec;
            return 1;
        }
        return 0;
    }

    public function update($user = null, $notrigger = false)
    {
        $this->db->begin();
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
        $sql .= "name='" . $this->db->escape($this->name) . "',";
        $sql .= "description='" . $this->db->escape($this->description) . "',";
        $sql .= "status='" . $this->db->escape($this->status) . "',";
        $sql .= "image_url='" . $this->db->escape($this->image_url ?? '') . "'";
        $sql .= " WHERE rowid=" . (int)$this->id;

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
        // First delete items linked to this package
        $this->db->begin();
        $sql_items = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package=".(int)$this->id;
        if (!$this->db->query($sql_items)) {
            $this->db->rollback();
            return -1;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=" . (int)$this->id;
        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        }
        $this->error = $this->db->lasterror();
        $this->db->rollback();
        return -1;
    }

    public function getItemCount() {
        $sql = "SELECT COUNT(*) as c FROM ".MAIN_DB_PREFIX."foodbank_package_items WHERE fk_package=".(int)$this->id;
        $res = $this->db->query($sql);
        return ($res && $obj=$this->db->fetch_object($res)) ? $obj->c : 0;
    }

    public function getNextRef()
    {
        $sql = "SELECT MAX(rowid) as maxid FROM " . MAIN_DB_PREFIX . $this->table_element;
        $res = $this->db->query($sql);
        $obj = $this->db->fetch_object($res);
        $nextId = ($obj->maxid ?? 0) + 1;
        return 'PKG' . date('Y') . '-' . sprintf('%04d', $nextId);
    }
}
?>