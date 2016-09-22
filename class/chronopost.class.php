<?php

class Chronopost {
	
	function __construct($server_ftp_adress, $login_ftp, $pass_ftp) {
		
		$this->server_ftp_adress = $server_ftp_adress;
		$this->login_ftp = $login_ftp;
		$this->pass_ftp = $pass_ftp;
		
		$thi->TErrors = array();
		
	}
	
	function connect() {
		
		$conn = ftp_connect($this->server_ftp_adress);
		
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
	
	function generate_file_to_send() {
		
		
		
	}
	
}
