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

class Twitterexamples extends CI_Controller
{  
    function __construct()
    {
        parent::__construct();
        
        $this->load->library('twitter');
    }
    
    /*
     * sendTweet()
     * An example of how to send a tweet using the twitter library
     * 
     * @author Chris Pattle
     * @api https://dev.twitter.com/docs/api/1/post/statuses/update
     */
    function sendTweet()
    {
        $response = $this->twitter->request('POST', 'https://api.twitter.com/1.1/statuses/update.json', array('include_entities' => 'true', 'status' => 'I want cookies'));
    }
    
    /*
     * sendTweet()
     * An example of how to send a tweet using the twitter library
     * 
     * @author Chris Pattle
     * @api https://dev.twitter.com/docs/api/1/get/statuses/user_timeline
     */
    function getTweets()
    {
        $response = $this->twitter->request('GET', 'https://api.twitter.com/1.1/statuses/user_timeline.json', array('include_entities' => 'true'));
    }

    /*
     * sendDirectMessage()
     * An example of how to send a direct message using the twitter library
     * 
     * @author Chris Pattle
     * @api https://dev.twitter.com/docs/api/1/post/direct_messages/new
     */
    function sendDirectMessage()
    {
        $response = $this->twitter->request('POST', 'https://api.twitter.com/1.1/direct_messages/new.json', array('include_entities' => 'true', 'screen_name' => 'chrispattle', 'text' => 'Can you give me cookies?'));
    }
    
    /*
     * getDirectMessages()
     * An example of how to get direct messages using the twitter library
     * 
     * @author Chris Pattle
     * @api https://dev.twitter.com/docs/api/1/get/direct_messages
     */
    function getDirectMessages()
    {        
        $response = $this->twitter->request('GET', 'https://api.twitter.com/1.1/direct_messages.json', array('count' => '5'));
    }
}

/* End of file twitterexamples.php */
/* Location: ./application/controllers/twitterexamples.php */
