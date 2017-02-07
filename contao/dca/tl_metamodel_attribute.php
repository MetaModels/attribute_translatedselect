<?php

/**
 * This file is part of MetaModels/attribute_translatedselect.
 *
 * (c) 2012-2016 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedcheckbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedselect extends select'] = array
(
    '+display' => array('select_langcolumn after select_id', 'select_srctable', 'select_srcsorting')
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_langcolumn'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_langcolumn'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'mandatory'          => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_srctable'] = array
(
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srctable'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => array
    (
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_srcsorting'] = array
(
    'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srcsorting'],
    'exclude'               => true,
    'inputType'             => 'select',
    'eval'                  => array
    (
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ),
);
