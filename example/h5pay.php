<?php
/*
 * Author: hackerwand
 * Email: heipi@hackerwand.com
 * Date: Tue May 26 2020
 */
require '../suishifu/SuishiPay.php';
require './config.php';

$suishiPay = new SuishiPay($config['merchantNo'], $config['requestKey'], $config['notifyUrl']);
try {
    $payUrl = $suishiPay->h5pay(time(), 1, 1, $config['returnUrl']);
    var_dump($payUrl);
} catch (\Exception $ex) {
    var_dump($ex);
}
?>

<a href="<?php echo $payUrl; ?>">点击支付</a>