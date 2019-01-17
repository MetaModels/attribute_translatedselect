<?php

/**
 * This file is part of MetaModels/attribute_translatedselect.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedselect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Christian de la Haye <service@delahaye.de>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['translatedselect extends select'] = [
    '+display' => ['select_langcolumn after select_id', 'select_srctable', 'select_srcsorting']
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_langcolumn'] = [
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_langcolumn'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => [
        'mandatory'          => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'                    => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_srctable'] = [
    'label'                  => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srctable'],
    'exclude'                => true,
    'inputType'              => 'select',
    'eval'                   => [
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'                    => 'varchar(255) NOT NULL default \'\''
];

$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['select_srcsorting'] = [
    'label'                 => &$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srcsorting'],
    'exclude'               => true,
    'inputType'             => 'select',
    'eval'                  => [
        'includeBlankOption' => true,
        'alwaysSave'         => true,
        'submitOnChange'     => true,
        'tl_class'           => 'w50',
        'chosen'             => 'true'
    ],
    'sql'                    => 'varchar(255) NOT NULL default \'\''
];

