<?php

use Restserver\Libraries\REST_Controller;

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Customer_wishlists extends REST_Controller
{

    private $data = [];
    private $error = [];
    private $filter = [];
    private $validations = [];
    private $datetime_format;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('customer/customer_wishlists_model');
        $this->load->model('product/products_model');
        $this->load->library('form_validation');
        $this->datetime_format = $this->settings_lib->config('config', 'default_date_time_format');
        $this->form_validation->set_error_delimiters('', '');
    }

    private function getData($object)
    {
        $result = [];
        if ($object):
            if ($object['special_price']) :
                $tax = $this->products_model->getTotalTax($object['id'], $object['special_price']);
                $special_price = $this->settings_lib->number_format($object['special_price']);
                $final_price = $this->settings_lib->number_format($object['special_price'] + $tax);
                $discount = $this->settings_lib->discount($object['price'] + $tax, $object['special_price'] + $tax);
            else :
                $discount = '';
                $special_price = false;
                $tax = $this->products_model->getTotalTax($object['id'], $object['price']);
                $final_price = $this->settings_lib->number_format($object['price'] + $tax);
            endif;
            $result = [
                'id' => $object['id'],
                'customer_id' => $object['customer_id'],
                'customer_name' => $object['customer_name'],
                'product_id' => $object['product_id'],                
                'price' => $this->settings_lib->number_format($object['price']),
                'special_price' => $special_price,
                'tax' => $this->settings_lib->number_format($tax),
                'final_price' => $final_price,
                'discount' => $discount,
                'product_name' => $object['product_name'],
                'product_image' => $object['product_image'] ? base_url($object['product_image']) : '',
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

        $this->data['status'] = true;

        if ($this->post('draw')):
            $this->data['draw'] = $this->post('draw');
        else:
            $this->data['draw'] = 0;
        endif;

        $this->data['data'] = [];

        $list = $this->customer_wishlists_model->getTables();

        $result = [];
        if ($list):
            foreach ($list as $object):
                $result[] = $this->getData($object);
            endforeach;
        else:
            $this->data['status'] = false;
        endif;

        $this->data['recordsTotal'] = $this->customer_wishlists_model->countAll();
        $this->data['recordsFiltered'] = $this->customer_wishlists_model->countFiltered();
        $this->data['data'] = $result;
        $this->data['message'] = $this->lang->line('text_loading');

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function delete_get($id)
    {
        $this->data = [];
        $this->data['data'] = [];
        $this->data['status'] = true;

        $object = $this->customer_wishlists_model->deleteById($id);

        $result = [];
        if ($object):
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_delete'), $this->lang->line('text_customer_group'));
            $result = $object;
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_delete'), $this->lang->line('text_customer_group'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function detail_post()
    {
        $this->data = [];
        $this->data['data'] = [];
        $id = $this->post('id');
        $object = $this->customer_wishlists_model->getById($id);

        $result = [];
        if ($object):
            $result = $this->getData($object);
            $this->data['status'] = true;
            $this->data['message'] = $this->lang->line('text_loading');
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_not_found'), $this->lang->line('text_customer_group'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function save_post()
    {
        $this->validation();

        $this->data = [];
        $this->data['data'] = [];

        $object = $this->customer_wishlists_model->save();

        $result = [];
        if ($object):
            $this->data['status'] = true;
            $this->data['message'] = sprintf($this->lang->line('success_save'), $this->lang->line('text_customer_group'));
            $result = $object;
        else:
            $this->data['status'] = false;
            $this->data['error'] = sprintf($this->lang->line('error_save'), $this->lang->line('text_customer_group'));
        endif;

        $this->data['data'] = $result;

        $this->set_response($this->data, REST_Controller::HTTP_OK);
    }

    public function validate_wishlist($field_value)
    {
        if ($this->customer_wishlists_model->checkWishlist($field_value)):
            $this->form_validation->set_message('validate_wishlist', sprintf($this->lang->line('error_already_exists'), '{field}'));
            return false;
        else:
            return true;
        endif;
    }

    public function validation()
    {
        $this->validations = array(
            'customer_id' => 'required',
            'product_id' => 'required|callback_validate_wishlist',
        );
        $this->_validation();
    }

    private function _validation()
    {
        $this->data = [];
        foreach ($this->validations as $key => $validation):
            $field = '';
            if ($this->lang->line('text_' . $key)):
                $field = $this->lang->line('text_' . $key);
            else:
                $field = humanize($key);
            endif;
            $this->form_validation->set_rules($key, $field, $validation);
        endforeach;

        if ($this->form_validation->run() == false):
            foreach ($this->validations as $key => $validation):
                if (form_error($key, '', '')):
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
