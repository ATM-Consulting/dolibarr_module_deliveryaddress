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
		if (in_array('ordercard',explode(':',$parameters['context']))
			|| in_array('propalcard',explode(':',$parameters['context']))
			|| in_array('invoicecard',explode(':',$parameters['context']))
			|| in_array('ordersuppliercard',explode(':',$parameters['context']))
			)
		{
			global $db, $user, $conf, $mysoc;
			$outputlangs = $parameters['outputlangs'];
			
			dol_include_once('/contact/class/contact.class.php');
			dol_include_once('/core/lib/pdf.lib.php');
			$wysiwyg = !empty($conf->fckeditor->enabled);
			$txt = '';
			
			$TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$contact = new Contact($db);
					$contact->fetch($c['id']);
					$soc = new Societe($db);
					$soc->fetch($c['socid']);
					
					$oldconf = $conf;

					$title = $outputlangs->trans("DeliveryAddress")." :\n";
					$socname = !empty($contact->socname) ? $contact->socname."\n" : "";
					if($wysiwyg) $socname = '<strong>'.$socname.'</strong>';
					
					$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS = true;
					$conf->global->MAIN_PDF_ADDALSOTARGETDETAILS = false;
					$address = pdf_build_address($outputlangs, $mysoc, $soc, $contact, 1, 'target');
					$conf = $oldconf;
					
					$phone = '';
					if(!empty($conf->global->DELIVERYADDRESS_SHOW_PHONE)) {
						if (! empty($contact->phone_pro) || ! empty($contact->phone_mobile)) $phone .= ($address ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
						if (! empty($contact->phone_pro)) $phone .= $outputlangs->convToOutputCharset($contact->phone_pro);
						if (! empty($contact->phone_pro) && ! empty($contact->phone_mobile)) $phone .= " / ";
						if (! empty($contact->phone_mobile)) $phone .= $outputlangs->convToOutputCharset($contact->phone_mobile);
					}
					$end = !empty($object->note_public) ? "\n" : "";
					
					$txt = $title . $socname . $address . $phone . $end;
					
					break;
				}
			}
			
			// Gestion des sauts de lignes si la note était en HTML de base
			if($wysiwyg) $object->note_public = dol_nl2br($txt).$object->note_public;
			else $object->note_public = $txt.$object->note_public;
		}
	}
}