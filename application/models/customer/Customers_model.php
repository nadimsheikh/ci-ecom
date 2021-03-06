<?php

class Customers_model extends CI_Model
{

    private $table = 'customers';
    private $table_view = 'customers_view';
    private $column_search = array('name', 'email', 'contact', 'updated_at');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
        $this->query_lib->table = $this->table;
        $this->query_lib->table_view = $this->table_view;
        $this->query_lib->column_search = $this->column_search;
    }

    private function _getTablesQuery()
    {
        $this->db->from($this->table_view);
        $this->query_lib->where();
        $this->query_lib->like();
        $this->query_lib->getSearch();
        $this->query_lib->getSort();
    }

    public function getTables()
    {
        $this->_getTablesQuery();
        $this->query_lib->getPaginate();
        $query = $this->db->get();
        // print_r($this->db->last_query());
        // exit;
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

    public function deleteById($id)
    {
        $this->db->trans_start();
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            return true;
        endif;
    }


    public function getByEmail($email)
    {
        $this->db->from($this->table_view);
        $this->db->where('email', $email);
        if ($this->input->post('id')) :
            $this->db->where('id !=', $this->input->post('id'));
        endif;
        $query = $this->db->get();
        return $query->row_array();
    }

    public function getByContact($contact)
    {
        $this->db->from($this->table_view);
        $this->db->where('contact', $contact);
        if ($this->input->post('id')) :
            $this->db->where('id !=', $this->input->post('id'));
        endif;
        $query = $this->db->get();
        return $query->row_array();
    }

    public function save()
    {
        $this->db->trans_start();
        $this->db->set('group_id', $this->input->post('group_id'));
        $this->db->set('name', $this->input->post('name'));
        $this->db->set('email', $this->input->post('email'));
        $this->db->set('contact', $this->input->post('contact'));

        if ($this->input->post('gst_number')) :
            $this->db->set('gst_number', $this->input->post('gst_number'));
        endif;

        if ($this->input->post('pan_number')) :
            $this->db->set('pan_number', $this->input->post('pan_number'));
        endif;

        if ($this->input->post('password')) :
            $this->db->set('password', md5($this->input->post('password')));
        endif;

        if ($this->input->post('status')) :
            $this->db->set('status', $this->input->post('status'));
        else :
            $this->db->set('status', 1);
        endif;

        if ($this->input->post('id')) :
            $this->db->set('updated_at', $this->currectDatetime);
            $id = $this->input->post('id');
            $this->db->where('id', $id);
            $this->db->update($this->table);
        else :
            $this->db->set('created_at', $this->currectDatetime);
            $this->db->insert($this->table);
            $id = $this->db->insert_id();
        endif;

        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
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
        $this->db->where('password', md5($this->input->post('password')));
        $query = $this->db->get();
        return $query->row_array();
    }

    public function checkOldPass($pass)
    {
        $this->db->from($this->table_view);
        $this->db->where('id', $this->input->post('id'));
        $this->db->where('password', md5($pass));
        $query = $this->db->get();
        return $query->row_array();
    }


    public function updatePassword()
    {
        $this->db->trans_start();

        $this->db->where('id', $this->input->post('id'));
        $this->db->set('password', md5($this->input->post('newPass')));
        $this->db->update($this->table);

        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function setToken($id)
    {
        $this->db->trans_start();

        $token = random_string('alnum', 8);

        $this->db->where('customer_id', $id);
        $this->db->delete('customer_sessions');

        $this->db->set('customer_id', $id);
        $this->db->set('token', $token);
        $this->db->insert('customer_sessions');

        $this->db->set('token', $token);
        $this->db->where('customer_id', $id);
        $this->db->update('carts');

        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            return $token;
        endif;
    }
}
