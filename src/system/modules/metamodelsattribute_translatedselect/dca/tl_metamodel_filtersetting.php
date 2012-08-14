<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package	   MetaModels
 * @subpackage Backend
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  CyberSpectrum
 * @license    private
 * @filesource
 */
if (!defined('TL_ROOT'))
{
	die('You cannot access this file directly!');
}

/**
 * Table tl_metamodel_filtersetting 
 */

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['simplelookup_palettes']['translatedselect'] = array
(
	'selecttrans_alllanguages'
);

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['fields']['selecttrans_alllanguages'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['selecttrans_alllanguages'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
);

?>