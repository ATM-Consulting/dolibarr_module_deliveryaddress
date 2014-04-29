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
		global $langs,$db,$conf;

		dol_include_once('/custom/deliveryaddress/class/deliveryaddress.class.php');

		$alreadyDone = true;

		TDeliveryaddress::doActionsDeliveryaddress($parameters, $object, $action, $hookmanager, $alreadyDone);
		
		return 0;
	}
}
