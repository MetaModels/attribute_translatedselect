<?php

/**
 * This file is part of MetaModels/attribute_translatedselect.
 *
 * (c) 2012-2024 The MetaModels team.
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
 * @author     Markus Gerards <markus.gerards@googlemail.com>
 * @author     Paul Pflugradt <paulpflugradt@googlemail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2024 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedSelectBundle\Attribute;

use Doctrine\DBAL\ArrayParameterType;
use MetaModels\Attribute\ITranslated;
use MetaModels\AttributeSelectBundle\Attribute\Select;
use MetaModels\ITranslatedMetaModel;

/**
 * This is the MetaModelAttribute class for handling translated select attributes.
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
     * @return string|false
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
    public function sortIds($idList, $strDirection)
    {
        $metaModel    = $this->getMetaModel();
        $strTableName = $this->getSortingOverrideTable();
        if (false !== $strTableName && '' !== $strTableName) {
            $strColNameId  = 'id';
            $strSortColumn = $this->getSortingOverrideColumn();

            return $this->connection->createQueryBuilder()
                ->select('m.id')
                ->from($metaModel->getTableName(), 'm')
                ->leftJoin('m', $strTableName, 't', sprintf('t.%s=m.%s', $strColNameId, $this->getColName()))
                ->orderBy('t.' . $strSortColumn, $strDirection)
                ->executeQuery()
                ->fetchFirstColumn();
        }

        $addWhere = $this->getAdditionalWhere();
        $langSet = \sprintf('\'%s\',\'%s\'', $this->getActiveLanguage(), $this->getMainLanguage());

        $subSelect = $this->connection->createQueryBuilder()
            ->select('z.id')
            ->from($this->getSelectSource(), 'z')
            ->where($this->getLanguageColumn() . ' IN (:langset)')
            ->andWhere('z.' . $this->getIdColumn() . '=m.' . $this->getColName())
            ->orderBy(sprintf('FIELD(z.%s,%s)', $this->getLanguageColumn(), $langSet))
            ->setMaxResults(1);

        if ((bool) $addWhere) {
            $subSelect->andWhere($addWhere);
        }

        $statement = $this->connection
            ->createQueryBuilder()
            ->select('m.id')
            ->from($this->getMetaModel()->getTableName(), 'm')
            ->leftJoin('m', $this->getSelectSource(), 's', \sprintf('s.id = (%s)', $subSelect->getSQL()))
            ->where('m.id IN (:ids)')
            ->orderBy('s.' . $this->getSortingColumn(), $strDirection)
            ->setParameter('ids', $idList)
            ->setParameter('langset', $langSet)
            ->executeQuery();

        // Return value list as list<mixed>, parent function wants a list<string> so we make a cast.
        return \array_map(static fn (mixed $value) => (string) $value, $statement->fetchFirstColumn());
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return \array_merge(
            parent::getAttributeSettingNames(),
            [
                'select_langcolumn',
                'select_srctable',
                'select_srcsorting'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        if (empty($varValue)) {
            return null;
        }

        $tableName   = $this->getSelectSource();
        $idColumn    = $this->getIdColumn();
        $aliasColumn = $this->getAliasColumn();
        $langColumn  = $this->getLanguageColumn();

        /** @psalm-suppress DeprecatedMethod */
        $builder = $this->connection->createQueryBuilder()
            ->select(\sprintf('IFNULL (j.%1$s, t.%1$s) as %1$s', $aliasColumn))
            ->from($tableName, 't')
            ->leftJoin(
                't',
                $tableName,
                'j',
                \sprintf('t.%1$s = j.%1$s AND j.%2$s = :activeLanguage', $idColumn, $langColumn)
            )
            ->setParameter('activeLanguage', $this->getActiveLanguage())
            ->where('t.' . $langColumn . ' = :fallbackLanguage')
            ->where('t.' . $idColumn . '=:id')
            ->setParameter('fallbackLanguage', $this->getMainLanguage())
            ->setParameter('id', $varValue[$this->getIdColumn()]);

        if (false === ($result = $builder->executeQuery()->fetchAssociative())) {
            return null;
        }

        return $result[$aliasColumn];
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $itemId)
    {
        if (null === $varValue) {
            return null;
        }

        $strColNameAlias = $this->getAliasColumn();
        $strColNameId    = $this->getIdColumn();
        $strColNameWhere = $this->getAdditionalWhere();
        $strColNameLang  = $this->getLanguageColumn();

        if (!$strColNameAlias) {
            $strColNameAlias = $strColNameId;
        }

        /** @psalm-suppress DeprecatedMethod */
        $builder = $this->connection->createQueryBuilder()
            ->select('t.*')
            ->from($this->getSelectSource(), 't')
            ->where('t.' . $strColNameAlias . '=:alias')
            ->andWhere('t.' . $strColNameLang . ' IN (:languages)')
            ->setParameter('alias', $varValue)
            ->setParameter(
                'languages',
                [$this->getActiveLanguage(), $this->getMainLanguage()],
                ArrayParameterType::STRING
            );

        if ((bool) $strColNameWhere) {
            $builder->andWhere($strColNameWhere);
        }

        if (false === ($result = $builder->executeQuery()->fetchAssociative())) {
            return null;
        }

        return $result;
    }

    /**
     * Retrieve the sorting part for the getFilterOptions() queries.
     *
     * @return string
     */
    protected function getFilterOptionsOrderBy()
    {
        if (false !== ($table = $this->getSortingOverrideTable()) && $this->getSortingOverrideColumn()) {
            return \sprintf(
                'FIELD(%s.id, (SELECT GROUP_CONCAT(id ORDER BY %s) FROM %s)),',
                $this->getSelectSource(),
                $this->getSortingOverrideColumn(),
                $table
            );
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptionsForUsedOnly($usedOnly)
    {
        $tableName   = $this->getSelectSource();
        $idColumn    = $this->getIdColumn();
        $aliasColumn = $this->getAliasColumn();
        $valueColumn = $this->getValueColumn();
        $langColumn  = $this->getLanguageColumn();
        $addWhere    = $this->getAdditionalWhere();
        $sortColumn  = $this->getSortingColumn();

        $builder = $this->connection->createQueryBuilder();
        $builder
            ->select(
                \sprintf(
                    'COUNT(t.%1$s) as mm_count, IFNULL (j.%1$s, t.%1$s) as %1$s, IFNULL (j.%2$s, t.%2$s) as %2$s',
                    $aliasColumn,
                    $valueColumn
                )
            )
            ->from($tableName, 't')
            ->leftJoin(
                't',
                $tableName,
                'j',
                \sprintf('t.%1$s = j.%1$s AND j.%2$s = :activeLanguage', $idColumn, $langColumn)
            )
            ->setParameter('activeLanguage', $this->getActiveLanguage())
            ->where('t.' . $langColumn . ' = :fallbackLanguage')
            ->setParameter('fallbackLanguage', $this->getMainLanguage())
            ->groupBy('t.' . $idColumn)
            ->orderBy('t.' . $sortColumn);

        if ((bool) $addWhere) {
            $builder->andWhere($addWhere);
        }

        if ($usedOnly) {
            $subSelect = $this->connection->createQueryBuilder();
            $subSelect
                ->select('m.' . $this->getColName())
                ->from($this->getMetaModel()->getTableName(), 'm');

            $builder->andWhere($builder->expr()->in('t.' . $idColumn, $subSelect->getSQL()));
        }

        return $builder->executeQuery();
    }

    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        if (($idList !== null) && empty($idList)) {
            return [];
        }

        $strTableName = $this->getSelectSource();
        $strColNameId = $this->getIdColumn();

        if (!($strTableName && $strColNameId)) {
            return [];
        }

        if ($idList) {
            $strColNameWhere = $this->getAdditionalWhere();

            $statement = $this
                ->connection
                ->prepare(
                    \strtr(
                        'SELECT COUNT({select_table}.{select_id}) as mm_count, {select_table}.*
                        FROM {mm_table}
                        LEFT JOIN {select_table} ON ({select_table}.id = (SELECT
                            {select_table}.id
                            FROM {select_table}
                            WHERE {select_lang} IN (:lang_set)
                            AND ({select_table}.{select_id}={mm_table}.{mm_id})
                            {add_where}
                            ORDER BY FIELD({select_table}.{select_lang},:lang_set)
                            LIMIT 1
                        ))
                        WHERE {mm_table}.id IN (:id_list)
                        GROUP BY {select_table}.{select_id}
                        ORDER BY {options_sort} {select_sort}',
                        [
                            '{select_table}' => $strTableName,
                            '{select_id}'   => $strColNameId,
                            '{select_lang}' => $this->getLanguageColumn(),
                            '{select_sort}' => $this->getSortingColumn(),
                            '{mm_table}' => $this->getMetaModel()->getTableName(),
                            '{mm_id}'   => $this->getColName(),
                            '{add_where}' => (((bool) $strColNameWhere) ? ' AND (' . $strColNameWhere . ')' : ''),
                            '{options_sort}' => $this->getFilterOptionsOrderBy()
                        ]
                    )
                );

            $statement->bindValue(':id_list', $idList, ArrayParameterType::STRING);
            $statement->bindValue(
                ':lang_set',
                [$this->getActiveLanguage(), $this->getMainLanguage()],
                ArrayParameterType::STRING
            );
            $result = $statement->executeQuery();
        } else {
            $result = $this->getFilterOptionsForUsedOnly($usedOnly);
        }

        return $this->convertOptionsList($result, $this->getAliasColumn(), $this->getValueColumn(), $arrCount);
    }

    /**
     * {@inheritdoc}
     *
     * Search the attribute in the current language.
     */
    public function searchFor($strPattern)
    {
        return null;
        // return $this->searchForInLanguages($strPattern, [$this->getActiveLanguage()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataFor($arrIds)
    {
        $strActiveLanguage = $this->getActiveLanguage();
        $strFallbackLanguage = $this->getMainLanguage();

        $arrReturn = $this->getTranslatedDataFor($arrIds, $strActiveLanguage);

        // Second round, fetch fallback languages if not all items could be resolved.
        if ((\count($arrReturn) < \count($arrIds)) && ($strActiveLanguage !== $strFallbackLanguage)) {
            $arrFallbackIds = [];
            foreach ($arrIds as $intId) {
                if (!$arrReturn[$intId]) {
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
        $this->setTranslatedDataFor($arrValues, $this->getActiveLanguage());
    }

    /**
     * {@inheritdoc}
     *
     * Search the attribute in the given languages.
     */
    public function searchForInLanguages($strPattern, $arrLanguages = [])
    {
        $strTableNameId     = $this->getSelectSource();
        $strColNameId       = $this->getIdColumn();
        $strColNameLangCode = $this->getLanguageColumn();
        $strColValue        = $this->getValueColumn();
        $strColAlias        = $this->getAliasColumn();
        $strColNameWhere    = $this->getAdditionalWhere();
        $fallbackLanguage   = $this->getMainLanguage();
        $arrReturn          = [];

        if ($strTableNameId && $strColNameId) {
            $strMetaModelTableName   = $this->getMetaModel()->getTableName();
            $strMetaModelTableNameId = $strMetaModelTableName . '_id';

            $strPattern = \str_replace(['*', '?'], ['%', '_'], $strPattern);

            if (!\in_array($fallbackLanguage, $arrLanguages)) {
                $arrLanguages[] = $fallbackLanguage;
            }

            // Using aliased join here to resolve issue #3 for normal select attributes
            // (SQL error for self referencing table).
            $objValue = $this->connection->prepare(
                \sprintf(
                    'SELECT sourceTable.*, %2$s.id AS %3$s
                FROM %1$s sourceTable
                RIGHT JOIN %2$s ON (sourceTable.%4$s=%2$s.%5$s)
                WHERE ' . ($arrLanguages ? '(sourceTable.%6$s IN (%7$s))' : '') . '
                AND (sourceTable.%8$s LIKE ? OR sourceTable.%9$s LIKE ?) %10$s',
                    // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                    $strTableNameId,                                 // 1
                    $strMetaModelTableName,                                 // 2
                    $strMetaModelTableNameId,                               // 3
                    $strColNameId,                                          // 4
                    $this->getColName(),                                    // 5
                    $strColNameLangCode,                                    // 6
                    '\'' . \implode('\',\'', $arrLanguages) . '\'', // 7
                    $strColValue,                                           // 8
                    $strColAlias,                                           // 9
                    ((bool) $strColNameWhere ? ('AND ' . $strColNameWhere) : '')   // 10
                // @codingStandardsIgnoreEnd
                )
            );

            if ([] === ($result = $objValue->executeQuery([$strPattern, $strPattern])->fetchAllAssociative())) {
                return $arrReturn;
            }

            foreach ($result as $value) {
                $arrReturn[] = (string) $value[$strMetaModelTableNameId];
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
            $strQuery = \sprintf(
                'UPDATE %1$s SET %2$s=? WHERE %1$s.id=?',
                $strMetaModelTableName,
                $this->getColName()
            );

            foreach ($arrValues as $intItemId => $arrValue) {
                $this->connection->prepare($strQuery)->executeQuery([$arrValue[$strColNameId] ?? null, $intItemId]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslatedDataFor($arrIds, $strLangCode)
    {
        $strTableNameId     = $this->getSelectSource();
        $strColNameId       = $this->getIdColumn();
        $strColNameLangCode = $this->getLanguageColumn();
        $strColNameWhere    = $this->getAdditionalWhere();
        $arrReturn          = [];

        if ($strTableNameId && $strColNameId) {
            $strMetaModelTableName   = $this->getMetaModel()->getTableName();
            $strMetaModelTableNameId = $strMetaModelTableName . '_id';

            // Using aliased join here to resolve issue #3 for normal select attributes
            // (SQL error for self referencing table).
            $objValue = $this->connection->prepare(
                \sprintf(
                    'SELECT sourceTable.*, %2$s.id AS %3$s
                FROM %1$s sourceTable
                LEFT JOIN %2$s
                    ON ((sourceTable.%7$s=?) AND (sourceTable.%4$s=%2$s.%5$s))
                WHERE %2$s.id IN (%6$s) %8$s',
                    // @codingStandardsIgnoreStart - we want to keep the numbers at the end of the lines below.
                    $strTableNameId,                                            // 1
                    $strMetaModelTableName,                                     // 2
                    $strMetaModelTableNameId,                                   // 3
                    $strColNameId,                                              // 4
                    $this->getColName(),                                        // 5
                    \implode(',', $arrIds),                            // 6
                    $strColNameLangCode,                                        // 7
                    ((bool) $strColNameWhere ? ' AND (' . $strColNameWhere . ')' : '') // 8
                // @codingStandardsIgnoreEnd
                )
            );

            if ([] === ($result = $objValue->executeQuery([$strLangCode])->fetchAllAssociative())) {
                return $arrReturn;
            }

            foreach ($result as $value) {
                $arrReturn[$value[$strMetaModelTableNameId]] = $value;
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

    /** @return non-empty-string */
    private function getActiveLanguage(): string
    {
        $metaModel = $this->getMetaModel();
        if ($metaModel instanceof ITranslatedMetaModel) {
            $language = $metaModel->getLanguage();
            assert('' !== $language);

            return $language;
        }

        /** @psalm-suppress DeprecatedMethod */
        $language = $metaModel->getActiveLanguage();
        assert('' !== $language);

        return $language;
    }

    /** @return non-empty-string */
    private function getMainLanguage(): string
    {
        $metaModel = $this->getMetaModel();
        if ($metaModel instanceof ITranslatedMetaModel) {
            $language = $metaModel->getMainLanguage();
            assert('' !== $language);

            return $language;
        }

        /** @psalm-suppress DeprecatedMethod */
        $language = $metaModel->getFallbackLanguage() ?? 'en';
        assert('' !== $language);

        return $language;
    }
}
