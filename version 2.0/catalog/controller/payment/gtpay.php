<?php

/**
 * Plugin Name: GTPay OpenCart Payment Gateway
 * Description: GTPay OpenCart Payment gateway allows you to accept payment on your OpenCart store via Visa Cards, Mastercards, Verve Cards, eTranzact, PocketMoni, Paga, Internet Banking, Bank Branch and Remita Account Transfer.
 * Author:      Oshadami Mike
 * Version:     1.0
 */
class ControllerPaymentGtpay extends Controller {

    public function index() {
        $this->language->load('payment/gtpay');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_id = $this->session->data['order_id'];
        if ($order_info) {
            $data['gtpay_mercid'] = trim($this->config->get('gtpay_mercid'));
            $data['gtpay_apikey'] = trim($this->config->get('gtpay_apikey'));
            $data['orderid'] = $this->session->data['order_id'];
            $data['returnurl'] = $this->url->link('payment/gtpay/callback', '', 'SSL');
            $data['total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $data['totalAmount'] = html_entity_decode($data['total']);
            $data['payerName'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
            $data['payerEmail'] = $order_info['email'];
            $data['payerPhone'] = html_entity_decode($order_info['telephone'], ENT_QUOTES, 'UTF-8');
            $data['customerId'] = $order_info['customer_id'];
            $data['button_confirm'] = $this->language->get('button_confirm');
            $totalAmountinKobo = $data['total'] * 100;
            $currencyCode = 566;
            $uniqueRef = uniqid();
            $gtpayorderid = $uniqueRef . '_' . $data['orderid'];
            $hash_string = $data['gtpay_mercid'] . $gtpayorderid . $totalAmountinKobo . $currencyCode . $data['customerId'] . $data['returnurl'] . $data['gtpay_apikey'];
            $data['hash'] = hash('sha512', $hash_string);
            $gtpay_args_array = array();
            $gtpay_args = array(
                'gtpay_mert_id' => $data['gtpay_mercid'],
                'gtpay_tranx_curr' => $currencyCode,
                'gtpay_tranx_amt' => $totalAmountinKobo,
                'gtpay_cust_id' => $data['customerId'],
                'gtpay_cust_name' => $data['payerName'],
                'gtpay_tranx_noti_url' => $data['returnurl'],
                'gtpay_hash' => $data['hash'],
                'gtpay_echo_data' => $data['orderid'],
                'gtpay_tranx_id' => $gtpayorderid
            );

            foreach ($gtpay_args as $key => $value) {
                $gtpay_args_array[] = "<input type='hidden' name='" . $key . "' value='" . $value . "'/>";
            }

            $data['gateway_url'] = 'https://ibank.gtbank.com/GTPay/Tranx.aspx';
            $data['gtpay_hidden_args'] = implode('', $gtpay_args_array);
        }

        //1 - Pending Status
        $message = 'Payment Status : Pending';
        $this->model_checkout_order->addOrderHistory($order_id, 1, $message, false);
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/gtpay.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/gtpay.tpl', $data);
        } else {
            return $this->load->view('default/template/payment/gtpay.tpl', $data);
        }
    }

    function gtpay_transaction_details($amount, $transId) {
        $merchantId = trim($this->config->get('gtpay_mercid'));
        $hashkey = trim($this->config->get('gtpay_apikey'));
        $hash_string = $merchantId . $transId . $hashkey;
        $hash = hash('sha512', $hash_string);
        $query_url = 'https://ibank.gtbank.com/GTPayService/gettransactionstatus.json?';
        $url = $query_url . 'mertid=' . $merchantId . '&amount=' . $amount . '&tranxid=' . $transId . '&hash=' . $hash;
        $result = file_get_contents($url);
        $response = json_decode($result, true);
        return $response;
    }

    function updatePaymentStatus($order_id, $response_code, $response_reason, $transRef) {
        switch ($response_code) {
            case "00":
                $message = 'Payment Status : - Successful - GTPay Transaction Reference: ' . $transRef;
                $this->model_checkout_order->addOrderHistory($order_id, trim($this->config->get('gtpay_processed_status_id')), $message, true);
                break;
            default:
                //process a failed transaction
                $message = 'Payment Status : - Not Successful - Reason: ' . $response_reason . ' - GTPay Transaction Reference: ' . $transRef;
                //1 - Pending Status
                $this->model_checkout_order->addOrderHistory($order_id, 1, $message, true);
                break;
        }
    }

    public function callback() {
        $paymentId = $_POST["gtpay_tranx_id"];
        $transAmount = $_POST["gtpay_tranx_amt"];
        $order_id = $_POST["gtpay_echo_data"];
        $response = $this->gtpay_transaction_details($transAmount, $paymentId);
        $data['order_id'] = $order_id;
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $data['response_code'] = $response['ResponseCode'];
        $data['transRef'] = $paymentId;
        $data['response_reason'] = $response['ResponseDescription'];
        $this->updatePaymentStatus($order_id, $data['response_code'], $data['response_reason'], $data['transRef']);
        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['payment_method']);
            unset($this->session->data['payment_methods']);
            unset($this->session->data['guest']);
            unset($this->session->data['comment']);
            unset($this->session->data['order_id']);
            unset($this->session->data['coupon']);
            unset($this->session->data['reward']);
            unset($this->session->data['voucher']);
            unset($this->session->data['vouchers']);
        }

        $this->language->load('checkout/success');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/cart'),
            'text' => $this->language->get('text_basket'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/checkout', '', 'SSL'),
            'text' => $this->language->get('text_checkout'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/success'),
            'text' => $this->language->get('text_success'),
            'separator' => $this->language->get('text_separator')
        );

        $data['heading_title'] = $this->language->get('heading_title');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['fail_continue'] = $this->url->link('checkout/checkout', '', 'SSL');
        $data['continue'] = $this->url->link('account/order/info', 'order_id=' . $data['order_id'], 'SSL');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['heading_title'] = $this->load->controller('common/heading_title');
        $data['footer'] = $this->load->controller('common/footer');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/gtpay_success.tpl')) {
            return $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/gtpay_success.tpl', $data));
        } else {
            return $this->response->setOutput($this->load->view('default/template/payment/gtpay_success.tpl', $data));
        }
    }

}

?>