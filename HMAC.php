<?php

require_once('LoginFormat.php');

/*
 * Multi-role HMAC authentication library
 *
 * Loops through all possible login formats as defined
 * in the formats() function. A login format represents
 * a single user role.
 *
 * The HMAC concatenates the items you provide into a
 * canonical string joining them with a given delimiter, then
 * runs them through base64 and then sha256.
 *
 * To implement this in your DB schema, you need to have one
 * or more models in your database which have columns for public
 * and private keys.
 *
 * The public key is supplied via a given GET parameter, and the
 * private key is used as an element within the HMAC. The HMAC itself
 * is supplied by the client in an HTTP header with the request.
 *
 * To create a login format, you must add to the array returned
 * by HMAC::formats(). The key for the array item should be the
 * session variable you want to set on success. The parameters
 * you must set for each login format are as follows:
 *
 * model        The model you want to use
 * attribute    The member of your model you want to store in
 *              the session variable
 * header       The HTTP header which should be set to the incoming
 *              HMAC for this login format.
 * delimiter    The delimiter with which to concatenate the string
 *              together.
 * hmac         An array of items to concatenate into the canonical
 *              string. To use properties of your model, use the prefix
 *              '%{model}.'. Two examples of this could be
 *              '%{model}.private' or '%{model}.name'.
 * public       The column name to use for the model's public key.
 * getparam     The get parameter to use for the public key
 *
 * Example formats() function:
 *
 * return array(
 *   'user' => array(
 *     'public' => 'publickey',
 *     'getparam' => 'pk',
 *     'attribute' => 'id',
 *     'delimiter' => '-',
 *     'header' => 'HMAC-Auth',
 *     'model' => 'User',
 *     'hmac' => array(
 *        date('d/m/Y'),
 *        '%{model}.privatekey',
 *        Request::server('request_uri'),
 *     ),
 *   ),
 * );
 *
 */
class HMAC
{
  public static function authenticate()
  {
    foreach(static::formats() as $key => $value)
    {
      $loginFormat = new LoginFormat(array($key => $value));

      if($loginFormat->attempt())
        return TRUE;
    }

    return FALSE;
  }

  private static function formats()
  {
    return array(
      'user' => array(
        'public' => 'publickey',
        'getparam' => 'pk',
        'attribute' => 'id',
        'header' => 'HMAC-Auth',
        'delimiter' => '-',
        'model' => 'User',
        'hmac' => array(
          date('dmy'),
          '%{model}.private',
          Request::server('request_uri'),
        ),
      ),
    );
  }
}

