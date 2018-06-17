<?php
namespace Sal\Api;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Api Wrapper
 */
class Client {

  const DEBUG = false;
  const SAL_API_URL = 'http://api.socialandloyal.xyz';

  const SAL_API_PORT = 80;
  const API_VERSION = '1';
  
  public function __construct($public_key='', $private_key='', $client_name='') {
    $this->public_key = $public_key;
    $this->private_key = $private_key;
    $this->client_name = $client_name;
  }

  private function gen_headers(){
    $timestamp = microtime(true);
    $hash = hash_hmac('sha1', $this->public_key.$timestamp, $this->private_key);
    $headers = array(
      'X-MICROTIME: '.$timestamp,
      'X-PUBLIC: '.$this->public_key,
      'X-HASH: '.$hash
    );

    return $headers;
  }
  
  private function _post($uri, $params){
    $headers = $this->gen_headers();
    $post_data = urldecode(http_build_query($params));

    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, count($post_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
    
  }

  private function _put($uri, $params){
    $headers = $this->gen_headers();
    $post_data = urldecode(http_build_query($params));

    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, count($post_data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
    
  }

  private function _get($uri, $params){
    $headers = $this->gen_headers();
    $post_data = urldecode(http_build_query($params));
    if ($post_data) {
      $uri = $uri.'?'.$post_data;
    }
    $ch = curl_init($uri);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    return json_decode($response);
  }
    
  protected function bcrypt($password, $cost)
  {
      $chars = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      $salt = sprintf('$2a$%02d$', $cost);
      for ($i = 0; $i < 22;
           $i++) {
          $salt .= $chars[rand(0, 63)];
      }
      return crypt($password, $salt);
  }

  public function register_user($external_id, $name, $surname, $email, $password = null, $birthday='', $location='', $gender=''){
    
    // *************************
    // POST /users
    // *************************
    $params_data = array(
                'client_name' => $this->client_name,
                'external_id' => $external_id,
                'name'        => $name,
                'surname'     => $surname,
                'email'       => $email,
                'birthday'    => $birthday,
                'location'    => $location,
                'gender'      => $gender
            );
            
    if ($password != null) {
        $params_data['password'] = $this->bcrypt($password, 10);
    }
                
    return $this->_post(Client::SAL_API_URL.'/users', $params_data);


  }
  
  public function get_user($external_id) {
    $params_data = array(
      'client_name' => $this->client_name,
    );
    
    $response = $this->_get(Client::SAL_API_URL."/users/external_id/$external_id" , $params_data);
    return $response->data;
  }

  public function get_user_movements($external_id) {
      $params_data = array(
        'client_name' => $this->client_name,
      );
      $response = $this->_get(Client::SAL_API_URL."/users/external_id/$external_id/movements" , $params_data);
      return $response->data;
  }

  public function get_by_email($email) {
      $params_data = array(
        'client_name' => $this->client_name,
      );
      $response = $this->_get(Client::SAL_API_URL."/users/email/$email" , $params_data);
      return $response->data;
  }

  public function get_by_user_id($user_id) {
      $params_data = array(
        'client_name' => $this->client_name,
      );
      $response = $this->_get(Client::SAL_API_URL."/users/$user_id" , $params_data);
      return $response->data;
  }


  public function update_external_id_by_user_id($user_id, $external_id) {
      $params_data = array(
        'client_name' => $this->client_name,
        'external_id' => $external_id
      );
      $response = $this->_put(Client::SAL_API_URL."/users/user_id/$user_id", $params_data);
      return $response['data'];
  }

  public function add_user_movements($external_id, $transaction_id, $description, $points, $price = 0) {
      $params_data = array(
                  'client_name' => $this->client_name,
                  'transaction_id' => $transaction_id,
                  'txt'            => $description,
                  'points'         => $points,
                  'price'          => $price
              );
      $response = $this->_post(Client::SAL_API_URL."/users/$external_id/external_transactions" , $params_data);
      return $response;
  }


  public function get_user_token($external_id){
    $params_data = array(
        'client_name' => $this->client_name,
        'external_id' => $external_id,
    );
    $response = $this->_get(Client::SAL_API_URL."/widgetToken" , $params_data);
    return $response->data;
  }

}