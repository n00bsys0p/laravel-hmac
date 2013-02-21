<?php

/*
 * Each login format can attempt one HMAC schema.
 */
class LoginFormat
{
  private $session_data;
  private $header;
  private $model;
  private $hmac_proto;
  private $delimiter;
  private $column_public;
  private $getparam;

  public function __construct($format)
  {
    $keys = array_keys($format);
    $session_parameter = array_shift($keys);

    $this->header = $format[$session_parameter]['header'];
    $this->model = $format[$session_parameter]['model'];
    $this->session_data = array(
      $session_parameter => $format[$session_parameter]['attribute']
    );
    $this->getparam = $format[$session_parameter]['getparam'];
    $this->delimiter = $format[$session_parameter]['delimiter'];
    $this->column_public = $format[$session_parameter]['public'];
    $this->hmac_proto = $format[$session_parameter]['hmac'];
  }

  public function attempt()
  {
    $hdrs = apache_request_headers();

    if(Input::has($this->getparam))
    {
      $public_key = Input::get($this->getparam);
      $auth_model = new $this->model;
      $auth_model = $auth_model->where($this->column_public, '=', Input::get($this->getparam))->first();

      $hmac = $this->generateHMAC($auth_model);

      // If we are successful, populate the session
      if(isset($hdrs[$this->header]) && $hdrs[$this->header] == $hmac)
      {
          $this->populateSession();
          return TRUE;
      }
    }

    // Default to return false
    return FALSE;
  }

  private function populateSession()
  {
    $session_param = array_keys($this->session_data);
    $session_param = array_shift($session_param);
    $session_value = $this->session_data[$session_param];

    Session::put($session_param, $session_value);
  }

  private function generateHMAC($model)
  {
    // If no model found, give up here
    if(!is_object($model))
      return '';
    // First go and set up the HMAC array with the correct values
    foreach($this->hmac_proto as $key => $value)
    {
      if(preg_match('/^\%\{model\}/', $value))
      {
        $property = str_replace('%{model}.', '', $value);
        $this->hmac_proto[$key] = $model->$property;
      }
    }

    $hmac = join($this->hmac_proto, $this->delimiter);
    $hmac = base64_encode($hmac);
    return hash('sha256', $hmac);    
  }
}

