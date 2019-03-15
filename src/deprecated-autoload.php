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
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

// This hack is to load the "old locations" of the classes.
use MetaModels\AttributeTranslatedSelectBundle\Attribute\AttributeTypeFactory;
use MetaModels\AttributeTranslatedSelectBundle\Attribute\TranslatedSelect;

spl_autoload_register(
    function ($class) {
        static $classes = [
            'MetaModels\Attribute\TranslatedSelect\TranslatedSelect'     => TranslatedSelect::class,
            'MetaModels\Attribute\TranslatedSelect\AttributeTypeFactory' => AttributeTypeFactory::class,
        ];

        if (isset($classes[$class])) {
            // @codingStandardsIgnoreStart Silencing errors is discouraged
            @trigger_error('Class "' . $class . '" has been renamed to "' . $classes[$class] . '"', E_USER_DEPRECATED);
            // @codingStandardsIgnoreEnd

            if (!class_exists($classes[$class])) {
                spl_autoload_call($class);
            }

            class_alias($classes[$class], $class);
        }
    }
);
