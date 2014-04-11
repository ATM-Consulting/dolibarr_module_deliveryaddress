<?php
class TDeliveryaddress{
	
	static function doActionsDeliveryaddress(&$parameters, &$object, &$action, &$hookmanager) {
		
		global $langs, $db, $conf, $user;
		
		if (in_array('ordercard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context']))
			|| in_array('expeditioncard',explode(':',$parameters['context'])) || in_array('invoicecard',explode(':',$parameters['context']))
			|| in_array('invoicesuppliercard',explode(':',$parameters['context'])) || in_array('ordersuppliercard',explode(':',$parameters['context']))){
			
        	if ($action == 'builddoc')
			{
		
				dol_include_once('/contact/class/contact.class.php');
				dol_include_once('/core/lib/pdf.lib.php');
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
							if($repeat)
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
				
				//Si le module est actif sans module spécifique client alors on reproduit la génération standard dolibarr sinon on retourne l'objet modifié
				if(!$conf->global->USE_SPECIFIC_CLIENT){
						
					// ***********************************************
					// On reproduis le traitement standard de dolibarr
					// ***********************************************
					
					if (GETPOST('model'))
					{
						$object->setDocModel($user, GETPOST('model'));
					}
					
					// Define output language
					$outputlangs = $langs;
					if (! empty($conf->global->MAIN_MULTILANGS))
					{
						$outputlangs = new Translate("",$conf);
						$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					
					switch ($object->element) {
						case 'propal':
							$result= propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							break;
						case 'facture':
							$result= facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							break;
						case 'commande':
							$result= commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							break;
						case 'shipping':
							$result= expedition_pdf_create($db, $object, $object->modelpdf, $outputlangs);
							break;
						case 'delivery':
							$result= delivery_order_pdf_create($db, $object, $object->modelpdf, $outputlangs);
							break;
						case 'order_supplier':
							$result= supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							break;
						case 'invoice_supplier':
							$result= supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
							break;	
						default:
							
							break;
					}
					
					
					if ($result <= 0)
					{
						dol_print_error($db,$result);
						exit;
					}
					elseif(!in_array('ordercard',explode(':',$parameters['context'])))
					{
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
						exit;
					}
				}
				
			}
		}
	}
}