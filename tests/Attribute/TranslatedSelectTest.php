<?php

/**
 * This file is part of MetaModels/attribute_translatedselect.
 *
 * (c) 2012-2022 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedselect
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Ingolf Steinhardt <info@e-spin.de>
 * @copyright  2012-2022 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedselect/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedSelectBundle\Test\Attribute;

use Doctrine\DBAL\Connection;
use MetaModels\AttributeTranslatedSelectBundle\Attribute\TranslatedSelect;
use MetaModels\Helper\TableManipulator;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests to test class Select.
 *
 * @covers \MetaModels\AttributeTranslatedSelectBundle\Attribute\TranslatedSelect
 */
class TranslatedSelectTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $language         The language.
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel
     */
    protected function mockMetaModel($language, $fallbackLanguage)
    {
        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);

        $metaModel
            ->method('getTableName')
            ->willReturn('mm_unittest');

        $metaModel
            ->method('getActiveLanguage')
            ->willReturn($language);

        $metaModel
            ->method('getFallbackLanguage')
            ->willReturn($fallbackLanguage);

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock the table manipulator.
     *
     * @param Connection $connection The database connection mock.
     *
     * @return TableManipulator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTableManipulator(Connection $connection)
    {
        return $this->getMockBuilder(TableManipulator::class)
            ->setConstructorArgs([$connection, []])
            ->getMock();
    }

    /**
     * Test that the attribute can be instantiated.
     *
     * @return void
     */
    public function testInstantiationTranslatedSelect()
    {
        $connection = $this->mockConnection();
        $select     = new TranslatedSelect(
            $this->mockMetaModel('en', 'en'),
            [],
            $connection,
            $this->mockTableManipulator($connection)
        );
        $this->assertInstanceOf(TranslatedSelect::class, $select);
    }
}
