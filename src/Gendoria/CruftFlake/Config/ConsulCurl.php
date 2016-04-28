<?php

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gendoria\CruftFlake\Config;

/**
 * Description of ConsulCurlRequestor
 *
 * @author Tomasz Struczyński <tomasz.struczynski@isobar.com>
 */
class ConsulCurl
{
    private $consulBaseUrl;
    
    private $apiPrefix = "/v1";
    
    public function __construct($consulBaseUrl, $apiPrefix = "/v1")
    {
        $this->consulBaseUrl = $consulBaseUrl;
        $this->apiPrefix = $apiPrefix;
    }
    
    public function performGetRequest($url)
    {
        $curlUrl = $this->consulBaseUrl . $this->apiPrefix . $url;
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $returnData = json_decode($result, true);
        return $returnData;
    }
    
    public function performPutRequest($url, $payload = null)
    {
        $curlUrl = $this->consulBaseUrl . $this->apiPrefix . $url;
        $ch = curl_init($curlUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload))
        );
        $result = curl_exec($ch);
        $returnData = json_decode($result, true);
        return $returnData;
    }
}