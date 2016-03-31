<?php

/**
 * Plugin Name: Remita OpenCart Payment Gateway
 * Plugin URI:  https://www.gtpay.net
 * Description: GTPay OpenCart Payment gateway allows you to accept payment on your OpenCart store via Visa Cards, Mastercards, Verve Cards, eTranzact, PocketMoni, Paga, Internet Banking, Bank Branch and Remita Account Transfer.
 * Author:      Oshadami Mike
 * Author URI:  http://www.oshadami.com
 * Version:     1.0
 */
class ControllerPaymentGtpay extends Controller {

    private $error = array();

    public function index() {
        $this->load->language('payment/gtpay');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('gtpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['entry_mercid'] = $this->language->get('entry_mercid');
        $data['entry_apikey'] = $this->language->get('entry_apikey');
        $data['entry_debug'] = $this->language->get('entry_debug');
        $data['entry_test'] = $this->language->get('entry_test');
        $data['entry_paymentoptions'] = $this->language->get('entry_paymentoptions');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_processed_status'] = $this->language->get('entry_processed_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['gtpay_mercid'])) {
            $data['error_mercid'] = $this->error['gtpay_mercid'];
        } else {
            $data['error_mercid'] = '';
        }

        if (isset($this->error['gtpay_apikey'])) {
            $data['error_apikey'] = $this->error['gtpay_apikey'];
        } else {
            $data['error_apikey'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/gtpay', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('payment/gtpay', 'token=' . $this->session->data['token'], 'SSL');

        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

        if (isset($this->request->post['gtpay_mercid'])) {
            $data['gtpay_mercid'] = $this->request->post['gtpay_mercid'];
        } else {
            $data['gtpay_mercid'] = $this->config->get('gtpay_mercid');
        }

        if (isset($this->request->post['gtpay_apikey'])) {
            $data['gtpay_apikey'] = $this->request->post['gtpay_apikey'];
        } else {
            $data['gtpay_apikey'] = $this->config->get('gtpay_apikey');
        }


        if (isset($this->request->post['gtpay_debug'])) {
            $data['gtpay_debug'] = $this->request->post['gtpay_debug'];
        } else {
            $data['gtpay_debug'] = $this->config->get('gtpay_debug');
        }

        if (isset($this->request->post['gtpay_pending_status_id'])) {
            $data['gtpay_pending_status_id'] = $this->request->post['gtpay_pending_status_id'];
        } else {
            $data['gtpay_pending_status_id'] = $this->config->get('gtpay_pending_status_id');
        }

        if (isset($this->request->post['gtpay_processed_status_id'])) {
            $data['gtpay_processed_status_id'] = $this->request->post['gtpay_processed_status_id'];
        } else {
            $data['gtpay_processed_status_id'] = $this->config->get('gtpay_processed_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['gtpay_geo_zone_id'])) {
            $data['gtpay_geo_zone_id'] = $this->request->post['gtpay_geo_zone_id'];
        } else {
            $data['gtpay_geo_zone_id'] = $this->config->get('gtpay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['gtpay_status'])) {
            $data['gtpay_status'] = $this->request->post['gtpay_status'];
        } else {
            $data['gtpay_status'] = $this->config->get('gtpay_status');
        }

        if (isset($this->request->post['gtpay_sort_order'])) {
            $data['gtpay_sort_order'] = $this->request->post['gtpay_sort_order'];
        } else {
            $data['gtpay_sort_order'] = $this->config->get('gtpay_sort_order');
        }
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('payment/gtpay.tpl', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'payment/gtpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['gtpay_mercid']) {
            $this->error['gtpay_mercid'] = $this->language->get('error_mercid');
        }

        if (!$this->request->post['gtpay_apikey']) {
            $this->error['gtpay_apikey'] = $this->language->get('error_apikey');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }

}

?>