<?php

class Purchases_model extends CI_Model
{

    private $table = 'purchases';
    private $table_view = 'purchases_view';
    private $column_search = array('name', 'email', 'contact', 'total', 'status', 'updated_at');
    private $currectDatetime = '';

    public function __construct()
    {
        parent::__construct();
        $this->currectDatetime = date('Y-m-d h:i:s');
        $this->query_lib->table = $this->table;
        $this->query_lib->table_view = $this->table_view;
        $this->query_lib->column_search = $this->column_search;
        $this->load->model('purchase/purchase_carts_model');
        $this->load->model('product/products_model');
        $this->load->model('stock/stocks_model');
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

    public function save()
    {
        $this->db->trans_start();

        if ($this->input->post('purchase_type_id')) {
            $this->db->set('purchase_type_id', $this->input->post('purchase_type_id'));
        } else {
            $this->db->set('purchase_type_id', 1);
        }
        $this->db->set('vendor_id', $this->input->post('vendor_id'));
        $this->db->set('comment', $this->input->post('comment'));

        if ($this->input->post('status')) :
            $this->db->set('status', $this->input->post('status'));
        else :
            $this->db->set('status', 1);
        endif;

        if ($this->input->post('purchase_status_id')) :
            $this->db->set('purchase_status_id', $this->input->post('purchase_status_id'));
        else :
            $this->db->set('purchase_status_id', 1);
        endif;

        $filter['token'] = $this->input->post('token');
        $filter['user_id'] = $this->input->post('user_id');

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

        $this->setProducts($id);
        $this->setTotals($id);
        $this->purchaseToStock($id);

        if ($this->db->trans_status() === false) :
            $this->db->trans_rollback();
            return false;
        else :
            $this->db->trans_commit();
            $this->clearCart($this->input->post('token'));
            return true;
        endif;
    }

    public function getProducts($id)
    {

        $this->db->from('purchase_products_view');
        $this->db->where('purchase_id', $id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function setProducts($id)
    {
        $this->db->where('purchase_id', $id);
        $this->db->delete('purchase_products');

        $filter = [];
        $filter['token'] = $this->input->post('token');
        $filter['user_id'] = $this->input->post('user_id');
        $products = $this->purchase_carts_model->getProducts($filter);
        // print_r($products);
        // exit;
        if ($products) :
            foreach ($products as $value) :
                $total = ($value['price'] * $value['quantity']);

                $this->db->set('purchase_id', $id);
                $this->db->set('product_id', $value['product_id']);
                $this->db->set('quantity', $value['quantity']);
                $this->db->set('price', $value['price']);
                $this->db->set('tax', $value['tax']);
                $this->db->set('total', $total);

                $this->db->insert('purchase_products');
            endforeach;
        endif;
    }

    public function setTotals($id)
    {
        $this->db->where('purchase_id', $id);
        $this->db->delete('purchase_totals');

        $totals = [];
        $taxes = $this->purchase_carts_model->getTaxTotal();
        $total = 0;
        $totalTax = 0;

        $total_data = [
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        ];

        $extensions = [
            'sub_total',
            'total_tax',
            'total',
        ];

        foreach ($extensions as $extension) :
            $this->load->model('purchase/total/' . $extension . '_model');
            $this->{$extension . '_model'}->getTotal($total_data);
        endforeach;

        foreach ($totals as $totalValue) :
            if ($totalValue['code'] == 'total_tax') :
                $totalTax = $totalValue['value'];
            endif;
            if ($totalValue['code'] == 'total') :
                $total = $totalValue['value'];
            endif;

            $this->db->set('purchase_id', $id);
            $this->db->set('code', $totalValue['code']);
            $this->db->set('title', $totalValue['title']);
            $this->db->set('value', $totalValue['value']);
            $this->db->set('sort_order', $totalValue['sort_order']);
            $this->db->insert('purchase_totals');
        endforeach;

        $this->db->set('total', $total);
        $this->db->set('total_tax', $totalTax);
        $this->db->where('id', $id);
        $this->db->update('purchases');
    }



    public function clearCart($token)
    {
        $this->db->where('token', $token);
        $this->db->delete('purchase_carts');
    }

    public function purchaseToStock($id)
    {
        $purchase = $this->getById($id);

        if ($purchase['purchase_status_id'] == $this->settings_lib->config('config', 'complete_purchase_status')) :
            $purchaseProducts = $this->getProducts($id);
            if ($purchaseProducts) :
                foreach ($purchaseProducts as  $purchaseProduct) :

                    $this->db->set('location_id',  $this->settings_lib->config('config', 'default_location'));
                    $this->db->set('reference', 'p');
                    $this->db->set('reference_id', $id);
                    $this->db->set('product_id', $purchaseProduct['product_id']);
                    $this->db->set('price', $purchaseProduct['price']);
                    $this->db->set('quantity', $purchaseProduct['quantity']);
                    $this->db->set('type', 'i');
                    $this->db->set('status', 1);


                    $stockFIlter = [
                        'reference_id' => $id,
                        'reference' => 'p',
                        'type' => 'i',
                        'product_id' => $purchaseProduct['product_id']
                    ];

                    $stocks = $this->stocks_model->getStock($stockFIlter);
                    if ($stocks) :
                        $this->db->set('updated_at', $this->currectDatetime);
                        $this->db->where('id', $stocks['id']);
                        $this->db->update('stocks');
                    else :
                        $this->db->set('created_at', $this->currectDatetime);
                        $this->db->insert('stocks');
                    endif;

                    $this->stocks_model->updateStock($purchaseProduct['product_id']);

                endforeach;
            endif;
        endif;
    }
}
