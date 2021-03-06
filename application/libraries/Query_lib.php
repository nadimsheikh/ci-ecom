<?php
class Query_lib
{
    private $ci;
    public $table;
    public $table_view;
    public $column_search;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->database();
    }

    public function getSearch()
    {
        $i = 0;
        foreach ($this->column_search as $item):
            if ($this->ci->input->post('search')):
                if ($i === 0):
                    $this->ci->db->group_start();
                    $this->ci->db->like($item, $this->ci->input->post('search'));
                else:
                    $this->ci->db->or_like($item, $this->ci->input->post('search'));
                endif;
                if (count($this->column_search) - 1 == $i):
                    $this->ci->db->group_end();
                endif;
            endif;
            $i++;
        endforeach;
    }

    public function getSort()
    {
        if ($this->ci->input->post('sort_by')):
            $this->ci->db->order_by($this->ci->input->post('sort_by'), $this->ci->input->post('sort_dir'));
        else:
            $this->ci->db->order_by('updated_at', 'desc');
        endif;
    }

    public function getPaginate()
    {
        if ($this->ci->input->post('length')):
            if ($this->ci->input->post('length') != -1):
                if ($this->ci->input->post('start')):
                    $start = $this->ci->input->post('start');
                else:
                    $start = 0;
                endif;
                $this->ci->db->limit($this->ci->input->post('length'), $start);
            endif;
        endif;
    }

    public function deleteById($id)
    {
        $this->ci->db->trans_start();
        $this->ci->db->where('id', $id);
        $this->ci->db->delete($this->table);
        $this->ci->db->trans_complete();
        if ($this->ci->db->trans_status() === false):
            $this->ci->db->trans_rollback();
            return false;
        else:
            $this->ci->db->trans_commit();
            return true;
        endif;
    }

    public function like()
    {
        if (isset($_POST['like'])):
            foreach ($_POST['like'] as $key => $value):
                $this->ci->db->like($key, $value);
            endforeach;
        endif;
    }
    public function where()
    {
        if (isset($_POST['where'])):
            foreach ($_POST['where'] as $key => $value):
                $this->ci->db->where($key, $value);
            endforeach;
        endif;
    }

    public function getById($id)
    {
        $this->ci->db->from($this->table_view);
        $this->ci->db->where('id', $id);
        $query = $this->ci->db->get();
        return $query->row_array();
    }

    public function getSpecialPrice()
    {
        if ($this->ci->input->post('customer_id')):
            $customer = $this->ci->db->get_where('customers', ['id' => $this->ci->input->post('customer_id')])->row_array();
            $this->ci->db->select('*, (SELECT pp.price FROM product_prices pp WHERE pp.product_id=' . $this->table_view . '.id AND pp.start <= date(now()) AND pp.end >= date(now()) AND pp.status=1 AND pp.customer_group_id=' . $customer['group_id'] . ' LIMIT 1) AS special_price');
        else:
            $this->ci->db->select('*, 0 AS special_price');
        endif;
    }
}
