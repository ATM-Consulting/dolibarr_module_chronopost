<?php

class Chronopost {

	function __construct($server_ftp_adress='', $login_ftp='', $pass_ftp, $port_ftp='') {

		$this->server_ftp_adress = $server_ftp_adress;
		$this->login_ftp = $login_ftp;
		$this->pass_ftp = $pass_ftp;
		$this->port_ftp = $port_ftp;

	}

	function connect() {

		$conn = ftp_connect($this->server_ftp_adress, empty($this->port_ftp) ? 21 : $this->port_ftp);

		if(!$conn) {
			$this->log_error('Erreur connection au serveur');
			return 0;
		}

		return $conn;

	}

	function login(&$conn) {

		$res = ftp_login($conn, $this->login_ftp, $this->pass_ftp);
		if($res) return 1;
		else {
			$this->log_error('Erreur login');
			return 0;
		}

	}

	function log_error($err) {

		global $conf;

		$error = 'Le '.date('d-m-Y').' à '.date('H:i:s').' : '.$err;
		$f = fopen($conf->chronopost->multidir_output[$conf->entity].'/error.log', 'a+');
		fwrite($f, $error."\n");

	}
	/**
	 * Crée le fichier à envoyer dans le répertoire /document/chronopost/files
	 */
	function generate_file_to_send($filename, &$expedition) {
		global $conf;

		$file_dir = $conf->chronopost->multidir_output[$conf->entity].'/files/';
		$fname = $file_dir.$filename;

		$f = fopen($fname, 'w+');
		$this->write_file($f, $expedition);
		fclose($f);

		return $f;

	}

	function write_file(&$f, &$expedition) {

		$TAddress = $this->get_used_address($expedition);

		fputcsv($f, array(
			substr(str_replace('-', '', $expedition->ref), 0, 17)
			,substr($expedition->thirdparty->nom, 0, 35)
			,substr($TAddress['address1'], 0, 35)
			//,substr($TAddress['address2'], 0, 35)
				,substr('1', 0, 35)
			,substr($TAddress['address3'], 0, 35)
			,substr($TAddress['zip'], 0, 9)
			,substr($TAddress['town'], 0, 35)
			,substr($TAddress['country_code_iso'], 0, 3)
			,substr(dol_string_nohtmltag($expedition->note_public), 0, 70)
			,substr('', 0, 1) // TODO Filler
			,substr($TAddress['phone'], 0, 35)
			,substr(!empty($expedition->date_delivery) ? date('Ymd', $expedition->date_delivery) : date('Ymd'), 0, 8)
			,substr($TAddress['address4'], 0, 35)
		)
		,';;');

	}

	private function get_used_address(&$expedition) {

		global $db;

		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/contact/class/contact.class.php');

		// Récupération de la commande de départ pour checker si elle a un contact livraison commande
		$commande = new Commande($db);
		if($commande->fetch($expedition->origin_id)) {

			$TContact = $commande->liste_contact(-1, 'external', 1, 'SHIPPING');

			if(!empty($TContact)) { // Adresse du contact livraison

				$c = new Contact($db);
				$c->fetch($TContact[0]);
				$TAddress = $this->get_array_address($c);

			} else { //Adresse du tiers

				$expedition->fetch_thirdparty();
				$soc = &$expedition->thirdparty;
				$TAddress = $this->get_array_address($soc);

			}

		} else return -1;

		return $TAddress;

	}

	private function get_array_address(&$obj) {

		global $db;

		dol_include_once('/core/class/ccountry.class.php');

		$TAddress = array();

		$pays = new Ccountry($db);
		$pays->fetch(empty($obj->country_id) ? 1 : $obj->country_id);

		$TAddressTMP = explode("\n", $obj->address);
		$TAddress['address1'] = $TAddressTMP[0];
		if(!empty($TAddressTMP[1])) $TAddress['address2'] = $TAddressTMP[1];
		if(!empty($TAddressTMP[2])) $TAddress['address3'] = $TAddressTMP[2];
		if(!empty($TAddressTMP[3])) $TAddress['address4'] = $TAddressTMP[3];
		$TAddress['zip'] = $obj->zip;
		$TAddress['town'] = $obj->town;
		$TAddress['country_code_iso'] = $pays->code;
		$TAddress['phone'] = (get_class($obj) === 'Societe') ? $obj->phone : $obj->phone_pro;

		return $TAddress;

	}

}
