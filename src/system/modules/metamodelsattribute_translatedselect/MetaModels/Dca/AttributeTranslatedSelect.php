<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package     MetaModels
 * @subpackage  AttributeTranslatedSelect
 * @author      Christian de la Haye <service@delahaye.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Dca;

use DcGeneral\DataContainerInterface;

/**
 * Supplementary class for handling DCA information for select attributes.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedSelect
 * @author     Christian de la Haye <service@delahaye.de>
 */
class AttributeTranslatedSelect extends AttributeSelect
{
	/**
	 * @var AttributeTranslatedSelect
	 */
	protected static $objInstance = null;

	/**
	 * Get the static instance.
	 *
	 * @static
	 * @return AttributeTranslatedSelect
	 */
	public static function getInstance()
	{
		if (self::$objInstance == null) {
			self::$objInstance = new AttributeTranslatedSelect();
		}
		return self::$objInstance;
	}

	public function getSourceColumnNames(DataContainerInterface $objDC)
	{
		$arrFields = array();

		if (($objDC->getEnvironment()->getCurrentModel())
			&& \Database::getInstance()->tableExists($objDC->getEnvironment()->getCurrentModel()->getProperty('select_srctable')))
		{
			foreach (\Database::getInstance()->listFields($objDC->getEnvironment()->getCurrentModel()->getProperty('select_srctable')) as $arrInfo)
			{
				if ($arrInfo['type'] != 'index')
				{
					$arrFields[$arrInfo['name']] = $arrInfo['name'];
				}
			}
		}

		return $arrFields;
	}
}
