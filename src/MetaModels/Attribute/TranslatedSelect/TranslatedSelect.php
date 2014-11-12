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
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Christian de la Haye <service@delahaye.de>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedSelect;

use MetaModels\Attribute\Select\Select;
use MetaModels\Attribute\ITranslated;

/**
 * This is the MetaModelAttribute class for handling translated select attributes.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedSelect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class TranslatedSelect extends Select implements ITranslated
{
    /**
     * Determine the correct language column to use.
     *
     * @return string
     */
    protected function getLanguageColumn()
    {
        return $this->get('select_langcolumn');
    }

    /**
     * Determine the correct sorting table to use.
     *
     * @return string
     */
    protected function getSortingOverrideTable()
    {
        return $this->get('select_srctable') ?: false;
    }

    /**
     * Determine the correct sorting column to use.
     *
     * @return string
     */
    protected function getSortingOverrideColumn()
    {
        return $this->get('select_srcsorting') ?: 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function sortIds($arrIds, $strDirection)
    {
        $strTableName = $this->getSortingOverrideTable();
        if (!$strTableName) {
            $strTableName  = $this->getSelectSource();
            $strColNameId  = $this->getIdColumn();
            $strSortColumn = $this->getSortingColumn();
        } else {
            $strColNameId  = 'id';
            $strSortColumn = $this->getSortingOverrideColumn();
        }
        $arrIds = \Database::getInstance()
            ->prepare(
                sprintf(
                    'SELECT %1$s.id FROM %1$s
                    LEFT JOIN %3$s ON (%3$s.%4$s=%1$s.%2$s)
                    WHERE %1$s.id IN (%5$s)
                    ORDER BY %3$s.%6$s %7$s',
                    // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                    $this->getMetaModel()->getTableName(), // 1
                    $this->getColName(),                   // 2
                    $strTableName,                         // 3
                    $strColNameId,                         // 4
                    implode(',', $arrIds),                 // 5
                    $strSortColumn,                        // 6
                    $strDirection                          // 7
                    // @codingStandardsIgnoreEnd
                )
            )
            ->execute()
            ->fetchEach('id');
        return $arrIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'select_langcolumn', 'select_srctable', 'select_srcsorting'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        $strColNameWhere = $this->getAdditionalWhere();
        $strColNameAlias = $this->getAliasColumn();
        if (!$strColNameAlias) {
            $strColNameAlias = $this->getIdColumn();
        }

        // Easy out, we have the correct language.
        if ($varValue[$this->getLanguageColumn()] == $this->getMetaModel()->getActiveLanguage()) {
            return $varValue[$strColNameAlias];
        }

        // Translate to current language.
        $objValue = \Database::getInstance()
            ->prepare(
                sprintf(
                    'SELECT %1$s.* FROM %1$s WHERE %2$s=? AND %3$s=?%4$s',
                    $this->getSelectSource(),
                    $this->getIdColumn(),
                    $this->getLanguageColumn(),
                    ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : '')
                )
            )
            ->execute(
                $varValue[$this->getIdColumn()],
                $this->getMetaModel()->getActiveLanguage()
            );

        return $objValue->$strColNameAlias;
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $intId)
    {
        $objDB           = \Database::getInstance();
        $strColNameAlias = $this->getAliasColumn();
        $strColNameId    = $this->getIdColumn();
        $strColNameWhere = $this->getAdditionalWhere();
        $strColNameLang  = $this->getLanguageColumn();
        $strLangSet      = sprintf(
            '\'%s\',\'%s\'',
            $this->getMetaModel()->getActiveLanguage(),
            $this->getMetaModel()->getFallbackLanguage()
        );

        if (!$strColNameAlias) {
            $strColNameAlias = $strColNameId;
        }

        // Lookup the id for this value.
        $objValue = $objDB
            ->prepare(
                sprintf(
                    'SELECT %1$s.* FROM %1$s WHERE %2$s=? AND %3$s IN (%4$s)%5$s',
                    $this->getSelectSource(),
                    $strColNameAlias,
                    $strColNameLang,
                    $strLangSet,
                    ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : '')
                )
            )
            ->execute($varValue);

        return $objValue->row();
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($arrIds, $usedOnly, &$arrCount = null)
    {
        if (($arrIds !== null) && empty($arrIds)) {
            return array();
        }

        $strTableName     = $this->getSelectSource();
        $strColNameId     = $this->getIdColumn();
        $strColNameLang   = $this->getLanguageColumn();
        $strColNameWhere  = $this->getAdditionalWhere();
        $strLangSet       = sprintf(
            '\'%s\',\'%s\'',
            $this->getMetaModel()->getActiveLanguage(),
            $this->getMetaModel()->getFallbackLanguage()
        );
        $strSortColumn    = $this->getSortingColumn();
        $strTableNameSrc  = $this->getSortingOverrideTable();
        $strSortColumnSrc = $this->getSortingOverrideColumn();

        $arrReturn = array();

        if ($strTableName && $strColNameId) {
            $strColNameValue = $this->getValueColumn();
            $strColNameAlias = $this->getAliasColumn();
            if (!$strColNameAlias) {
                $strColNameAlias = $strColNameId;
            }

            if ($strTableNameSrc) {
                $orderBy = sprintf(
                    'FIELD(%s.id, (SELECT GROUP_CONCAT(id ORDER BY %s) FROM %s)),',
                    $strTableName,
                    $strSortColumnSrc,
                    $strTableNameSrc
                );
            } else {
                $orderBy = '';
            }

            $objDB = \Database::getInstance();
            if ($arrIds) {
                $objValue = $objDB->prepare(sprintf(
                    'SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                    FROM %3$s
                    LEFT JOIN %1$s ON (%1$s.id = (SELECT
                        %1$s.id
                        FROM %1$s
                        WHERE %7$s IN (%8$s)
                        AND (%1$s.%2$s=%3$s.%4$s)
                        %6$s
                        ORDER BY FIELD(%1$s.%7$s,%8$s)
                        LIMIT 1
                    ))
                    WHERE %3$s.id IN (%5$s)
                    GROUP BY %1$s.%2$s
                    ORDER BY %10$s %9$s',
                    // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                    $strTableName,                                           // 1
                    $strColNameId,                                           // 2
                    $this->getMetaModel()->getTableName(),                   // 3
                    $this->getColName(),                                     // 4
                    implode(',', $arrIds),                                   // 5
                    ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : ''), // 6
                    $strColNameLang,                                         // 7
                    $strLangSet,                                             // 8
                    $strSortColumn,                                          // 9
                    $orderBy                                                 // 10
                    // @codingStandardsIgnoreEnd
                ))
                    ->execute($this->get('id'));
            } else {
                if ($usedOnly) {
                    $strQuery = sprintf(
                        'SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                        FROM %3$s
                        LEFT JOIN %1$s ON (%1$s.id = (SELECT
                            %1$s.id
                            FROM %1$s
                            WHERE %5$s IN (%6$s)
                            AND (%1$s.%2$s=%3$s.%4$s)
                            %7$s
                            ORDER BY FIELD(%1$s.%5$s,%6$s)
                            LIMIT 1
                        ))
                        GROUP BY %1$s.%2$s
                        ORDER BY %9$s %8$s',
                        // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                        $strTableName,                                           // 1
                        $strColNameId,                                           // 2
                        $this->getMetaModel()->getTableName(),                   // 3
                        $this->getColName(),                                     // 4
                        $strColNameLang,                                         // 5
                        $strLangSet,                                             // 6
                        ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : ''), // 7
                        $strSortColumn,                                          // 8
                        $orderBy                                                 // 9
                        // @codingStandardsIgnoreEnd
                    );
                } else {
                    $strQuery = sprintf(
                        'SELECT COUNT(%1$s.%2$s) as mm_count, %1$s.*
                        FROM %1$s
                        WHERE %3$s IN (%4$s)
                        %5$s
                        GROUP BY %1$s.%2$s
                        ORDER BY %7$s %6$s',
                        // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                        $strTableName,                                           // 1
                        $strColNameId,                                           // 2
                        $strColNameLang,                                         // 3
                        $strLangSet,                                             // 4
                        ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : ''), // 5
                        $strSortColumn,                                          // 6
                        $orderBy                                                 // 7
                        // @codingStandardsIgnoreEnd
                    );
                }
                $objValue = $objDB->prepare($strQuery)
                ->execute();
            }

            while ($objValue->next()) {
                if (is_array($arrCount)) {
                    $arrCount[$objValue->$strColNameAlias] = $objValue->mm_count;
                }

                $arrReturn[$objValue->$strColNameAlias] = $objValue->$strColNameValue;
            }
        }
        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     *
     * Search the attribute in the current language.
     */
    public function searchFor($strPattern)
    {
        return $this->searchForInLanguages($strPattern, array($this->getMetaModel()->getActiveLanguage()));
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        $strActiveLanguage   = $this->getMetaModel()->getActiveLanguage();
        $strFallbackLanguage = $this->getMetaModel()->getFallbackLanguage();

        $arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

        // Second round, fetch fallback languages if not all items could be resolved.
        if ((count($arrReturn) < count($arrIds)) && ($strActiveLanguage != $strFallbackLanguage)) {
            $arrFallbackIds = array();
            foreach ($arrIds as $intId) {
                if (empty($arrReturn[$intId])) {
                    $arrFallbackIds[] = $intId;
                }
            }

            if ($arrFallbackIds) {
                $arrFallbackData = $this->getTranslatedDataFor($arrFallbackIds, $strFallbackLanguage);
                // Cannot use array_merge here as it would renumber the keys.
                foreach ($arrFallbackData as $intId => $arrValue) {
                    $arrReturn[$intId] = $arrValue;
                }
            }
        }
        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFor($arrValues)
    {
        $this->setTranslatedDataFor($arrValues, $this->getMetaModel()->getActiveLanguage());
    }

    /**
     * {@inheritdoc}
     *
     * Search the attribute in the given languages.
     */
    public function searchForInLanguages($strPattern, $arrLanguages = array())
    {
        $objDB              = \Database::getInstance();
        $strTableNameId     = $this->getSelectSource();
        $strColNameId       = $this->getIdColumn();
        $strColNameLangCode = $this->getLanguageColumn();
        $strColValue        = $this->getValueColumn();
        $strColAlias        = $this->getAliasColumn();
        $strColNameWhere    = $this->getAdditionalWhere();
        $arrReturn          = array();

        if ($strTableNameId && $strColNameId) {
            $strMetaModelTableName   = $this->getMetaModel()->getTableName();
            $strMetaModelTableNameId = $strMetaModelTableName.'_id';

            $strPattern = str_replace(array('*', '?'), array('%', '_'), $strPattern);

            // Using aliased join here to resolve issue #3 for normal select attributes
            // (SQL error for self referencing table).
            $objValue = $objDB->prepare(sprintf(
                'SELECT sourceTable.*, %2$s.id AS %3$s
                FROM %1$s sourceTable
                RIGHT JOIN %2$s ON (sourceTable.%4$s=%2$s.%5$s)
                WHERE '.($arrLanguages ? '(sourceTable.%6$s IN (%7$s))' : '').'
                AND (sourceTable.%8$s LIKE ? OR sourceTable.%9$s LIKE ?) %10$s',
                // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                $strTableNameId,                                      // 1
                $strMetaModelTableName,                               // 2
                $strMetaModelTableNameId,                             // 3
                $strColNameId,                                        // 4
                $this->getColName(),                                  // 5
                $strColNameLangCode,                                  // 6
                '\'' . implode('\',\'', $arrLanguages) . '\'',        // 7
                $strColValue,                                         // 8
                $strColAlias,                                         // 9
                ($strColNameWhere ? ('AND ' . $strColNameWhere) : '') // 10
            // @codingStandardsIgnoreEnd
            ))
            ->execute($strPattern, $strPattern);

            while ($objValue->next()) {
                $arrReturn[] = $objValue->$strMetaModelTableNameId;
            }
        }
        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslatedDataFor($arrValues, $strLangCode)
    {
        $strMetaModelTableName = $this->getMetaModel()->getTableName();
        $strTableName          = $this->getSelectSource();
        $strColNameId          = $this->getIdColumn();

        if ($strTableName && $strColNameId) {
            $objDB    = \Database::getInstance();
            $strQuery = sprintf(
                'UPDATE %1$s SET %2$s=? WHERE %1$s.id=?',
                $strMetaModelTableName,
                $this->getColName()
            );

            foreach ($arrValues as $intItemId => $arrValue) {
                $objDB->prepare($strQuery)->execute($arrValue[$strColNameId], $intItemId);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $objDB              = \Database::getInstance();
        $strTableNameId     = $this->getSelectSource();
        $strColNameId       = $this->getIdColumn();
        $strColNameLangCode = $this->getLanguageColumn();
        $strColNameWhere    = $this->getAdditionalWhere();
        $arrReturn          = array();

        if ($strTableNameId && $strColNameId) {
            $strMetaModelTableName   = $this->getMetaModel()->getTableName();
            $strMetaModelTableNameId = $strMetaModelTableName.'_id';

            // Using aliased join here to resolve issue #3 for normal select attributes
            // (SQL error for self referencing table).
            $objValue = $objDB->prepare(sprintf(
                'SELECT sourceTable.*, %2$s.id AS %3$s
                FROM %1$s sourceTable
                LEFT JOIN %2$s
                    ON ((sourceTable.%7$s=?) AND (sourceTable.%4$s=%2$s.%5$s))
                WHERE %2$s.id IN (%6$s) %8$s',
                // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                $strTableNameId,                                        // 1
                $strMetaModelTableName,                                 // 2
                $strMetaModelTableNameId,                               // 3
                $strColNameId,                                          // 4
                $this->getColName(),                                    // 5
                implode(',', $arrIds),                                  // 6
                $strColNameLangCode,                                    // 7
                ($strColNameWhere ? ' AND ('.$strColNameWhere.')' : '') // 8
            // @codingStandardsIgnoreEnd
            ))
                ->execute($strLangCode);
            while ($objValue->next()) {
                $arrReturn[$objValue->$strMetaModelTableNameId] = $objValue->row();
            }
        }
        return $arrReturn;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetValueFor($arrIds, $strLangCode)
    {
        parent::unsetDataFor($arrIds);
    }
}
