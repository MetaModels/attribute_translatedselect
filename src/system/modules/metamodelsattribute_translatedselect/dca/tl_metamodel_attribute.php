<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');


/**
 * Table tl_metamodel_attribute 
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedselect extends select'] = array
(
	'+title' => array('select_langcolumn after select_id')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_langcolumn'] = array
(
	'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_langcolumn'],
	'exclude'               => true,
	'inputType'             => 'select',
	'options_callback'      => array('TableMetaModelsAttributeSelect', 'getColumnNames'),
	'eval'                  => array
	(
		'includeBlankOption' => true,
		'doNotSaveEmpty' => true,
		'alwaysSave' => true,
		'submitOnChange'=> true,
		'tl_class'=>'w50',
		'chosen' => 'true'
	),
);

?>