<?php
/**
 *  Website: https://mudew.com/
 *  Author: Mudew
 *  Version: 0.0.1
 */

class Ocr
{
    private $_captcha = '';
    public function foreUpload($filename)
    {
        $this->_captcha = $filename;

        if (!file_exists($this->_captcha)) {
            die('{"success": -1}');
        }

        $url = 'http://ocr.shouji.sogou.com/v2/ocr/json';
        $data = array('pic' => new CURLFile(realpath($this->_captcha)));
        //curl设置
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = array(
            'multipart/form-data;boundary=5cb7c439-7b71-4c07-b997-7f74b01958b7',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1"); //代理服务器地址
        //curl_setopt($ch, CURLOPT_PROXYPORT, "8888"); //代理服务器端口
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //开启后从浏览器输出，curl_exec()方法没有返回值
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
