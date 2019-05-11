<?php

class Customers_model extends CI_Model
{

    private $table = 'customers';
    private $table_view = 'customers';
    private $column_order = array(null, 'name', 'email', 'contact', 'updated_at', null);
    private $column_search = array('name', 'email', 'contact', 'updated_at');
    private $order = array('updated_at' => 'desc');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
    }

    private function _getTablesQuery()
    {
        $this->db->from($this->table_view);
        if ($this->input->post('name')):
            $this->db->where('name', $this->input->post('name'));
        endif;
        $status = 1;
        if ($this->input->post('status') && $this->input->post('status') == 'false'):
            $status = 0;
        endif;
        $this->db->where('status', $status);
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
        $this->db->from($this->table_view);
        $this->db->where('id', $id);
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

    public function setToken($id)
    {
        $this->db->trans_start();

        $token = random_string('alnum', 8);

        $this->db->set('customer_id', $id);
        $this->db->set('token', $token);

        $query = $this->db->get_where('customer_sessions', ['customer_id' => $this->input->post('customer_id')])->row_array();
        if ($query):
            $this->db->where('id', $query['id']);
            $this->db->update('customer_sessions');
        else:
            $this->db->insert('customer_sessions');
        endif;

        $this->db->set('token', $token);
        $this->db->where('customer_id', $id);
        $this->db->update('carts');

        if ($this->db->trans_status() === false):
            $this->db->trans_rollback();
            return false;
        else:
            $this->db->trans_commit();
            return $token;
        endif;
    }

}
