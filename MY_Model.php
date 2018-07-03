<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Model extends CI_Model
{
    const DB_TABLE = 'abstract';
    const DB_TABLE_PK = 'abstract';
    
    /**
     * Setting ID
     * @param int $id
     */
    public function setID($id = 0)
    {
        if ((int) $id != 0) {
            $this->{$this::DB_TABLE_PK} = $id;
        }

        return $this;
    }

    /**
     * Get ID from the database model
     * @return int id model
     */
    public function getID()
    {
        return $this->{$this::DB_TABLE_PK};
    }
    
    /**
     * Insert 1 record.
     */
    private function insert()
    {
        $this->db->insert($this::DB_TABLE, $this->htmlEscape($this));
        return $this->{$this::DB_TABLE_PK} = $this->db->insert_id();
    }
    
    /**
     * Insert multiple records.
     * @param array $datas
     */
    private function insert_batch($datas = array())
    {
        if (!empty($datas)) {
            return $this->db->insert_batch($this::DB_TABLE, $this->htmlEscape($datas));
        }
    }
    
    private function htmlEscape($data)
    {            
        $datas = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $val = $this->htmlEscape($value);
            } else {
                if ($key == "slug") {
                    $val = url_title(strtolower($value), '-', true);
                } elseif ($key == "url") {
                    $val = prep_url($value);
                } else {
                    $val = strip_tags($value);
                }
            }

            if (!empty($val)) {
                $datas[$key] =$val;
            }
        }
        return $datas;
    }

    /**
     * Update 1 record.
     */
    private function update()
    {
        $upd = array();
        foreach ($this as $key => $value) {
            if (!is_null($value)) {
                
                if (is_array($value)) {
                    $upd[$key] = json_encode($value);
                } else {
                    if ($key == "slug") {
                        $upd[$key] = url_title($value, '-', true);
                    } elseif ($key == "url") {
                        $upd[$key] = prep_url($value);
                    } else {
                        $upd[$key] = strip_tags($value);
                    }
                }

                if ($value == "") {
                    unset($upd[$key]);
                }
            }
        }
        return $this->db->update($this::DB_TABLE, $upd, array($this::DB_TABLE_PK => $this->{$this::DB_TABLE_PK}));
    }
    
    /**
     * Populate from an array or standard class.
     * @param mixed $row
     */
    public function populate($row)
    {
        foreach ($row as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $this->$key = strip_tags($value);
            }
        }
        
        unset($this);

        return $row;
    }
    
    /**
     * Load from the database.
     * @param string $p
     */
    public function search($p = "", $wht = "")
    {
        if ($wht == "") {
            $this->db->like($p);
        } elseif ($wht == "or") {
            $this->db->or_like($p);
        } elseif ($wht == "ornot") {
            $this->db->or_not_like($p);
        } elseif ($wht == "not") {
            $this->db->not_like($p);
        }
        
        $query = $this->db->get($this::DB_TABLE);
        
        return $this->populate($query->result());
    }
    
    /**
     * Load from the database.
     * @param array $param
     */

    public function load($param = array())
    {
        $select = isset($param['select']) ? $param['select'] : '';
        $join = isset($param['join']) ? $param['join'] : '';
        $order = isset($param['order']) ? $param['order'] : '';
        $id = isset($param['id']) ? $param['id'] : '';
        $where_not_in = isset($param['where_not_in']) ? $param['where_not_in'] : '';

        if (is_array($order) && count($order)>1) {
            foreach ($order as $key => $value) {
                $this->db->order_by($value->key, $value->value);
            }
        } elseif (is_array($order)) {
            foreach ($order as $key => $value) {
                $this->db->order_by($key, $value);
            }
        }

        $jointype = 'inner';
        if (is_array($join) && count($order) > 1) {
            foreach ($join as $key => $value) {
                if (isset($value['join'])) {
                    $jointype = $value['join'];
                }
                
                $this->db->join($value['table'], $value['on'], $jointype);
            }
        } elseif (is_array($join) && count($order) == 1) {
            if (isset($join['join'])) {
                $join = $join['join'];
            }
            
            $this->db->join($join['table'], $join['on'], $jointype);
        }

        if (!empty($where_not_in)) {
            foreach ($where_not_in as $wnikey => $wnival) {
                $this->db->where_not_in($wnikey, $wnival);
            }
        }
        
        if (!empty($select)) {
            $this->db->select($select);
        }        
        

        if (!empty($this->htmlEscape($this))) {
            $query = $this->db->get_where($this::DB_TABLE, $this->htmlEscape($this));
        } elseif ($id != "") {
            $whr = array($this::DB_TABLE_PK => $id);
            $query = $this->db->get_where($this::DB_TABLE, $whr);
        } else {
            $query = $this->db->get_where($this::DB_TABLE);
        }
        return $this->populate($query->result());
    }

    /**
     * Load from the database.
     * @param int $id
     */
    public function countRows($id)
    {
        if (is_array($id)) {
            $whr = $id;
        } else {
            $whr = array($this::DB_TABLE_PK => $id);
        }
        $query = $this->db->get_where($this::DB_TABLE, $whr);
        return $query->num_rows();
    }
    
    /**
     * Delete the current record.
     */
    public function delete()
    {
        $this->db->delete($this::DB_TABLE, $this->htmlEscape($this));
        unset($this->{$this::DB_TABLE});
    }
    
    /**
     * Save the record.
     */
    public function save($param = array(), $batch = false)
    {
        if (isset($this->{$this::DB_TABLE_PK})) {
            return $this->update();
        } else {
            if (!$batch) {
                return $this->insert();
            } else {
                return $this->insert_batch($param);
            }
        }
    }
    
    /**
     * Get an array of Models with an optional limit, offset.
     * 
     * @param int $limit Optional.
     * @param int $offset Optional; if set, requires $limit.
     * @return array Models populated by database, keyed by PK.
     */
    public function get($limit = 0, $offset = 0)
    {
        if ($limit) {
            $query = $this->db->get($this::DB_TABLE, $limit, $offset);
        } else {
            $query = $this->db->get($this::DB_TABLE);
        }
        $ret_val = array();
        $class = get_class($this);
        foreach ($query->result() as $row) {
            $model = new $class;
            $model->populate($row);
            $ret_val[$row->{$this::DB_TABLE_PK}] = $model;
        }
        return $ret_val;
    }
}
