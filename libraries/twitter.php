<?php

/*
A Codeigniter libray to make requests to the Twitter API
Copyright (C) 2013  Christopher Pattle

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Twitter
{
    private $_consumerKey;
    private $_consumerSecret;
    private $_oAuthToken;
    private $_oAuthSecret;
    
    private $_oAuthNonce;
    private $_oAuthSignature;
    private $_oAuthSignatureMethod;
    private $_oAuthTimeStamp;
    private $_oAuthVersion;
    
    private $_httpMethod;
    private $_baseUrl;
    private $_requestFormat;
    
    private $_aParams;

    function __construct()
    {
        $this->config->load('twitter');

        $this->_consumerKey = $this->config->item('consumerKey');
        $this->_consumerSecret = $this->config->item('consumerSecret');
        $this->_oAuthToken = $this->config->item('oAuthToken');
        $this->_oAuthSecret = $this->config->item('oAuthSecret');
        $this->_oAuthNonce = $this->generateNonce();
        $this->_oAuthSignature = $this->config->item('oAuthSignature');;
        $this->_oAuthSignatureMethod = $this->config->item('oAuthSignatureMethod');
        $this->_oAuthTimeStamp = $this->config->item('oAuthTimeStamp');
        $this->_oAuthVersion = $this->config->item('oAuthVersion');
    }
    
    /*
     * generateNonce()
     * Generates a random alphanumeric string containing upper and lower case
     * characters of a set length
     * 
     * @author Chris Pattle
     * 
     * @param $numChars INT The number of characters the string should be
     * 
     * @return STRING Returns 
     */
    function generateNonce($numChars = 24)
    {
        //Create multidimentional array of random characters 
        $aChars[] = range('a', 'z');
        $aChars[] = range('A', 'Z');
        $aChars[] = range('0', '9');
        
        $nonce = '';
        
        //Create a loop until we reach our target number of characters
        while(strlen($nonce) < $numChars)
        {
            $num = rand(0, 2);
            
            if($num < 2)
                $num2 = rand(0, 24);
            else
                $num2 = rand(0,8);
            
            $nonce .= $aChars[$num][$num2];
        }
        
        return $nonce;
    }
    
    /*
     * encodeRequestParams()
     * Percent encodes the request parameters
     * 
     * @author Chris Pattle
     * 
     * @param $aRequestParams ARRAY An array of request parameters
     */
    function encodeRequestParams($aRequestParams)
    {
        $this->_aRequestParams = array();
        
        foreach($aRequestParams as $paramKey => $paramValue)
        {
            $this->_aRequestParams[rawurlencode($paramKey)] = rawurlencode($paramValue);
        }
    }
    
    /*
     * buildParams()
     * Builds an array of parameters needed for the request
     * 
     * @author Chris Pattle
     * 
     * @param $aRequestParams ARRAY An array of request parameters
     * 
     * @return ARRAY Returns the array of parameters
     */
    function buildParams()
    {
        $this->_aParams = array(
            rawurlencode('oauth_consumer_key') => rawurlencode($this->_consumerKey),
            rawurlencode('oauth_token') => rawurlencode($this->_oAuthToken),
            rawurlencode('oauth_nonce') => rawurlencode($this->_oAuthNonce),
            rawurlencode('oauth_signature_method') => rawurlencode($this->_oAuthSignatureMethod),
            rawurlencode('oauth_timestamp') => rawurlencode($this->_oAuthTimeStamp),
            rawurlencode('oauth_version') => rawurlencode($this->_oAuthVersion)
        );
        
        $this->_aParams[rawurlencode('oauth_signature')] = $this->getSignature();
    }
    
    /*
     * getSignature()
     * Gerenates a signature
     * 
     * @author Chris Pattle
     * @api https://dev.twitter.com/docs/auth/creating-signature
     * 
     * @return STRING Returns the oauth_signature
     */
    function getSignature()
    {
        $paramString = '';
        
        $aParams = array_merge($this->_aParams, $this->_aRequestParams);
        ksort($aParams);
        
        foreach($aParams as $paramKey => $paramValue)
        {
            if($paramString != '')
                $paramString .= '&';
            
            $paramString .= $paramKey . '=' . $paramValue;
        }
        
        $signatureBaseString = rawurlencode(strtoupper($this->_httpMethod)) . '&' . rawurlencode($this->_baseUrl) . '&' . rawurlencode($paramString);

        $signingKey = rawurlencode($this->_consumerSecret) . '&' . rawurlencode($this->_oAuthSecret);
        
        $signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, TRUE));

        return $signature;
    }
    
    /*
     * buildParamString()
     * Build the a string of request parameters.  
     * This is either sent via POST or append to the end of the URL for GET's
     * 
     * @author Chris Pattle
     * 
     * @return STRING Returns the string of parameters
     */
    function buildParamString()
    {
        $paramString = '';
        
        foreach($this->_aRequestParams as $paramKey => $paramValue)
        {
            if($paramString != '')
                $paramString .= '&';
            
            $paramString .= $paramKey . '=' . $paramValue;
        }
        
        return $paramString;
    }
    
    /*
     * buildAuthHeader()
     * Create the authorization header needed to let Twitter know who the 
     * request is coming from
     * 
     * @author Chris Pattle
     * 
     * @return STRING Returns the authorization header
     */
    function buildAuthHeader()
    {
        $authHeader = 'OAuth ';
        
        ksort($this->_aParams);
        
        $paramCount = 1;
        
        foreach($this->_aParams as $paramKey => $paramValue)
        {
            $authHeader .= rawurlencode($paramKey) . '="' . rawurlencode($paramValue) . '"';
            
            if($paramCount != count($this->_aParams))
                $authHeader .= ', ';
            
            $paramCount++;
        }
        
        return $authHeader;
    }
    
    /*
     * getRequestFormat()
     * Get the format for the request from the base URL
     * This should be either xml or json
     * 
     * @author Chris Pattle
     * 
     * @return STRING Returns the last part of the base URL which should be the format
     */
    function getRequestFormat()
    {
        $aURLParts = explode('.', $this->_baseUrl);
        
        return end($aURLParts);
    }
    
    /*
     * request()
     * Makes a request using the Twitter API
     * 
     * @author Chris Pattle
     * 
     * @param $httpMethod STRING POST/GET etc
     * @param $baseUrl STRING The base URL for the call
     * @param $aRequestParams ARRAY An array of parameters specific to this request
     * 
     * @return Returns the response from Twitter.  Either XML or JSON
     */
    function request($httpMethod, $baseUrl, $aRequestParams = array())
    {
        //Set the httpMethod and base URL for this request
        $this->_httpMethod = $httpMethod;
        $this->_baseUrl = $baseUrl;
        $this->_requestFormat = $this->getRequestFormat();
        $this->_requestParams = $this->encodeRequestParams($aRequestParams);
    
        //Create an array of parameters
        $this->buildParams();
        
        //Create the parameter string we will need to send
        $paramString = $this->buildParamString();
        
        //Build the authorization header so Twitter know its us
        $authHeader = $this->buildAuthHeader();
        
        //If we are using GET we need to append the parameters to the URL
        if($httpMethod == 'GET')
            $this->_baseUrl .= '?' . $paramString;
        
        //Use cURL to POST the data needed to send the SMS
        $curl = curl_init($this->_baseUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: ' . $authHeader));
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        
        //If we are using POST we need to configure cURL to post the parameters
        if($this->_httpMethod == 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $paramString);
        }
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEVALUE, 30);

        $oReturn = curl_exec($curl);

        if($oReturn === FALSE)
        {
            curl_close($curl);
            return FALSE;
        }
        else
        {
            return $oReturn;
        }
    }
}

/* End of file twitter.php */
/* Location: ./application/libraries/twitter.php */
