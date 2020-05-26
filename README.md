# 随时付 支付接口

**提供任意php网站的支付接口对接服务**

**联系邮箱: heipi@hackerwand.com**

**费用：400/次起**

## 二维码支付示例
```php
<?php
require '../suishifu/SuishiPay.php';
require './config.php';

$suishiPay = new SuishiPay($config['merchantNo'], $config['requestKey'], $config['notifyUrl']);
try {
    $pay = $suishiPay->pay(time(), 1, 'ALIPAY');
    var_dump($pay);
} catch (\Exception $ex) {
    var_dump($ex);
}
?>

<img src="<?php echo $pay['qrCode']; ?>" />
```

## 其他接口代码预览
```php
<?php
    /**
     * @param merchantNo 商户号
     * @param requestKey 请求密钥
     * @param notifyUrl 回调通知地址
     */
    public function __construct ($merchantNo, $requestKey, $notifyUrl) {}

    /**
     * 统一下单接口
     * @param orderId 商户订单号
     * @param transAmount 交易金额 单位分
     * @param payChannelCode 支付渠道编码 ALIPAY - 支付宝支付, WECHAT - 微信支付
     */
    public function pay ($orderId, $transAmount, $payChannelCode) {}

    /**
     * H5支付
     * @param orderId 商户订单号
     * @param transAmount 支付金额
     * @param payType 支付方式 0微信 1支付宝
     * @param returnUrl 支付完成返回url
     */
    public function h5pay ($orderId, $transAmount, $payType, $returnUrl) {}

    /**
     * 回调处理
     * @param orderId 订单号
     * @param payTime 付款时间
     * @param sign 签名
     * @param transAmount 实付金额
     * @param transSeqId 交易流水号
     */
    public function notify () {}

    /**
     * 页面返回处理
     * @param out_trade_no transSeqId 交易流水号
     */
    public function return () {}

    /**
     * 订单查询
     * @param transSeqId 交易流水号
     */
    public function query ($transSeqId) {}
```

**更多详情请参考example目录**