<?php /* Copyright (C) 2005-2011 Laurent Destailleur 
<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/triggers/interface_90_all_Demo.class.php
 *  \ingroup    core
 *  \brief      Fichier de demo de personalisation des actions du workflow
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *				                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la methode constructeur doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */


/**
 *  Class of triggers for Mantis module
 */
 
class InterfaceDeliveryAddressWorkflow
{
    var $db;
    
    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    
        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ATM";
        $this->description = "Trigger du module de devise multiple";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }
    
    
    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,&$object,$user,$langs,$conf)
	{
		global  $user, $conf;
		dol_include_once('/contact/class/contact.class.php');
		dol_include_once('/core/lib/pdf.lib.php');

		$db=&$this->db;

		if($action == "BEFORE_ORDER_BUILDDOC"  || $action == "BEFORE_ORDER_SUPPLIER_BUILDDOC"){
				
			$txt = '';
			$TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$contact = new Contact($db);
					$contact->fetch($c['id']);
					$soc = new Societe($db);
					$soc->fetch($c['socid']);

					//$address = $langs->trans("DeliveryAddress").": \n";
					$address = $langs->trans("DeliveryAddress")." : \n";
					$address.= !empty($contact->socname) ? $contact->socname."\n" : "";
					$address.= pdf_build_address($langs, $mysoc, $soc, $contact, 1, 'target');
					$address.= !empty($contact->phone_pro) ? "\n".$langs->transnoentities("Phone").": ".$langs->convToOutputCharset($contact->phone_pro) : "";
					$address.= !empty($object->note_public) ? "\n" : "";
					
					if($conf->clipastel->enabled){
						if(!$alreadyDone)
							$txt = $address;
					}
					else{
						$txt = $address;
					}
					
					break;
				}
			}
			
			// Gestion des sauts de lignes si la note était en HTML de base
			if(dol_textishtml($object->note_public)) $object->note_public = dol_nl2br($txt).$object->note_public;
			else $object->note_public = $txt.$object->note_public;

		}	
		
		if($action == "ORDER_BUILDDOC" || $action == "ORDER_SUPPLIER_BUILDDOC") {
			
			$object->fetch($object->id);
			
		}
		
		
		return 1;
	}
}
