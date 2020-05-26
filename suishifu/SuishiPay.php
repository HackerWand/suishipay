<?php
/*
 * Author: hackerwand
 * Email: heipi@hackerwand.com
 * Date: Tue May 26 2020
 */
require_once 'phpqrcode.php';

class SuishiPay {
    const baseUrl = 'http://suishipay.com:30000';
    const payUrl = 'http://suishipay.com';

    public $merchantNo = null;
    public $requestKey = null;
    public $notifyUrl = null;

    /**
     * @param merchantNo 商户号
     * @param requestKey 请求密钥
     * @param notifyUrl 回调通知地址
     */
    public function __construct ($merchantNo, $requestKey, $notifyUrl) {
        $this->merchantNo = $merchantNo;
        $this->requestKey = $requestKey;
        $this->notifyUrl = $notifyUrl;
    }

    /**
     * 统一下单接口
     * @param orderId 商户订单号
     * @param transAmount 交易金额 单位分
     * @param payChannelCode 支付渠道编码 ALIPAY - 支付宝支付, WECHAT - 微信支付
     */
    public function pay ($orderId, $transAmount, $payChannelCode) {
        $url = self::baseUrl . '/payment/trans/create';
        $orderParam = [
            'merchantNo' => $this->merchantNo,
            'orderId' => $orderId,
            'transAmount' => $transAmount,
            'payChannelCode' => $payChannelCode,
            'notifyUrl' => $this->notifyUrl
        ];
        $sign = $this->signParam($orderParam, $this->requestKey);
        $orderParam['sign'] = $sign;

        $this->checkParam($orderParam);

        $result = $this->curl($url, $orderParam);
        if ($result['msg'] !== 'success' || $result['result'] !== '0000') {
            throw new \Exception($result['msg'], $result['result']);
        }

        // transSeqId 中心生成的交易流水号
        // qrCode 二维码url, 用户将该url转成二维码提供给用户扫码完成支付
        // realAmount 实际支付金额, 单位为分
        // expiresTime 交易过期时间, 单位分钟
        require_once 'phpqrcode.php';
        $result['data']['qrCode'] = $this->getQRcode($result['data']['qrCode']);
        return $result['data'];
    }

    /**
     * H5支付
     * @param orderId 商户订单号
     * @param transAmount 支付金额
     * @param payType 支付方式 0微信 1支付宝
     * @param returnUrl 支付完成返回url
     */
    public function h5pay ($orderId, $transAmount, $payType, $returnUrl) {
        $url = 'http://suishipay.com/cashier/init';
        $payParam = [
            'merchantNo' => $this->merchantNo,
            'orderId' => $orderId,
            'transAmount' => $transAmount,
            'payType' => $payType,
            'notifyUrl' => $this->notifyUrl,
            'returnUrl' => $returnUrl
        ];

        $sign = $this->signParam($payParam, $this->requestKey);
        $payParam['sign'] = $sign;

        $this->checkParam($payParam);

        $result = $this->curl($url, $payParam);
        if ($result['msg'] !== 'success' || $result['result'] !== '0000') {
            throw new \Exception($result['msg'], $result['result']);
        }

        return self::payUrl . $result['data']['payUrl'];
    }

    /**
     * 回调处理
     * @param orderId 订单号
     * @param payTime 付款时间
     * @param sign 签名
     * @param transAmount 实付金额
     * @param transSeqId 交易流水号
     */
    public function notify () {
        $notifyData = json_decode(file_get_contents("php://input"), true);
        $this->signCheck($notifyData);
        return $notifyData;
    }

    /**
     * 页面返回处理
     * @param out_trade_no transSeqId 交易流水号
     */
    public function return () {
        if (!isset($_GET['out_trade_no']) || empty($_GET['out_trade_no'])) {
            throw new \Exception("参数错误", 500);
        }
        return $this->query($_GET['out_trade_no']);
    }

    /**
     * 订单查询
     * @param transSeqId 交易流水号
     */
    public function query ($transSeqId) {
        $url = self::baseUrl . '/payment/trans/query';
        $queryParam = [
            'merchantNo' => $this->merchantNo,
            'transSeqId' => $transSeqId
        ];
        $sign = $this->signParam($queryParam, $this->requestKey);
        $queryParam['sign'] = $sign;

        $result = $this->curl($url, $queryParam);
        if ($result['msg'] !== 'success' || $result['result'] !== '0000') {
            throw new \Exception($result['msg'], $result['result']);
        }

        // transSeqId 中心生成的交易流水号
        // orderId 商户订单号
        // transAmount 交易金额, 单位为分
        // realAmount 实际支付金额, 单位为分
        // payChannelCode 支付渠道编码
        // payStatus 1-支付成功, 0-未完成支付
        // payTime 支付时间"
        return $result['data'];
    }

    public function checkParam ($param) {
        if (!isset($param['merchantNo']) || empty($param['merchantNo'])) {
            throw new \Exception("商户号不能为空", 500);
        }
        if (!isset($param['notifyUrl']) || empty($param['notifyUrl'])) {
            throw new \Exception("回调地址不能为空", 500);
        }
        if (!isset($param['sign']) || empty($param['sign'])) {
            throw new \Exception("签名错误", 500);
        }
        if (isset($param['payChannelCode']) && !in_array($param['payChannelCode'], ['ALIPAY', 'WECHAT'])) {
            throw new \Exception("支付方式错误", 500);
        }
    }
    
    public function curl ($url, $data){
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        $jsonResponse = json_decode($response, true);
        if (!$jsonResponse) {
            throw new Exception("异常数据返回: " . $response, 500);
        }

        return $jsonResponse;
    }
    
    public function signCheck ($param) {
        if (!$param) {
            throw new \Exception("验签失败, 参数错误", 500);
        }

        if (!isset($param['sign']) || empty($param['sign'])) {
            throw new \Exception("验签失败, 签名为空", 500);
        }
        
        $sign = $this->signParam($param, $this->requestKey);

        if ($sign !== $param['sign']) {
            throw new \Exception("验签失败, 非法签名", 500);
        }
    }

    public function signParam ($param, $key){
        ksort($param);
        $buff = "";
        foreach ($param as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        $prestr = $buff . '&key=' . $key;
        return strtoupper(md5($prestr));
    }

    public function getQRcode($url){
        ob_start();
        $returnData = QRcode::pngString($url,false, "H", 3, 1);
        $imageString = base64_encode(ob_get_contents());
        ob_end_clean();
        $str = "data:image/png;base64," . $imageString;
        return $str;
    }
}    
