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

require_once __DIR__ . '/../backport/v19/core/class/commonhookactions.class.php';

/**
 * Class ActionsDeliveryAddress
 */
class ActionsDeliveryAddress extends \deliveryaddress\RetroCompatCommonHookActions
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
		$wysiwyg = isModEnabled('fckeditor');

		if (
			(	in_array('ordercard',explode(':',$parameters['context'])) && !getDolGlobalString('DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERCARD'))
			|| 	(in_array('propalcard',explode(':',$parameters['context'])) && !getDolGlobalString('DELIVERYADDRESS_HIDE_ADDRESS_ON_PROPALCARD'))
			||	(in_array('invoicecard',explode(':',$parameters['context'])) && !getDolGlobalString('DELIVERYADDRESS_HIDE_ADDRESS_ON_INVOICECARD'))
			|| 	(in_array('ordersuppliercard',explode(':',$parameters['context'])) && !getDolGlobalString('DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD'))
			)
		{
			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');
			$TContacts = array();
			if(method_exists($object, 'liste_contact')) $TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					if (in_array('ordersuppliercard',explode(':',$parameters['context'])) && ($action == 'confirm_approve' || $action == 'confirm_approve2')) break;
					$txt.= $this->addConctactToString($object, $c, $outputlangs, $wysiwyg);

					break;
				}
			}

			if (getDolGlobalString('DELIVERYADDRESS_SHOW_INFO_REPONSABLE_RECEPTION'))
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

						if (getDolGlobalString('DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES')){
							switch (getDolGlobalString('DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES')) {
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
			||  (in_array('expeditioncard',explode(':',$parameters['context'])) && getDolGlobalString('DELIVERYADDRESS_DISPLAY_BILLED_ON_EXPEDITIONCARD'))
			|| 	(in_array('deliverycard',explode(':',$parameters['context'])) && getDolGlobalString('DELIVERYADDRESS_DISPLAY_BILLED_ON_DELIVERYCARD'))
		) {

			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');


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
					$txt.= $this->addConctactToString($object, $c, $outputlangs, $wysiwyg);
					break;
				}
			}
		}

		if(!empty($txt)){
			// Gestion des sauts de lignes si la note était en HTML de base
			if (!isset($object->note_public_original)) {
				$object->note_public_original = $object->note_public;
			}
			if($wysiwyg) $object->note_public = dol_nl2br($txt).$object->note_public;
			else $object->note_public = $txt.$object->note_public;
		}

		return 0;

	}

	/**
	 * @param commonObject $object
	 * @param array $c a contact item from commonobject->liste_contact()
	 * @param Translate $outputlangs
	 * @param bool $wysiwyg
	 * @return string
	 */
	function addConctactToString($object, $c, $outputlangs, $wysiwyg = false){

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
		if(getDolGlobalString('MAIN_TVAINTRA_NOT_IN_ADDRESS')) $maconfTVA = getDolGlobalString('MAIN_TVAINTRA_NOT_IN_ADDRESS');
		else $maconfTVA = '';
		if(getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS')) $maconfTargetDetails = getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS');
		else $maconfTargetDetails = '';
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = true;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = false;

		$address = $this->buildCustomAddress($outputlangs, $mysoc, $soc, $contact);
		$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = $maconfTVA;
		$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = $maconfTargetDetails;

		$phone = '';
		if (getDolGlobalString('DELIVERYADDRESS_SHOW_PHONE')) {
			if (!empty($contact->phone_pro) || !empty($contact->phone_mobile)) $phone .= ($address ? "\n" : '') . $outputlangs->transnoentities("Phone") . ": ";			if (!empty($contact->phone_pro)) $phone .= $outputlangs->convToOutputCharset($contact->phone_pro);
			if (!empty($contact->phone_pro) && !empty($contact->phone_mobile)) $phone .= " / ";
			if (!empty($contact->phone_mobile)) $phone .= $outputlangs->convToOutputCharset($contact->phone_mobile);
		}
		$email = '';
        if (getDolGlobalString('DELIVERYADDRESS_SHOW_EMAIL')) {
			if (!empty($contact->email)) $email = ($phone || $address ? "\n" : '') . $outputlangs->transnoentities("Email") . ": " . $outputlangs->convToOutputCharset($contact->email);
		}
		if (getDolGlobalString('DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES')) {
			switch (getDolGlobalString('DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES')) {
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

        return  $title . $socname . $address . $phone . $email . $end;
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
		// note: be carefull object is &$object ! please note that others modules could update object ...
		$obj = $parameters['object'];
		if (isset($obj->note_public_original)) {
			$object->note_public = $obj->note_public_original;
		}
		return 0;
	}


	/**
	 * @param Societe $company
	 * @return void
	 */
	private function getStateIfNeeded(Societe $company): void
	{
		if (!empty($company->state_id) && empty($company->state)) {
			$company->state = getState($company->state_id);
		}
	}

	/**
	 * @param Translate $outputlangs
	 * @param Contact $contact
	 * @param Societe $company
	 * @return string
	 */
	private function getFormattedAddress(Translate $outputlangs, Contact $contact, Societe $company): string
	{
		$stringaddress = $outputlangs->convToOutputCharset($contact->getFullName($outputlangs, 1));

		if (!empty($contact->address)) {
			$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->convToOutputCharset(dol_format_address($contact)) . "\n";
		} else {
			$companytouseforaddress = $company;

			if ($contact->socid > 0 && $contact->socid != $company->id) {
				$contact->fetch_thirdparty();
				$companytouseforaddress = $contact->thirdparty;
			}

			$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->convToOutputCharset(dol_format_address($companytouseforaddress)) . "\n";
		}

		return $stringaddress;
	}

	/**
	 * @param Translate $outputlangs
	 * @param Societe $sourcecompany
	 * @param Societe $targetcompany
	 * @param Contact $targetcontact
	 * @param string $stringaddress
	 * @return void
	 */
	private function addCountryInfo(Translate $outputlangs, Societe $sourcecompany, Societe $targetcompany, Contact $targetcontact, string &$stringaddress): void
	{
		$countryCode = $targetcontact->country_code ?: $targetcompany->country_code;
		if ($countryCode && $countryCode != $sourcecompany->country_code) {
			$stringaddress .= (($stringaddress && !getDolGlobalString('MAIN_PDF_REMOVE_BREAK_BEFORE_COUNTRY')) ? "\n" : '') . $outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country" . $countryCode));
		}
	}

	/**
	 * @param Translate $outputlangs
	 * @param Contact $contact
	 * @param string $stringaddress
	 * @return void
	 */
	private function addTargetDetails(Translate $outputlangs, Contact $contact, string &$stringaddress): void
	{
		if (getDolGlobalString('MAIN_PDF_ADDALSOTARGETDETAILS')) {
			if (!empty($contact->phone_pro) || !empty($contact->phone_mobile)) {
				$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("Phone") . ": ";
				$stringaddress .= !empty($contact->phone_pro) ? $outputlangs->convToOutputCharset($contact->phone_pro) : '';
				$stringaddress .= (!empty($contact->phone_pro) && !empty($contact->phone_mobile)) ? " / " : '';
				$stringaddress .= !empty($contact->phone_mobile) ? $outputlangs->convToOutputCharset($contact->phone_mobile) : '';
			}
			if ($contact->fax) {
				$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("Fax") . ": " . $outputlangs->convToOutputCharset($contact->fax);
			}
			if ($contact->email) {
				$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("Email") . ": " . $outputlangs->convToOutputCharset($contact->email);
			}
			if ($contact->url) {
				$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("Web") . ": " . $outputlangs->convToOutputCharset($contact->url);
			}
		}
	}

	/**
	 * @param Translate $outputlangs
	 * @param Societe $company
	 * @param string $stringaddress
	 * @return void
	 */
	private function addVATAndLegalInfo(Translate $outputlangs, Societe $company, string &$stringaddress): void
	{
		if (!getDolGlobalString('MAIN_TVAINTRA_NOT_IN_ADDRESS') && !empty($company->tva_intra)) {
			$stringaddress .= ($stringaddress ? "\n" : '') . $outputlangs->transnoentities("VATIntraShort") . ": " . $outputlangs->convToOutputCharset($company->tva_intra);
		}
		if (getDolGlobalString('MAIN_LEGALFORM_IN_ADDRESS') && !empty($company->forme_juridique_code)) {
			$tmp = getFormeJuridiqueLabel($company->forme_juridique_code);
			$stringaddress .= ($stringaddress ? "\n" : '') . $tmp;
		}
	}

	/**
	 * @param Societe $company
	 * @param string $stringaddress
	 * @return void
	 */
	private function addPublicNote(Societe $company, string &$stringaddress): void
	{
		if (getDolGlobalString('MAIN_PUBLIC_NOTE_IN_ADDRESS') && !empty($company->note_public)) {
			$stringaddress .= ($stringaddress ? "\n" : '') . dol_string_nohtmltag($company->note_public);
		}
	}

	/**
	 *    Return a string with full address formatted for output on PDF documents
	 *
	 * @param Translate $outputlangs Output langs object
	 * @param Societe $sourcecompany Source company object
	 * @param Societe $targetcompany Target company object
	 * @param Contact $targetcontact Target contact object
	 * @return string String with full address or -1 if KO
	 */
	private function buildCustomAddress(Translate $outputlangs, Societe $sourcecompany, Societe $targetcompany, Contact $targetcontact): string
	{
		$this->getStateIfNeeded($sourcecompany);
		$this->getStateIfNeeded($targetcompany);

		$stringaddress = $this->getFormattedAddress($outputlangs, $targetcontact, $targetcompany);
		$this->addCountryInfo($outputlangs, $sourcecompany, $targetcompany, $targetcontact, $stringaddress);
		$this->addTargetDetails($outputlangs, $targetcontact, $stringaddress);
		$this->addVATAndLegalInfo($outputlangs, $targetcompany, $stringaddress);
		$this->addPublicNote($targetcompany, $stringaddress);

		return $stringaddress;
	}
}
