<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class DistributionLine extends CommonObject
{
    public $element = 'distributionline';
    public $table_element = 'foodbank_distribution_lines';
    
    public $id;
    public $fk_distribution;
    public $fk_donation;
    public $product_name;
    public $quantity;
    public $unit;
    public $note;

    public function __construct($db) { $this->db = $db; }

    public function create($user = null)
    {
        $this->db->begin();
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."foodbank_distribution_lines (";
        $sql .= "fk_distribution, fk_donation, product_name, quantity, unit, note";
        $sql .= ") VALUES (";
        $sql .= (int)$this->fk_distribution.",";
        $sql .= (int)$this->fk_donation.",";
        $sql .= "'".$this->db->escape($this->product_name)."',";
        $sql .= (float)$this->quantity.",";
        $sql .= "'".$this->db->escape($this->unit)."',";
        $sql .= "'".$this->db->escape($this->note)."'";
        $sql .= ")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."foodbank_distribution_lines");
            
            // CRITICAL: Deduct Stock
            $this->updateDonationAllocation($this->fk_donation, $this->quantity, 'add');
            
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    public function fetch($id) {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE rowid=".(int)$id;
        $res = $this->db->query($sql);
        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->fk_distribution = $obj->fk_distribution;
            $this->fk_donation = $obj->fk_donation;
            $this->product_name = $obj->product_name;
            $this->quantity = $obj->quantity;
            $this->unit = $obj->unit;
            $this->note = $obj->note;
            return 1;
        }
        return -1;
    }

    public function delete($user = null)
    {
        $this->db->begin();
        
        // CRITICAL: Restore Stock
        $this->updateDonationAllocation($this->fk_donation, $this->quantity, 'remove');

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines WHERE rowid=".(int)$this->id;
        if ($this->db->query($sql)) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    // Logic to manage inventory allocation
    private function updateDonationAllocation($fk_donation, $quantity, $action)
    {
        $op = ($action == 'add') ? '+' : '-';
        $sql = "UPDATE ".MAIN_DB_PREFIX."foodbank_donations 
                SET quantity_allocated = quantity_allocated $op ".(float)$quantity." 
                WHERE rowid = ".(int)$fk_donation;
        $this->db->query($sql);
    }

    public static function getAllByDistribution($db, $fk_distribution)
    {
        $lines = array();
        $sql = "SELECT dl.*, d.ref as donation_ref 
                FROM ".MAIN_DB_PREFIX."foodbank_distribution_lines dl
                LEFT JOIN ".MAIN_DB_PREFIX."foodbank_donations d ON dl.fk_donation = d.rowid
                WHERE dl.fk_distribution = ".(int)$fk_distribution;
        $res = $db->query($sql);
        while ($res && $obj = $db->fetch_object($res)) {
            $line = new DistributionLine($db);
            $line->id = $obj->rowid;
            $line->fk_distribution = $obj->fk_distribution;
            $line->fk_donation = $obj->fk_donation;
            $line->product_name = $obj->product_name;
            $line->quantity = $obj->quantity;
            $line->unit = $obj->unit;
            $line->note = $obj->note;
            $line->donation_ref = $obj->donation_ref;
            $lines[] = $line;
        }
        return $lines;
    }
}
?>