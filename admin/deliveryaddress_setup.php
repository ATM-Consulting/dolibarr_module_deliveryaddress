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
 * 	\file		admin/deliveryaddress.php
 * 	\ingroup	deliveryaddress
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/deliveryaddress.lib.php';
dol_include_once('abricot/includes/lib/admin.lib.php');

// Translations
$langs->loadLangs(array("admin", "deliveryaddress@deliveryaddress"));

$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code, 'none'), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "DeliveryAddressSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_module.svg@deliveryaddress');
// Configuration header
$head = deliveryaddressAdminPrepareHead();
$notab = 1;
echo dol_get_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104060Name"),
	$notab,
    "module@deliveryaddress"
);


echo dol_get_fiche_end($notab);

// Check abricot version
if(!function_exists('setup_print_title') || !function_exists('isAbricotMinVersion') || isAbricotMinVersion('3.1.0') < 0 ){
	print '<div class="error" >'.$langs->trans('AbricotNeedUpdate').' : <a href="http://wiki.atm-consulting.fr/index.php/Accueil#Abricot" target="_blank"><i class="fa fa-info"></i> Wiki</a></div>';
	exit;
}

// Setup page goes here
$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


// Example with a yes / no select
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamDELIVERYADDRESS_SHOW_PHONE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_SHOW_PHONE">';
print $form->selectyesno("DELIVERYADDRESS_SHOW_PHONE",getDolGlobalString('DELIVERYADDRESS_SHOW_PHONE')?$conf->global->DELIVERYADDRESS_SHOW_PHONE:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ParamDELIVERYADDRESS_SHOW_EMAIL").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_SHOW_EMAIL">';
print $form->selectyesno("DELIVERYADDRESS_SHOW_EMAIL",getDolGlobalString('DELIVERYADDRESS_SHOW_EMAIL')?$conf->global->DELIVERYADDRESS_SHOW_EMAIL:'',1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_SHOW_INFO_REPONSABLE_RECEPTION").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_SHOW_INFO_REPONSABLE_RECEPTION">';
print ajax_constantonoff('DELIVERYADDRESS_SHOW_INFO_REPONSABLE_RECEPTION');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_HIDE_ADDRESS_ON_INVOICECARD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_HIDE_ADDRESS_ON_INVOICECARD">';
print ajax_constantonoff('DELIVERYADDRESS_HIDE_ADDRESS_ON_INVOICECARD');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERCARD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERCARD">';
print ajax_constantonoff('DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERCARD');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_HIDE_ADDRESS_ON_PROPALCARD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_HIDE_ADDRESS_ON_PROPALCARD">';
print ajax_constantonoff('DELIVERYADDRESS_HIDE_ADDRESS_ON_PROPALCARD');
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD">';
print ajax_constantonoff('DELIVERYADDRESS_HIDE_ADDRESS_ON_ORDERSUPPLIERCARD');
print '</form>';
print '</td></tr>';


$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'; // Keep form because ajax_constantonoff return single link with <a> if the js is disabled
print '<input type="hidden" name="token" value="'.$newToken.'">';
print '<input type="hidden" name="action" value="set_DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES">';
$arrayType=array('returnChar1'=>$langs->trans('DeliveryAddressSepReturnCar1'),
                 'returnChar2'=>$langs->trans('DeliveryAddressSepReturnCar2'),
                 'dash'=>$langs->trans('DeliveryAddressSepdash'));
print $form->selectarray("DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES",$arrayType,$conf->global->DELIVERYADDRESS_SEPARATOR_BETWEEN_NOTES,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';





// *************************************************
// CONFIGURATION AJOUT DE L'ADRESSE DE FACTURATION *
// CONFIGURATION ADD BILLING ADDRESS               *
// *************************************************
setup_print_title('OptionsForBillingAddress');

setup_print_on_off('DELIVERYADDRESS_DISPLAY_BILLED_ON_EXPEDITIONCARD');
setup_print_on_off('DELIVERYADDRESS_DISPLAY_BILLED_ON_DELIVERYCARD');

print '</table>';

llxFooter();

$db->close();
