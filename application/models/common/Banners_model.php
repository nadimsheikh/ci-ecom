<?php

class Banners_model extends CI_Model
{

    private $table = 'banners';
    private $table_view = 'banners';
    private $column_order = array(null, 'b.name', 't.name', 'b.updated_at', null);
    private $column_search = array('b.name', 't.name', 'b.updated_at');
    private $order = array('b.created_at' => 'desc');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
    }

    private function _getTablesQuery()
    {
        $this->db->select('b.*');
        $this->db->select('t.name as type');
        $this->db->from($this->table_view . ' b');
        $this->db->join('types t', 't.id=b.type_id');

        if ($this->input->post('name')):
            $this->db->where('b.name', $this->input->post('name'));
        endif;
        $status = 1;
        if ($this->input->post('status') && $this->input->post('status') == 'false'):
            $status = 0;
        endif;
        $this->db->where('b.status', $status);
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
        $this->db->select('b.*');
        $this->db->select('t.name as type');
        $this->db->from($this->table_view . ' b');
        $this->db->join('types t', 't.id=b.type_id');
        $this->db->where('b.id', $id);
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

        $this->db->set('type_id', $this->input->post('type_id'));
        $this->db->set('name', $this->input->post('name'));
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

        $this->db->where('banner_id', $id);
        $this->db->delete('banner_images');

        if ($this->input->post('images')):
            $images = json2arr($this->input->post('images'));
            if ($images):
                foreach ($images as $image):
                    $this->db->set('banner_id', $id);
                    // $this->db->set('type', $image['type']);
                    // $this->db->set('type_id', $image['type_id']);
                    $this->db->set('name', $image['name']);
                    $this->db->set('image', $image['image']);
                    $this->db->set('link', $image['link']);
                    $this->db->set('sort_order', $image['sort_order']);
                    $this->db->insert('banner_images');
                endforeach;
            endif;
        endif;

        if ($this->db->trans_status() === false):
            $this->db->trans_rollback();
            return false;
        else:
            $this->db->trans_commit();
            return true;
        endif;
    }

    public function getImages($id)
    {
        $this->db->from('banner_images');
        $this->db->where('banner_id', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

}