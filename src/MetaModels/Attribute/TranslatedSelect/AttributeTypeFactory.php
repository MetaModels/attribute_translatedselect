<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  2012 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedSelect;

use MetaModels\Attribute\AbstractAttributeTypeFactory;

/**
 * Attribute type factory for select attributes.
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->typeName  = 'translatedselect';
        $this->typeIcon  = 'system/modules/metamodelsattribute_translatedselect/html/select.png';
        $this->typeClass = 'MetaModels\Attribute\TranslatedSelect\TranslatedSelect';
    }
}
