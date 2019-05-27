<?php

class Vendors_model extends CI_Model
{

    private $table = 'vendors';
    private $table_view = 'vendors';
    private $column_order = array(null, 'eg.name', 'l.name', 'e.name', 'e.email', 'e.contact', 't.updated_at', null);
    private $column_search = array('eg.name', 'l.name', 'e.name', 'e.email', 'e.contact', 't.updated_at');
    private $order = array('t.updated_at' => 'desc');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
    }

    private function _getTablesQuery()
    {
        $this->db->select('t.*');
        $this->db->select('eg.name as group');
        $this->db->select('l.name as location');
        $this->db->from($this->table_view . ' t');
        $this->db->join('vendor_groups eg', 'eg.id=t.group_id');
        $this->db->join('locations l', 'l.id=t.location_id');
        if ($this->input->post('name')):
            $this->db->where('t.name', $this->input->post('name'));
        endif;
        $status = 1;
        if ($this->input->post('status') && $this->input->post('status') == 'false'):
            $status = 0;
        endif;
        $this->db->where('t.status', $status);
        $i = 0;
        foreach ($this->column_search as $item):
            if (isset($_POST['length'])):
                if (isset($_POST['search']['value'])):
                    if ($i === 0):
                        $this->db->group_start();
                        $this->db->like($item, $_POST['search']['value']);
                    else:
                        $this->db->or_like($item, $_POST['search']['value']);
                    endif;
                    if (count($this->column_search) - 1 == $i):
                        $this->db->group_end();
                    endif;
                endif;
            endif;
            $i++;
        endforeach;
        if (isset($_POST['order'])):
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        elseif (isset($this->order)):
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        endif;
    }

    public function getTables()
    {
        $this->_getTablesQuery();
        if ($this->input->post('length')):
            if ($this->input->post('length') != -1):
                if ($this->input->post('start')):
                    $start = $this->input->post('start');
                else:
                    $start = 0;
                endif;
                $this->db->limit($this->input->post('length'), $start);
            endif;
        endif;
        $query = $this->db->get();
//        print_r($this->db->last_query());
        //        exit;
        return $query->result_array();
    }

    public function countFiltered()
    {
        $this->_getTablesQuery();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function countAll()
    {
        $this->db->from($this->table_view);
        return $this->db->count_all_results();
    }

    public function getById($id)
    {
        $this->db->select('t.*');
        $this->db->select('eg.name as group');
        $this->db->select('l.name as location');
        $this->db->from($this->table_view . ' t');
        $this->db->join('vendor_groups eg', 'eg.id=t.group_id');
        $this->db->join('locations l', 'l.id=t.location_id');
        $this->db->where('t.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getByEmail($email)
    {
        $this->db->from($this->table_view);
        $this->db->where('email', $email);
        if ($this->input->post('id')):
            $this->db->where('id !=', $this->input->post('id'));
        endif;
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getByContact($contact)
    {
        $this->db->from($this->table_view);
        $this->db->where('contact', $contact);
        if ($this->input->post('id')):
            $this->db->where('id !=', $this->input->post('id'));
        endif;
        $query = $this->db->get();
        return $query->row_array();
    }

    public function deleteById($id)
    {
        $this->db->trans_start();
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false):
            $this->db->trans_rollback();
            return false;
        else:
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function save()
    {
        $this->db->trans_start();
        $this->db->set('location_id', $this->input->post('location_id'));
        $this->db->set('group_id', $this->input->post('group_id'));
        $this->db->set('name', $this->input->post('name'));
        $this->db->set('email', $this->input->post('email'));
        $this->db->set('contact', $this->input->post('contact'));
        $this->db->set('password', $this->input->post('password'));
        $this->db->set('status', $this->input->post('status'));

        if ($this->input->post('id')):
            $this->db->set('updated_at', $this->currectDatetime);
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $this->db->update($this->table);
        else:
            $this->db->set('created_at', $this->currectDatetime);
            $this->db->insert($this->table);
            $id = $this->db->insert_id();
        endif;

        if ($this->db->trans_status() === false):
            $this->db->trans_rollback();
            return false;
        else:
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function checkUsername($username)
    {
        $this->db->from($this->table_view);
        $this->db->group_start();
        $this->db->where('email', $username);
        $this->db->or_where('contact', $username);
        $this->db->group_end();
        $query = $this->db->get();
        return $query->row_array();
    }

    public function login()
    {
        $this->db->from($this->table_view);
        $this->db->group_start();
        $this->db->where('email', $this->input->post('username'));
        $this->db->or_where('contact', $this->input->post('username'));
        $this->db->group_end();
        $this->db->where('password', $this->input->post('password'));
        $query = $this->db->get();
        return $query->row_array();
    }

}
