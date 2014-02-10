<?php
class ActionsDeliveryaddress
{ 
	/** Overloading the doActions function : replacing the parent's function with the one below 
	 *  @param      parameters  meta datas of the hook (context, etc...) 
	 *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
	 *  @param      action             current action (if set). Generally create or edit or null 
	 *  @return       void 
	 */
	function doActions($parameters, &$object, &$action, $hookmanager) 
	{
		global $langs,$db;
		
 		if ($action == 'builddoc'
 			&& (in_array('ordersuppliercard',explode(':',$parameters['context']))
				|| in_array('ordercard',explode(':',$parameters['context']))))
			{
				
				/*echo '<pre>';
				print_r($object);
				echo '</pre>'; exit;*/
				
				dol_include_once('/contact/class/contact.class.php');
				dol_include_once('/core/lib/pdf.lib.php');
	
				$TContacts = $object->liste_contact();
				foreach($TContacts as $c) {
					if($c['code'] == 'SHIPPING') {
						$contact = new Contact($db);
						$contact->fetch($c['id']);
						$soc = new Societe($db);
						$soc->fetch($c['socid']);
						
						$address = $langs->trans("DeliveryAddress").": \n";
						$address.= !empty($contact->socname) ? $contact->socname."\n" : "";
						$address.= pdf_build_address($langs, $mysoc, $soc, $contact, 1, 'target');
						$address.= !empty($contact->phone_pro) ? "\n".$langs->transnoentities("Phone").": ".$langs->convToOutputCharset($contact->phone_pro) : "";
						$address.= !empty($object->note_public) ? "\n" : "";
						
						if(strpos($object->note_public, $address) === FALSE){
							$object->note_public = $address.$object->note_public;
						}
						break;
					}
				}
			}
		
		return 0;
	}
}
