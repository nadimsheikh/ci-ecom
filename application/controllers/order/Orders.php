<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Orders extends REST_Controller
{

    private $data = [];
    private $error = [];
    private $filter = [];
    private $validations = [];
    private $datetime_format;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('order/orders_model');
        $this->load->library('form_validation');
        $this->datetime_format = $this->settings_lib->config('config', 'default_date_time_format');
        $this->form_validation->set_error_delimiters('', '');
    }

    private function getData($object)
    {
        $result = [];
        if ($object) :
            $result = [
                'id' => $object['id'],
                'invoice_no' => $object['invoice_no'],
                'order_type_id' => $object['order_type_id'],
                'order_type' => $object['order_type'],
                'customer_id' => $object['customer_id'],
                'address_id' => $object['address_id'],
                'order_status_id' => $object['order_status_id'],
                'order_status' => $object['order_status'],
                'name' => $object['name'],
                'email' => $object['email'],
                'contact' => $object['contact'],
                'person_name' => $object['person_name'],
                'person_contact' => $object['person_contact'],
                'country_id' => $object['country_id'],
                'country' => $object['country'],
                'zone_id' => $object['zone_id'],
                'zone' => $object['zone'],
                'city_id' => $object['city_id'],
                'city' => $object['city'],
                'postcode' => $object['postcode'],
                'address' => $object['address'],
                'comment' => $object['comment'],
                'total_tax' => $this->settings_lib->number_format($object['total_tax']),
                'total' => $this->settings_lib->number_format($object['total']),
                'status' => $object['status'],
                'status_text' => $object['status'] ? $this->lang->line('text_enable') : $this->lang->line('text_disable'),
                'created_at' => date($this->datetime_format, strtotime($object['created_at'])),
                'updated_at' => date($this->datetime_format, strtotime($object['updated_at'])),
            ];
        endif;
        return $result;
    }

    private function getProductData($objects)
    {
        $data = [];
        if ($objects) :
            foreach ($objects as $key => $object) :
                $data[] = [
                    'product_id' => $object['product_id'],
                    'product' => $object['product'],
                    'product_image' => base_url($object['product_image']),
                    'quantity' => $this->settings_lib->number_format($object['quantity']),
                    'price' => $this->settings_lib->number_format($object['price']),
                    'tax' => $this->settings_lib->number_format($object['tax']),
                    'total' => $this->settings_lib->number_format($object['total']),
                ];
            endforeach;
        endif;

        return $data;
    }

    private function getTotalsData($objects)
    {
        $data = [];
        foreach ($objects as $object) :
            $data[] = [
                'code' => $object['code'],
                'title' => $object['title'],
                'value' => $this->settings_lib->number_format($object['value']),
            ];
        endforeach;
        return $data;
    }

    public function index_post()
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $list = $this->orders_model->getTables();

        $result = [];
        if ($list) :
            foreach ($list as $object) :
                $result[] = $this->getData($object);
            endforeach;
        else :
            $this->data['status'] = false;
        endif;

        $this->data['recordsTotal'] = $this->orders_model->countAll();
        $this->data['recordsFiltered'] = $this->orders_model->countFiltered();
        $this->data['data'] = $result;
        $this->data['message'] = $this->lang->line('text_loading');

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function delete_get($id)
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $object = $this->orders_model->deleteById($id);

        $result = [];
        if ($object) :
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_country'));
            $result = $object;
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function deleteAll_post()
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $list = json2arr($this->post('list'));

        $result = [];
        if ($list) :
            foreach ($list as $id) :
                $object = $this->orders_model->deleteById($id);
            endforeach;
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_country'));
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function detail_post()
    {
        $this->data = [];
        $this->data['data'] = [];

        $id = $this->post('id');

        $object = $this->orders_model->getById($id);

        $result = [];
        if ($object) :
            $products = $this->orders_model->getProducts($object['id']);
            $productsData = $this->getProductData($products);
            $totals = $this->orders_model->getTotals($object['id']);
            $totalsData = $this->getTotalsData($totals);
            $result = $this->getData($object);
            $result['products'] = $productsData;
            $result['totals'] = $totalsData;
            $this->data['status'] = true;
            $this->data['message'] = $this->lang->line('text_loading');
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_not_found'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function clear_history_get($id)
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $object = $this->orders_model->clearHistories($id);

        $result = [];
        if ($object) :
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_country'));
            $result = $object;
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function history_post()
    {
        $this->data = [];
        $this->data['data'] = [];

        $id = $this->post('id');

        $object = $this->orders_model->getHistories($id);

        if ($object) :
            $this->data['data'] = $object;
            $this->data['status'] = true;
            $this->data['message'] = $this->lang->line('text_loading');
        else :
            $this->data['data'] = [];
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_not_found'), $this->lang->line('text_country'));
        endif;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function save_post()
    {
        $this->validation();

        $this->data = [];
        $this->data['data'] = [];

        $object = $this->orders_model->save();

        $result = [];
        if ($object) :
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_save'), $this->lang->line('text_country'));
            $result = $object;
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_save'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function validation()
    {
        $this->validations = array(
            'order_type_id' => 'required',
            'customer_id' => 'required',
            'address_id' => 'required',
        );
        $this->_validation();
    }

    private function _validation()
    {
        $this->data = [];
        foreach ($this->validations as $key => $validation) :
            $field = '';
            if ($this->lang->line('text_' . $key)) :
                $field = $this->lang->line('text_' . $key);
            else :
                $field = humanize($key);
            endif;
            $this->form_validation->set_rules($key, $field, $validation);
        endforeach;

        if ($this->form_validation->run() == false) :
            foreach ($this->validations as $key => $validation) :
                if (form_error($key, '', '')) :
                    $this->error[] = array(
                        'id' => $key,
                        'text' => form_error($key, '', ''),
                    );
                endif;
            endforeach;

            $this->data['status'] = false;
            $this->data['message'] = $this->lang->line('error_validation');
            $this->data['result'] = $this->error;
            echo json_encode($this->data);
            exit;
        endif;
    }

    public function print_get($id)
    {
        $this->data = [];
        $this->data['data'] = [];

        $object = $this->orders_model->getById($id);

        $result = [];
        if ($object) :
            $products = $this->orders_model->getProducts($object['id']);
            $productsData = $this->getProductData($products);
            $totals = $this->orders_model->getTotals($object['id']);
            $totalsData = $this->getTotalsData($totals);
            $result = $this->getData($object);
            $result['products'] = $productsData;
            $result['totals'] = $totalsData;
        endif;

        $this->data = $result;
        // print_r($this->data);
        // exit;

        $this->load->view('order/print', $this->data);
    }
}
