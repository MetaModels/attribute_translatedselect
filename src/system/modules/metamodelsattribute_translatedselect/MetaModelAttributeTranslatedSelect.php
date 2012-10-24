<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package	   MetaModels
 * @subpackage AttributeTranslatedSelect
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
 * This is the MetaModelAttribute class for handling translated select attributes.
 *
 * @package	   MetaModels
 * @subpackage AttributeTranslatedSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class MetaModelAttributeTranslatedSelect
extends MetaModelAttributeSelect
implements IMetaModelAttributeTranslated
{
	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttribute
	/////////////////////////////////////////////////////////////////

	public function getAttributeSettingNames()
	{
		return array_merge(parent::getAttributeSettingNames(), array(
			'select_langcolumn'
		));
	}

	/**
	 * {@inheritdoc}
	 *
	 * Search the attribute in the current language.
	 */
	public function searchFor($strPattern)
	{
		return $this->searchForInLanguages($strPattern, $this->getMetaModel()->getActiveLanguage());
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeComplex
	/////////////////////////////////////////////////////////////////

	public function getDataFor($arrIds)
	{
		$strActiveLanguage = $this->getMetaModel()->getActiveLanguage();
		$strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

		$arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

		// second round, fetch fallback languages if not all items could be resolved.
		if ((count($arrReturn) < count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage))
		{
			$arrFallbackIds = array();
			foreach ($arrIds as $intId)
			{
				if (empty($arrReturn[$intId]))
				{
					$arrFallbackIds[] = $intId;
				}
			}

			if ($arrFallbackIds)
			{
				$arrFallbackData = $this->getTranslatedDataFor($arrFallbackIds, $strFallbackLanguage);
				// cannot use array_merge here as it would renumber the keys.
				foreach ($arrFallbackData as $intId => $arrValue)
				{
					$arrReturn[$intId] = $arrValue;
				}
			}
		}
		return $arrReturn;
	}

	public function setDataFor($arrValues)
	{
		$this->setTranslatedDataFor($arrValues, $this->getMetaNodel()->getActiveLanguage());
	}

	/////////////////////////////////////////////////////////////////
	// interface IMetaModelAttributeTranslated
	/////////////////////////////////////////////////////////////////

	/**
	 * {@inheritdoc}
	 *
	 * Search the attribute in the given languages.
	 */
	public function searchForInLanguages($strPattern, $arrLanguages = array())
	{
		$objDB = Database::getInstance();
		$strTableNameId = $this->get('select_table');
		$strColNameId = $this->get('select_id');
		$strColNameLangCode = $this->get('select_langcolumn');
		$strColValue = $this->get('select_column');
		$arrReturn = array();

		if ($strTableNameId && $strColNameId)
		{
			$strMetaModelTableName = $this->getMetaModel()->getTableName();
			$strMetaModelTableNameId = $strMetaModelTableName.'_id';

			$objValue = $objDB->prepare(sprintf('SELECT %1$s.*, %2$s.id AS %3$s FROM %1$s LEFT JOIN %2$s ON (%1$s.%4$s=%2$s.%5$s) WHERE '.($arrLanguages ? '(%1$s.%6$s IN (%7$s))' : '').' AND %1$s.%8$s LIKE ?',
				$strTableNameId, // 1
				$strMetaModelTableName, // 2
				$strMetaModelTableNameId, // 3
				$strColNameId, // 4
				$this->getColName(), // 5
				$strColNameLangCode, // 6
				'\'' . implode('\',\'', $arrLanguages) . '\'', // 7
				$strColValue // 8
			))
			->execute(str_replace(array('*', '?'), array('%', '_'), $strPattern));
			while ($objValue->next())
			{
				$arrReturn[] = $objValue->$strMetaModelTableNameId;
			}
		}
		return $arrReturn;
	}


	public function setTranslatedDataFor($arrValues, $strLangCode)
	{
		$strMetaModelTableName = $this->getMetaModel()->getTableName();
		$strTableName = $this->get('select_table');
		$strColNameId = $this->get('select_id');
		$arrReturn = array();

		if ($strTableName && $strColNameId)
		{
			$strColNameValue = $this->get('select_column');
			$objDB = Database::getInstance();
			$strQuery = sprintf('UPDATE %1$s SET %2$s=? WHERE %1$s.id=?', $strMetaModelTableName, $this->getColName());
			foreach ($arrValues as $intItemId => $arrValue)
			{
				$objQuery = $objDB->prepare($strQuery)->execute($arrValue[$strColNameId], $intItemId);
			}
		}
	}

	/**
	 * Get values for the given items in a certain language.
	 */
	public function getTranslatedDataFor($arrIds, $strLangCode)
	{
		$objDB = Database::getInstance();
		$strTableNameId = $this->get('select_table');
		$strColNameId = $this->get('select_id');
		$strColNameLangCode = $this->get('select_langcolumn');
		$arrReturn = array();

		if ($strTableNameId && $strColNameId)
		{
			$strMetaModelTableName = $this->getMetaModel()->getTableName();
			$strMetaModelTableNameId = $strMetaModelTableName.'_id';

			$objValue = $objDB->prepare(sprintf('SELECT %1$s.*, %2$s.id AS %3$s FROM %1$s LEFT JOIN %2$s ON ((%1$s.%7$s=?) AND (%1$s.%4$s=%2$s.%5$s)) WHERE %2$s.id IN (%6$s)',
				$strTableNameId, // 1
				$strMetaModelTableName, // 2
				$strMetaModelTableNameId, // 3
				$strColNameId, // 4
				$this->getColName(), // 5
				implode(',', $arrIds), //6
				$strColNameLangCode // 7
			))
			->execute($strLangCode);
			while ($objValue->next())
			{
				$arrReturn[$objValue->$strMetaModelTableNameId] = $objValue->row();
			}
		}
		return $arrReturn;
	}

	/**
	 * Remove values for items in a certain lanugage.
	 */
	public function unsetValueFor($arrIds, $strLangCode)
	{
		// FIXME: unimplemented
		throw new Exception('MetaModelAttributeTags::unsetValueFor() is not yet implemented, please do it or find someone who can!', 1);
	}
}

?>