<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Model\View;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @magentoDataFixture Magento/Theme/Model/_files/design/themes.php
 */
class DesignTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_model;

    /**
     * @var \Magento\Framework\View\FileSystem
     */
    protected $_viewFileSystem;

    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    protected $_viewConfig;

    public static function setUpBeforeClass()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var \Magento\Framework\Filesystem $filesystem */
        $filesystem = $objectManager->get('Magento\Framework\Filesystem');
        $themeDir = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $themeDir->delete('theme/frontend');
        $themeDir->delete('theme/_merged');

        $libDir = $filesystem->getDirectoryWrite(DirectoryList::LIB_WEB);
        $libDir->copyFile('prototype/prototype.js', 'prototype/prototype.min.js');
    }

    public static function tearDownAfterClass()
    {
        /** @var \Magento\Framework\Filesystem $filesystem */
        $filesystem = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get('Magento\Framework\Filesystem');
        $libDir = $filesystem->getDirectoryWrite(DirectoryList::LIB_WEB);
        $libDir->delete('prototype/prototype.min.js');
    }

    protected function setUp()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->_model = $objectManager->create('Magento\\Framework\View\DesignInterface');
        $this->_viewFileSystem = $objectManager->create('Magento\Framework\View\FileSystem');
        $this->_viewConfig = $objectManager->create('Magento\Framework\View\ConfigInterface');
        $objectManager->get('Magento\Framework\App\State')->setAreaCode('frontend');
    }

    /**
     * Emulate fixture design theme
     *
     * @param string $themePath
     */
    protected function _emulateFixtureTheme($themePath = 'Test/default')
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize(
            [
                Bootstrap::INIT_PARAM_FILESYSTEM_DIR_PATHS => [
                    DirectoryList::THEMES => [
                        'path' => realpath(__DIR__ . '/../_files/design'),
                    ],
                ],
            ]
        );
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea('frontend');
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $objectManager->get('Magento\Framework\View\DesignInterface')->setDesignTheme($themePath);

        $this->_viewFileSystem = $objectManager->create('Magento\Framework\View\FileSystem');
        $this->_viewConfig = $objectManager->create('Magento\Framework\View\ConfigInterface');
    }

    public function testSetGetArea()
    {
        $this->assertEquals(\Magento\Framework\View\DesignInterface::DEFAULT_AREA, $this->_model->getArea());
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get('Magento\Framework\App\State')
            ->setAreaCode('test');
        $this->assertEquals('test', $this->_model->getArea());
    }

    public function testSetDesignTheme()
    {
        $this->_model->setDesignTheme('Magento/blank', 'frontend');
        $this->assertEquals('Magento/blank', $this->_model->getDesignTheme()->getThemePath());
    }

    public function testGetDesignTheme()
    {
        $this->assertInstanceOf('Magento\Framework\View\Design\ThemeInterface', $this->_model->getDesignTheme());
    }

    /**
     * @magentoConfigFixture current_store design/theme/theme_id 0
     */
    public function testGetConfigurationDesignThemeDefaults()
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $themes = ['frontend' => 'test_f', 'adminhtml' => 'test_a'];
        $design = $objectManager->create('Magento\Theme\Model\View\Design', ['themes' => $themes]);
        $objectManager->addSharedInstance($design, 'Magento\Theme\Model\View\Design');

        $model = $objectManager->get('Magento\Theme\Model\View\Design');

        $this->assertEquals('test_f', $model->getConfigurationDesignTheme());
        $this->assertEquals('test_f', $model->getConfigurationDesignTheme('frontend'));
        $this->assertEquals('test_f', $model->getConfigurationDesignTheme('frontend', ['store' => 0]));
        $this->assertEquals('test_f', $model->getConfigurationDesignTheme('frontend', ['store' => null]));
        $this->assertEquals('test_a', $model->getConfigurationDesignTheme('adminhtml'));
        $this->assertEquals('test_a', $model->getConfigurationDesignTheme('adminhtml', ['store' => uniqid()]));
    }

    /**
     * @magentoConfigFixture current_store design/theme/theme_id one
     * @magentoConfigFixture fixturestore_store design/theme/theme_id two
     * @magentoDataFixture Magento/Core/_files/store.php
     */
    public function testGetConfigurationDesignThemeStore()
    {
        $storeId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Store\Model\StoreManagerInterface'
        )->getStore()->getId();
        $this->assertEquals('one', $this->_model->getConfigurationDesignTheme());
        $this->assertEquals('one', $this->_model->getConfigurationDesignTheme(null, ['store' => $storeId]));
        $this->assertEquals('one', $this->_model->getConfigurationDesignTheme('frontend', ['store' => $storeId]));
        $this->assertEquals('two', $this->_model->getConfigurationDesignTheme(null, ['store' => 'fixturestore']));
        $this->assertEquals(
            'two',
            $this->_model->getConfigurationDesignTheme('frontend', ['store' => 'fixturestore'])
        );
    }

    /**
     * @dataProvider getFilenameDataProvider
     * @magentoAppIsolation enabled
     */
    public function testGetFilename($file, $params)
    {
        $this->_emulateFixtureTheme();
        $this->assertFileExists($this->_viewFileSystem->getFilename($file, $params));
    }

    /**
     * @return array
     */
    public function getFilenameDataProvider()
    {
        return [
            ['theme_file.txt', ['module' => 'Magento_Catalog']],
            ['Magento_Catalog::theme_file.txt', []],
            ['Magento_Catalog::theme_file_with_2_dots..txt', []],
            ['Magento_Catalog::theme_file.txt', ['module' => 'Overridden_Module']]
        ];
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetViewConfig()
    {
        $this->_emulateFixtureTheme();
        $config = $this->_viewConfig->getViewConfig();
        $this->assertInstanceOf('Magento\Framework\Config\View', $config);
        $this->assertEquals(['var1' => 'value1', 'var2' => 'value2'], $config->getVars('Namespace_Module'));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetConfigCustomized()
    {
        $this->_emulateFixtureTheme();
        /** @var $theme \Magento\Framework\View\Design\ThemeInterface */
        $theme = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\View\DesignInterface'
        )->getDesignTheme();
        $customConfigFile = $theme->getCustomization()->getCustomViewConfigPath();
        /** @var $filesystem \Magento\Framework\Filesystem */
        $filesystem = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create('Magento\Framework\Filesystem');
        $directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $relativePath = $directory->getRelativePath($customConfigFile);
        try {
            $directory->writeFile(
                $relativePath,
                '<?xml version="1.0" encoding="UTF-8"?>
                <view><vars  module="Namespace_Module"><var name="customVar">custom value</var></vars></view>'
            );

            $config = $this->_viewConfig->getViewConfig();
            $this->assertInstanceOf('Magento\Framework\Config\View', $config);
            $this->assertEquals(['customVar' => 'custom value'], $config->getVars('Namespace_Module'));
        } catch (\Exception $e) {
            $directory->delete($relativePath);
            throw $e;
        }
        $directory->delete($relativePath);
    }
}
