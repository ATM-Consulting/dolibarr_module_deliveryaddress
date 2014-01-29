<?php
class ActionsSupplierdeliveryaddress
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
		
 		if ($action == 'builddoc' && (in_array('ordersuppliercard',explode(':',$parameters['context']))
						|| in_array('ordercard',explode(':',$parameters['context']))))
        {
        	require dol_buildpath('/contact/class/contact.class.php');
			require dol_buildpath('/core/lib/pdf.lib.php');

			$TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$contact = new Contact($db);
					$contact->fetch($c['id']);
					$note = empty($object->note_public)?"":$object->note_public."\n\n";
					$object->note_public = $note.$langs->trans("DeliveryAddress")." :\n".pdf_build_address($langs, $contact);
					break;
				}
			}
        }
		
		return 0;
	}
}
