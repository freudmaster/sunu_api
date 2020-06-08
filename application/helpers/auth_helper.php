<?php
/**
 * Provides authorized access to the system for a user, based on the provided
 * credentials, using a query to the database. If the authorization is
 * successful, a unique JSON Web Token is generated and stored in a cookie.
 * @param  $table - The database table to query.
 * @param $fields - An array of names for the fields to be requested.
 * @param $username_field - The name of the username field.
 * @param $password_field - The name of the password field.
 * @param $id_field - The name of the id field.
 * @param $username_value - The value of the username field.
 * @param $password_value - The value of the password field.
 * @param $service_name - The name of the service.
 * @param $cookie_name - The name of the cookie used to store the authorization
 *  token.
 * @return associative array
 */
function authorize($table, $fields, $num_souscripteur_value, $num_souscripteur_field, $id_field, $service_name, $cookie_name){
  // Load the appropriate helpers
  $ci =& get_instance(); $ci->load->helper('database'); $ci->load->helper('error_code');
  $id_field = str_replace("`", "", $id_field);
  $user = database_query(
    "SELECT ".$fields." FROM ".$table." WHERE ".$num_souscripteur_field."=?",
    "s", [$num_souscripteur_value]
  );
  if($user == false)
    return array(
      "code" => BAD_CREDENTIALS,
      "message"=>"No users with this souscription number."
    );
}

function get_infos_client($table, $num_souscripteur_value, $num_souscripteur_field){
  $ci =& get_instance(); $ci->load->helper('database'); $ci->load->helper('error_code');
  $infos = database_query(
    "SELECT * FROM ".$table." 
    WHERE ".$num_souscripteur_field."=?",
    "s", [$num_souscripteur_value]
  );
  if($infos == false){
    return array(
      "code" => BAD_CREDENTIALS,
      "message"=>"No infos user for this souscription number."
    );
  }else{
  $infos=$infos[0];
    $infos2 = database_query(
      "SELECT * FROM ".$table." 
      WHERE ".$num_souscripteur_field."=?",
      "s", [$num_souscripteur_value]
    );
    $i = 0;
    foreach($infos2 as $assure){
      $contrat = database_query(
        "SELECT * FROM police
        INNER JOIN produit on police.produit_id = produit.id
        INNER JOIN info_contrats on info_contrats.police_id = info_contrats.id
        WHERE  police.Clients_id =?",
        "s", [$assure['id']]
      );
      $infos2[json_encode($i)]["contrat"] = $contrat[0];
      $i++;
    }
    $infos["assures"] = $infos2;

    $id = $infos["id"];
    
    $infos3 = database_query(
      "SELECT * FROM police
      INNER JOIN produit on police.produit_id = produit.id
      INNER JOIN info_contrats on info_contrats.police_id = info_contrats.id
      WHERE  police.Clients_id =?",
      "s", [$id]
    );
    $infos["contrat"] = $infos3[0];
    return $infos;
  }
}

/**
 * Generates a unique JSON Web Token from the values provided.
 * @param $username_value - The user's unique username.
 * @param $id_value - The user's unique id.
 * @param $service_name - The name of the service.
 * @param $cookie_name - The name of the cookie used to store the authorization
 *  token.
 * @return void
 */
function generate_jwt_cookie($num_souscripteur_value, $id_value, $service_name, $cookie_name){
  $secret = parse_ini_file(__DIR__.'/../../config.ini')["secret"];

  $timestamp = date_timestamp_get(date_create());
  mt_srand(intval(substr($timestamp,-16,12)/substr(join(array_map(function ($n) { return sprintf('%03d', $n); }, unpack('C*', $secret))),0,2)));
  $stamp_validator = mt_rand();

  $token = array(
    "iat" => $timestamp,
    "chk" => $stamp_validator,
    "num_souscripteur" => $num_souscripteur_value,
    "id" => $id_value,
    "iss" => $service_name
  );
  $cookie = array (
    "id" => $id_value,
    "token" => jwt_encode($token, $secret)
  );
  // Change the first NULL below to set a domain, change the second NULL below
  // to make this only transmit over HTTPS
  setcookie($cookie_name, json_encode($cookie), 0, "/", NULL, NULL, true );
}

/**
 * Regenerates a unique JSON Web Token from the values provided. Will return a
 * message if no existing cookie is found.
 * @param $service_name - The name of the service.
 * @param $cookie_name - The name of the cookie used to store the authorization
 *  token.
 * @return associative array
 */
function regenerate_jwt_cookie($service_name, $cookie_name){
  // Load the appropriate helpers
  $ci =& get_instance(); $ci->load->helper('jwt'); $ci->load->helper('error_code');
  $secret = parse_ini_file(__DIR__.'/../../config.ini')["secret"];

  if(!isset($_COOKIE[$cookie_name]))
    return array(
      "code" => NO_COOKIE,
      "message" => "Token not found."
    );

  $cookie_contents = json_decode($_COOKIE[$cookie_name], true);
  $token = (array)jwt_decode($cookie_contents["token"], $secret);

  generate_jwt_cookie($token["num_souscripteur"], $token["id"], $service_name, $cookie_name);
  return array(
    "code" => SUCCESS,
    "message" => "Token regenerated successfully."
  );
}

/**
 * Checks the validity of a unique JSON Web Token.
 * @param $service_name - The name of the service.
 * @param $cookie_name - The name of the cookie used to store the authorization
 *  token.
 * @return true if the cookie is found and the JWT is valid, false otherwise
 */
function check_jwt_cookie($service_name, $cookie_name){
  // Load the appropriate helpers
  $ci =& get_instance(); $ci->load->helper('jwt');
  $secret = parse_ini_file(__DIR__.'/../../config.ini')["secret"];

  if(!isset($_COOKIE[$cookie_name]))
    return false;

  $cookie_contents = json_decode($_COOKIE[$cookie_name], true);
  $token = (array)jwt_decode($cookie_contents["token"], $secret);

  if($token["iss"] != $service_name)
    return false;

  if($token["id"] != $cookie_contents["id"])
    return false;

  mt_srand(intval(substr($token["iat"],-16,12)/substr(join(array_map(function ($n) { return sprintf('%03d', $n); }, unpack('C*', $secret))),0,2)));
  $stamp_validator = mt_rand();
  if($stamp_validator != $token["chk"])
    return false;

  return true;
}

/**
 * Gets the data stored in a unique JSON Web Token.
 * @param $cookie_name - The name of the cookie used to store the authorization
 *  token.
 * @return associative array
 */
function get_jwt_data($cookie_name){
  // Load the appropriate helpers
  $ci =& get_instance(); $ci->load->helper('jwt'); $ci->load->helper('error_code');
  $secret = parse_ini_file(__DIR__.'/../../config.ini')["secret"];

  if(!isset($_COOKIE[$cookie_name]))
    return array(
      "code" => NO_COOKIE,
      "message" => "Token not found."
    );

  $cookie_contents = json_decode($_COOKIE[$cookie_name], true);
  return (array)jwt_decode($cookie_contents["token"], $secret);
}
?>
