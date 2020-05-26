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
    $pay = $suishiPay->pay(time(), 1, 'ALIPAY');
    var_dump($pay);
} catch (\Exception $ex) {
    var_dump($ex);
}
?>

<img src="<?php echo $pay['qrCode']; ?>" />