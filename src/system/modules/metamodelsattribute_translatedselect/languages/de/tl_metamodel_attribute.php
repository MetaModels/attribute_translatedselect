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

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['typeOptions']['translatedselect']    = 'Übersetzte Auswahl';
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srctable']                    = array('Ursprungstabelle für Sortierung', 'Bitte wählen Sie die Tabelle aus, die das Feld für die Sortierung enthält.');
$GLOBALS['TL_LANG']['tl_metamodel_attribute']['select_srcsorting']                  = array('Sortierspalte', 'Bitte wählen Sie die Spalte in der Ursprungstabelle aus, nach der die Auswahlen sortiert werden sollen.');