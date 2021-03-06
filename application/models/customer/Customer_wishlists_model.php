<?php

class Customer_wishlists_model extends CI_Model
{

    private $table = 'customer_wishlists';
    private $table_view = 'customer_wishlists_view';
    private $column_search = array('product_name', 'customer_name', 'updated_at');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
        $this->query_lib->table = $this->table;
        $this->query_lib->table_view = $this->table_view;
        $this->query_lib->column_search = $this->column_search;
    }

    private function getSpecialPrice()
    {        
        if ($this->input->post('where[customer_id]')) :
            $customer = $this->db->get_where('customers', ['id' => $this->input->post('where[customer_id]')])->row_array();
            $this->db->select('*, (SELECT pp.price FROM product_prices pp WHERE pp.product_id=' . $this->table_view . '.id AND pp.start <= date(now()) AND pp.end >= date(now()) AND pp.status=1 AND pp.customer_group_id=' . $customer['group_id'] . ' LIMIT 1) AS special_price');
        else :
            $this->db->select('*, 0 AS special_price');
        endif;
    }

    private function _getTablesQuery()
    {
        $this->getSpecialPrice();
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
        if ($this->db->trans_status() === false):
            $this->db->trans_rollback();
            return false;
        else:
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function checkWishlist($id)
    {
        $this->db->from($this->table_view);
        $this->db->where('product_id', $id);
        $this->db->where('customer_id', $this->input->post('customer_id'));
        $query = $this->db->get();
        return $query->row_array();
    }

    public function save()
    {
        $this->db->trans_start();
        $this->db->set('customer_id', $this->input->post('customer_id'));
        $this->db->set('product_id', $this->input->post('product_id'));
        if ($this->input->post('status')):
            $this->db->set('status', $this->input->post('status'));
        else:
            $this->db->set('status', 1);
        endif;

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

}
