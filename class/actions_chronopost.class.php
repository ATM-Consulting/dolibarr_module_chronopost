<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_chronopost.class.php
 * \ingroup chronopost
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionschronopost
 */
class Actionschronopost
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $conf, $user, $langs;

		$actionATM = GETPOST('actionATM');

		if (in_array('expeditioncard', explode(':', $parameters['context'])))
		{
			// On préremplit le code relais de l'expédition avec celui présent sur la commande si existant
			$originid = GETPOST('origin_id');
			if($action === 'create' && !empty($originid)) {
				require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
				$com = new Commande($db);
				$com->fetch($originid);
				$code_relais_colis = $com->array_options['options_code_relais_colis'];
				if(!empty($code_relais_colis)) $_POST['options_code_relais_colis'] = $code_relais_colis;
			}
			
			if(!empty($user->rights->chronopost->sendfile) && $actionATM === 'generate_and_send_chronopost_file' && $object->statut == 1) { // Validé

				dol_include_once('/chronopost/class/chronopost.class.php');

				$chronopost = new Chronopost($conf->global->CHRONOPOST_FTP_HOST, $conf->global->CHRONOPOST_FTP_LOGIN, $conf->global->CHRONOPOST_FTP_PASSWORD, $conf->global->CHRONOPOST_FTP_PORT);

				if(empty($conf->global->CHRONOPOST_ONLY_IN_DOCUMENTS)) {
					$conn = $chronopost->connect();
					$res = $chronopost->login($conn);
				}

				if($res > 0 || !empty($conf->global->CHRONOPOST_ONLY_IN_DOCUMENTS)) {

					//$res = $chronopost->generate_file_to_send('expedition_'.$object->id.'_'.date('YmdHis').'.csv', $object);
					if(empty($object->array_options['options_code_relais_colis'])) {
						$fname = 'Bons de livraison C.txt';
						$res = $chronopost->generate_file_to_send($fname, $object);
					}
					else {
						$fname = 'Bons de livraison R.txt';
						$res = $chronopost->generate_file_to_send($fname, $object, true);
					}
					
					if(!empty($res)) {
						chmod(DOL_DATA_ROOT . '/chronopost/files/'.$fname, 0777);
						setEventMessage($langs->trans('ChronopostFileGenerated'));
					}
				}

			}

		}

		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user, $db, $bc;

		$langs->load('chronopost@chronopost');

		if (in_array('expeditioncard', explode(':', $parameters['context']))) {

			if(!empty($user->rights->chronopost->sendfile) && !empty($object->statut)) {

				?>
				<script type="text/javascript">

					$(document).ready(function() {

						$('div.tabsAction').prepend('<a class="butAction" href="<?php print $_SERVER['PHP_SELF'].'?id='.GETPOST('id').'&actionATM=generate_and_send_chronopost_file'; ?>"><?php print $langs->trans('ChronopostGenerateAndSendFile'); ?></a>');

					});

				</script>
				<?php

			}

		}

		// Always OK
		return 0;
	}

}