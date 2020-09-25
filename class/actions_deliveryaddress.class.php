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
 * \file    class/actions_deliveryaddress.class.php
 * \ingroup deliveryaddress
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsDeliveryAddress
 */
class ActionsDeliveryAddress
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
	function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $user, $conf, $mysoc;

		$outputlangs = $parameters['outputlangs'];
		$outputlangs->load('deliveryaddress@deliveryaddress');
		$txt = '';

		if (
			(	in_array('ordercard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERCARD))
			|| 	(in_array('propalcard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_HIDE_ADDRESS_ON_PROPALCARD))
			||	(in_array('invoicecard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_HIDE_ADDRESS_ON_INVOICECARD))
			|| 	(in_array('ordersuppliercard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD))
			)
		{
			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');
			$wysiwyg = !empty($conf->fckeditor->enabled);
			$TContacts = array();
			if(method_exists($object, 'liste_contact')) $TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$txt.= $this->addConctactToString($c, $outputlangs, $wysiwyg = false);

					break;
				}
			}

			if (!empty($conf->global->DELIVERYADDRESS_SHOW_INFO_REPONSABLE_RECEPTION))
			{
				$TContacts = array();
				if(method_exists($object, 'liste_contact')) $TContacts = $object->liste_contact(-1, 'internal');
				foreach($TContacts as $c)
				{
					// Responsable réception commande fournisseur
					if($c['code'] == 'SHIPPING')
					{
						$u = new User($db);
						$u->fetch($c['id']);

						if (empty($object->note_public)) $txt .= "\n";

						$title = $outputlangs->trans("ReceiptContact")." :\n";
						$name = dolGetFirstLastname($u->firstname, $u->lastname)."\n";
						if($wysiwyg) $name = '<strong>'.$name.'</strong>';

						$phone = $outputlangs->transnoentities("Phone").': ';
						if (!empty($u->office_phone)) $phone.= $u->office_phone;
						if (!empty($u->office_phone) && !empty($u->user_mobile)) $phone.= ' / '.$u->user_mobile;
						else if (!empty($u->user_mobile)) $phone .= $u->user_mobile;

						if (!empty($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES)){
							switch ($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES) {
								case 'returnChar1':
									$sep="\r\n";
									break;
								case 'returnChar2':
									$sep="\r\n\r\n";
									break;
								case 'dash':
									$sep="\r\n-----------\r\n";
									break;
							}
						} else {
							$sep="\r\n";
						}
						$end = !empty($object->note_public) ? $sep : "";

						$txt.= $title . $name . $phone . $end;

						break;
					}
				}
			}
		}

		if (
			!empty($parameters['DELIVERYADDRESS_DISPLAY_BILLED']) // IN case of custom PDF
			||  (in_array('expeditioncard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_DISPLAY_BILLED_ON_EXPEDITIONCARD))
			|| 	(in_array('deliverycard',explode(':',$parameters['context'])) && empty($conf->global->DELIVERYADDRESS_DISPLAY_BILLED_ON_DELIVERYCARD))
		) {

			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');
			$wysiwyg = !empty($conf->fckeditor->enabled);


			$TContacts = array();

			if (empty($object->commande)){
				$object->commande = new Commande($db);

				if ($object->element == "delivery"){
					// We get the shipment that is the origin of delivery receipt
					$expedition = new Expedition($db);
					$result = $expedition->fetch($object->origin_id);
					$TContacts = $expedition->liste_contact();

					if ($expedition->origin == 'commande')
					{
						$object->commande->fetch($expedition->origin_id);
					}
				}
				else if ($object->element == "shipping" && $object->origin == 'commande') {
					$object->commande->fetch($object->origin_id);
				}
			}

			if (!empty($object->commande) && method_exists($object->commande, 'liste_contact')) $TContacts = $object->commande->liste_contact();

			foreach ($TContacts as $c) {
				if ($c['code'] == 'BILLING') {
					$txt.= $this->addConctactToString($c, $outputlangs, $wysiwyg);
					break;
				}
			}
		}

		// Gestion des sauts de lignes si la note était en HTML de base
		if (!isset($object->note_public_original)) {
			$object->note_public_original = $object->note_public;
		}
		if($wysiwyg) $object->note_public = dol_nl2br($txt).$object->note_public;
		else $object->note_public = $txt.$object->note_public;
	}

	/**
	 * @param array $c a contact item from commonobject->liste_contact()
	 * @param Translate $outputlangs
	 * @param bool $wysiwyg
	 * @return string
	 */
	function addConctactToString($c, $outputlangs, $wysiwyg = false){

		global $db, $conf, $mysoc;

		$contact = new Contact($db);
		$contact->fetch($c['id']);
		$soc = new Societe($db);
		$soc->fetch($c['socid']);

		if($c['code'] == 'SHIPPING') {
			$title = $outputlangs->trans("DeliveryAddress") . " :\n";
		}

		if ($c['code'] == 'BILLING') {
			$title = $outputlangs->trans("BillingAddress") . " :\n";
		}

		$socname = !empty($contact->socname) ? $contact->socname . "\n" : "";
		if ($wysiwyg) $socname = '<strong>' . $socname . '</strong>';
		$maconfTVA = $conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS;
		$maconfTargetDetails = $conf->global->MAIN_PDF_ADDALSOTARGETDETAILS;
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = true;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = false;
		$address = pdf_build_address($outputlangs, $mysoc, $soc, $contact, 1, 'target');
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = $maconfTVA;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = $maconfTargetDetails;

		$phone = '';
		if (!empty($conf->global->DELIVERYADDRESS_SHOW_PHONE)) {
			if (!empty($contact->phone_pro) || !empty($contact->phone_mobile)) $phone .= ($address ? "\n" : '') . $outputlangs->transnoentities("Phone") . ": ";
			if (!empty($contact->phone_pro)) $phone .= $outputlangs->convToOutputCharset($contact->phone_pro);
			if (!empty($contact->phone_pro) && !empty($contact->phone_mobile)) $phone .= " / ";
			if (!empty($contact->phone_mobile)) $phone .= $outputlangs->convToOutputCharset($contact->phone_mobile);
		}
		if (!empty($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES)) {
			switch ($conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES) {
				case 'returnChar1':
					$sep = "\r\n";
					break;
				case 'returnChar2':
					$sep = "\r\n\r\n";
					break;
				case 'dash':
					$sep = "\r\n-----------\r\n";
					break;
			}
		} else {
			$sep = "\r\n";
		}

		$end = !empty($object->note_public) ? $sep : "";

		return  $title . $socname . $address . $phone . $end;
	}

	/**
	 * @param array        $parameters
	 * @param CommonObject $object
	 * @param string       $action
	 * @param HookManager  $hookmanager
	 */
	function afterPDFCreation($parameters, &$object, &$action, $hookmanager)
	{
		// clean up the object if it was altered by beforePDFCreation
		$object = $parameters['object'];
		if (isset($object->note_public_original)) {
			$object->note_public = $object->note_public_original;
		}
		return 0;
	}
}
