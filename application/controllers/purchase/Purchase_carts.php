<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Purchase_carts extends REST_Controller
{

    private $data = [];
    private $error = [];
    private $filter = [];
    private $validations = [];
    private $datetime_format;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_carts_model');
        $this->load->library('form_validation');
        $this->datetime_format = $this->settings_lib->config('config', 'default_date_time_format');
        $this->form_validation->set_error_delimiters('', '');
    }

    private function getData($object)
    {
        $result = [];
        if ($object) :
            $total = $this->settings_lib->number_format(($object['price'] * $object['quantity']) + $object['tax']);
            $result = [
                'id' => $object['id'],
                'token' => $object['token'],
                'product_id' => $object['product_id'],
                'product' => $object['product'],
                'product_image' => $object['product_image'] ? base_url($object['product_image']) : '',
                'price' => $this->settings_lib->number_format($object['price']),
                'quantity' => $this->settings_lib->number_format($object['quantity']),
                'tax' => $this->settings_lib->number_format($object['tax']),
                'total' => $total,
                'status' => $object['status'],
                'status_text' => $object['status'] ? $this->lang->line('text_enable') : $this->lang->line('text_disable'),
                'created_at' => date($this->datetime_format, strtotime($object['created_at'])),
                'updated_at' => date($this->datetime_format, strtotime($object['updated_at'])),
            ];
        endif;
        return $result;
    }

    public function index_post()
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $list = $this->purchase_carts_model->getTables();

        $result = [];
        if ($list) :
            foreach ($list as $object) :
                $result[] = $this->getData($object);
            endforeach;
        else :
            $this->data['status'] = false;
        endif;

        
        $this->data['recordsTotal'] = $this->purchase_carts_model->countAll();
        $this->data['recordsFiltered'] = $this->purchase_carts_model->countFiltered();
        $this->data['data'] = $result;        
        $this->data['message'] = $this->lang->line('text_loading');


        $totals = [];
        $taxes = $this->purchase_carts_model->getTaxTotal();
        $total = 0;

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

        $this->data['totals'] = [];

        foreach ($totals as $totalValue) :
            if ($totalValue['title'] == 'Total') :
                $total = $totalValue['value'];
            endif;
            $this->data['totals'][] = [
                'text' => $totalValue['title'],
                'value' => $this->settings_lib->number_format($totalValue['value'])
            ];
        endforeach;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function delete_get($id)
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $object = $this->purchase_carts_model->deleteById($id);

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
                $object = $this->purchase_carts_model->deleteById($id);
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

        $object = $this->purchase_carts_model->getById($id);

        $result = [];
        if ($object) :
            $result = $this->getData($object);
            $this->data['status'] = true;
            $this->data['message'] = $this->lang->line('text_loading');
        else :
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_not_found'), $this->lang->line('text_country'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function save_post()
    {
        $this->validation();

        $this->data = [];
        $this->data['data'] = [];

        $object = $this->purchase_carts_model->save();

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

    public function validate_product($field_value)
    {
        if ($this->purchase_carts_model->checkProduct($field_value)) :
            $this->form_validation->set_message('validate_product', sprintf($this->lang->line('error_already_exists'), '{field}'));
            return false;
        else :
            return true;
        endif;
    }

    public function validation()
    {
        $this->validations = array(
            'token' => 'required',
            'product_id' => 'required|callback_validate_product',
            'price' => 'required',
            'quantity' => 'required',
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
            if (validation_errors()) {
                $this->data['message'] = validation_errors();
            } else {
                $this->data['message'] = $this->lang->line('error_validation');
            }
            $this->data['result'] = $this->error;
            echo json_encode($this->data);
            exit;
        endif;
    }
}
