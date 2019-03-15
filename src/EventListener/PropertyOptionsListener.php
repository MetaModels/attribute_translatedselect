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
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     David Molineus <david.molineus@netzmacht.de>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedSelectBundle\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\AttributeSelectBundle\EventListener\BackendEventsListener;

/**
 * Handle events for tl_metamodel_attribute.
 */
class PropertyOptionsListener extends BackendEventsListener
{
    /**
     * Retrieve all database table names.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getTableNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_srctable')) {
            return;
        }

        $event->setOptions($this->getTableAndMetaModelsList());
    }

    /**
     * Retrieve all column names for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getColumnNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_langcolumn')
        ) {
            return;
        }

        $result = $this->getColumnNamesFrom($event->getModel()->getProperty('select_table'));

        if (!empty($result)) {
            \asort($result);
            $event->setOptions($result);
        }
    }

    /**
     * Retrieve all column names of type int for the current selected table.
     *
     * @param GetPropertyOptionsEvent $event The event.
     *
     * @return void
     */
    public function getSourceColumnNames(GetPropertyOptionsEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== 'tl_metamodel_attribute')
            || ($event->getPropertyName() !== 'select_srcsorting')
        ) {
            return;
        }

        $model         = $event->getModel();
        $table         = $model->getProperty('tag_srctable');
        $schemaManager = $this->connection->getSchemaManager();

        if (!$table || !$schemaManager->tablesExist([$table])) {
            return;
        }

        $result = [];

        foreach ($schemaManager->listTableColumns($table) as $column) {
            $result[$column->getName()] = $column->getName();
        }

        $event->setOptions($result);
    }

    /**
     * Retrieve all columns from a database table.
     *
     * @param string     $tableName  The database table name.
     *
     * @param array|null $typeFilter Optional of types to filter for.
     *
     * @return string[]
     */
    protected function getColumnNamesFromMetaModel($tableName, $typeFilter = null)
    {
        if (!$this->connection->getSchemaManager()->tablesExist([$tableName])) {
            return array();
        }

        $result    = [];
        $fieldList = $this->connection->getSchemaManager()->listTableColumns($tableName);

        foreach ($fieldList as $column) {
            if (($typeFilter === null) || in_array($column->getType()->getName(), $typeFilter)) {
                $result[$column->getName()] = $column->getName();
            }
        }

        if (!empty($result)) {
            asort($result);
            return $result;
        }

        return $result;
    }
}
