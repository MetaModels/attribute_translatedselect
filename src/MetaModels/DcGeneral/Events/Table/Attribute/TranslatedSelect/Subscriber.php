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
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\DcGeneral\Events\Table\Attribute\TranslatedSelect;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;

/**
 * Handle events for tl_metamodel_attribute.
 */
class Subscriber extends \MetaModels\DcGeneral\Events\Table\Attribute\Select\Subscriber
{
    /**
     * Boot the system in the backend.
     *
     * @return void
     */
    protected function registerEventsInDispatcher()
    {
        $this
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getTableNames']
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getColumnNames']
            )
            ->addListener(
                GetPropertyOptionsEvent::NAME,
                [$this, 'getSourceColumnNames']
            );
    }

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

        $model    = $event->getModel();
        $table    = $model->getProperty('tag_srctable');
        $database = $this->getDatabase();

        if (!$table || !$database->tableExists($table)) {
            return;
        }

        $result = [];

        foreach ($database->listFields($table) as $arrInfo) {
            if ($arrInfo['type'] != 'index') {
                $result[$arrInfo['name']] = $arrInfo['name'];
            }
        }

        $event->setOptions($result);
    }
}
