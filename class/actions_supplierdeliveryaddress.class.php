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
		//require dol_buildpath('/contact/class/contact.class.php');
		require dol_buildpath('/core/lib/pdf.lib.php');
 		if (in_array('ordersuppliercard',explode(':',$parameters['context'])) && $action == 'builddoc')
        {
        	
			require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
        	
        	/*echo '<pre>';
        	print_r($object);
			echo '</pre>';*/
        	/*$id_fournisseur = $_REQUEST['id'];
			//var_dump($hookmanager);
			//var_dump($object);
			//echo $id_fournisseur;
			$soc = new Societe($db);
			$soc->fetch($id_fournisseur);
			$Tcontacts = $soc->contact_array();*/
			
			$TContacts = $object->liste_contact();
			foreach($TContacts as $c) {
				if($c['code'] == 'SHIPPING') {
					$contact = new Contact($db);
					$contact->fetch($c['id']);
					
					$note = empty($object->note_public)?"":$object->note_public."\n\n";
					$object->note_public = $note.$langs->trans("DeliveryAddress")." :\n".pdf_build_address($langs, $contact);
					break;
				}

				//Requête pour voir s'il existe un 'contact fournisseur facturation commande' dans la table llx_element_contact
				/*$sql = "SELECT sc.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as sc";
				$sql.= " WHERE sc.element_id = ".$id_fournisseur;
				$sql.= " AND sc.fk_socpeople = ".$id_contact;
				$sql.= " AND sc.fk_c_type_contact = 145";
				
				$resql = $db->query($sql);
			
				if($resql->num_rows != 0) {
					//echo $sql;
					$contact = new Contact($db);
					$contact->fetch($id_contact);
					//var_dump($object);
					//echo "<br />".$contact->address;
					//print_r($object);
					$object->note_public = "ouighliugliug";
					
				}*/
			}
        }
		
		return 0;
	}/*
     
    function formEditProductOptions($parameters, &$object, &$action, $hookmanager) 
    {
		
    	if (in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	
        }
		
        return 0;
    }

	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	
        }

		return 0;
	}

	function printObjectLine ($parameters, &$object, &$action, $hookmanager){
		
		global $db;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        	
        }

		return 0;
	}*/
}