<?php

class Chronopost {
	
	function __construct($server_ftp_adress, $login_ftp, $pass_ftp, $port_ftp) {
		
		$this->server_ftp_adress = $server_ftp_adress;
		$this->login_ftp = $login_ftp;
		$this->pass_ftp = $pass_ftp;
		$this->port_ftp = $port_ftp;
		
		$this->TErrors = array();
		
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
		
		$this->TErrors[] = 'Le '.date('d-m-Y').' à '.date('H:i:s').' : '.$err;
		
		// TODO écrire les erreurs dans un fichier de log ou dans une table
		
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

		fputcsv($f, array(
			''
			,substr($expedition->thirdparty->nom, 0, 35)
		)
		,';');
		
	}
	
}
