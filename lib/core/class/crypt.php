<?php
namespace core;

/**
 * Cryptage réversible
 *
 * @author Daniel Gomes
 */
class crypt {

    /**
     * Attributs
     */
	private $firstKey	= 'Lk5Uz3slx3BrAghS1aaW5AYgWZRV0tIX5eI0yPchFz4=';
	private $secondKey 	= 'EZ44mFi3TlAey1b2w4Y7lVDuqO+SRxGXsa7nctnr/JmMrA2vN6EJhrvdVZbxaQs5jpSe34X3ejFK/o9+Y5c83w==';


    /**
     * Constructeur
     */
	public function __construct($key = '')
	{
		if (!empty($key)) {
			$this->firstKey = $key;
		}
	}


	/**
     * Chiffrement
     *
     * @param   string      $str    Chaine à chiffrer
     */
	public function encrypt($str)
	{
		$first_key = base64_decode($this->firstKey);
		$second_key = base64_decode($this->secondKey);

		$method = "aes-256-cbc";
		$iv_length = openssl_cipher_iv_length($method);
		$iv = openssl_random_pseudo_bytes($iv_length);

		$first_encrypted = openssl_encrypt($str, $method, $first_key, OPENSSL_RAW_DATA, $iv);
		$second_encrypted = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);

		$output = base64_encode($iv.$second_encrypted.$first_encrypted);
		return $output;
	}


	/**
     * Déchiffrement
     *
     * @param   string      $str    Chaine à déchiffrer
     */
	public function decrypt($str)
		{
		$first_key = base64_decode($this->firstKey);
		$second_key = base64_decode($this->secondKey);
		$mix = base64_decode($str);

		$method = "aes-256-cbc";
		$iv_length = openssl_cipher_iv_length($method);

		$iv = substr($mix,0,$iv_length);
		$second_encrypted = substr($mix,$iv_length,64);
		$first_encrypted = substr($mix,$iv_length+64);

		$data = openssl_decrypt($first_encrypted, $method, $first_key, OPENSSL_RAW_DATA, $iv);
		$second_encrypted_new = hash_hmac('sha3-512', $first_encrypted, $second_key, TRUE);

		if (hash_equals($second_encrypted, $second_encrypted_new))
		return $data;
	}
}
