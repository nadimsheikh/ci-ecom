<?php

class Locations_model extends CI_Model
{

    private $table = 'locations';
    private $table_view = 'locations_view';
    private $column_search = array('name', 'contact_person', 'contact', 'email', 'postcode', 'address', 'country', 'zone', 'city', 'updated_at');
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
        $this->db->select('*');
        $this->db->from($this->table_view);

        if ($this->input->post('latitude') && $this->input->post('longitude')) :
            $this->db->select('111.111 *
            DEGREES(ACOS(LEAST(COS(RADIANS(' . $this->input->post('latitude') . '))
                 * COS(RADIANS(latitude))
                 * COS(RADIANS(' . $this->input->post('longitude') . ' - longitude))
                 + SIN(RADIANS(' . $this->input->post('latitude') . '))
                 * SIN(RADIANS(latitude)), 1.0))) AS distance');
        else :
            $this->db->select('0 AS distance');
        endif;

        if ($this->input->post('distance')) :
            $this->db->having('distance <=', $this->input->post('distance'));
        endif;

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

    public function save()
    {
        $this->db->trans_start();

        $this->db->set('name', $this->input->post('name'));
        $this->db->set('contact_person', $this->input->post('contact_person'));
        $this->db->set('contact', $this->input->post('contact'));
        $this->db->set('email', $this->input->post('email'));
        $this->db->set('country_id', $this->input->post('country_id'));
        $this->db->set('zone_id', $this->input->post('zone_id'));
        $this->db->set('city_id', $this->input->post('city_id'));
        $this->db->set('postcode', $this->input->post('postcode'));
        $this->db->set('address', $this->input->post('address'));

        if ($this->input->post('sort_order')) :
            $this->db->set('sort_order', $this->input->post('sort_order'));
        else :
            $this->db->set('sort_order', 1);
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
}
