<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Varien
 * @package    Varien_Object
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include_once "Maged/Pear.php";

class Maged_Model_Pear extends Maged_Model
{
    protected $_remotePackages;

    protected function _construct()
    {
        parent::_construct();
    }

    public function pear()
    {
        return Maged_Pear::getInstance();
    }

    public function installAll($force=false)
    {
        $options = array();
        if ($force) {
            $this->pear()->cleanRegistry();
        }
        $options['force'] = 1;
        $packages = array(
            'Mage_All_Latest',
        );
        $params = array();
        foreach ($packages as $pkg) {
            $params[] = 'connect.magentocommerce.com/core/'.$pkg;
        }
        $this->pear()->runHtmlConsole(array('command'=>'install', 'options'=>$options, 'params'=>$params));
    }

    public function upgradeAll()
    {
        $this->pear()->runHtmlConsole(array('command'=>'upgrade-all'));
    }

    public function getAllPackages()
    {
        $pear = $this->pear();

        $packages = array();

        foreach ($this->pear()->getMagentoChannels() as $channel=>$channelName) {
            $pear->run('list', array('channel'=>$channel));
            $output = $pear->getOutput();
            if (empty($output)) {
                continue;
            }
            foreach ($output as $channelData) {
                $channelData = $channelData['output'];
                $channel = $channelData['channel'];
                if (!is_array($channelData) || !isset($channelData['headline']) || !isset($channelData['data'])) {
                    continue;
                }
                foreach ($channelData['data'] as $pkg) {
                    $packages[$channel][$pkg[0]] = array(
                        'local_version' => $pkg[1],
                        'state' => $pkg[2],
                        'remote_version'=>'',
                        'summary'=>'',
                    );
                }
            }
        }

        foreach ($this->pear()->getMagentoChannels() as $channel=>$channelName) {
            $pear->getFrontend()->clear();
            $result = $pear->run('list-all', array('channel'=>$channel));
            $output = $pear->getOutput();
            if (empty($output)) {
                continue;
            }

            foreach ($output as $channelData) {
                $channelData = $channelData['output'];
                $channel = $channelData['channel'];
                if (!isset($channelData['headline'])) {
                    continue;
                }
                if (empty($channelData['data'])) {
                    continue;
                }
                foreach ($channelData['data'] as $category=>$pkglist) {
                    foreach ($pkglist as $pkg) {
                        $pkgNameArr = explode('/', $pkg[0]);
                        $pkgName = isset($pkgNameArr[1]) ? $pkgNameArr[1] : $pkgNameArr[0];
                        if (!isset($packages[$channel][$pkgName])) {
                            continue;
                        }
                        $packages[$channel][$pkgName]['remote_version'] = isset($pkg[1]) ? $pkg[1] : '';
                        $packages[$channel][$pkgName]['summary'] = isset($pkg[3]) ? $pkg[3] : '';
                    }
                }
            }
        }

        foreach ($packages as $channel=>&$pkgs) {
            foreach ($pkgs as $pkgName=>&$pkg) {
                if ($pkgName=='Mage_Pear_Helpers') {
                    unset($packages[$channel][$pkgName]);
                    continue;
                }
                $actions = array();
                $systemPkg = $channel==='connect.magentocommerce.com/core' && $pkgName==='Mage_Downloader';
                if (version_compare($pkg['local_version'], $pkg['remote_version'])==-1) {
                    $status = 'upgrade-available';
                    $actions['upgrade'] = 'Upgrade';
                    if (!$systemPkg) {
                        $actions['uninstall'] = 'Uninstall';
                    }
                } else {
                    $status = 'installed';
                    $actions['reinstall'] = 'Reinstall';
                    if (!$systemPkg) {
                        $actions['uninstall'] = 'Uninstall';
                    }
                }
                $pkg['actions'] = $actions;
                $pkg['status'] = $status;
            }
        }

        return $packages;
    }

    public function applyPackagesActions($packages)
    {
        $actions = array();
        foreach ($packages as $package=>$action) {
            if ($action) {
                $arr = explode('|', $package);
                $package = $arr[0].'/'.$arr[1];
                if ($action=='upgrade') {
                    $package .= '-'.$arr[2];
                }
                $actions[$action][] = $package;
            }
        }
        if (empty($actions)) {
            $this->pear()->runHtmlConsole('No actions selected');
            exit;
        }

        $this->controller()->startInstall();

        foreach ($actions as $action=>$packages) {
            switch ($action) {
                case 'install': case 'uninstall': case 'upgrade':
                    $this->pear()->runHtmlConsole(array(
                        'command'=>$action,
                        'params'=>$packages
                    ));
                    break;

                case 'reinstall':
                    $this->pear()->runHtmlConsole(array(
                        'command'=>'install',
                        'options'=>array('force'=>1),
                        'params'=>$packages
                    ));
                    break;
            }
        }

        $this->controller()->endInstall();
    }

    public function installPackage($id)
    {
        if (!preg_match('#^magento-([^/]+)/([^-]+)(-[^-]+)?$#', $id, $match)) {
            $this->pear()->runHtmlConsole('Invalid package identifier provided: '.$id);
            exit;
        }

        $pkg = 'connect.magentocommerce.com/'.$match[1].'/'.$match[2].(!empty($match[3]) ? $match[3] : '');

        $this->controller()->startInstall();

        $this->pear()->runHtmlConsole(array(
            'command'=>'install',
            'options'=>array('force'=>1),
            'params'=>array($pkg),
        ));

        $this->controller()->endInstall();
    }

    public function saveConfigPost($p)
    {
        $result = $this->pear()->run('config-set', array(), array('preferred_state', $p['preferred_state']));
        if ($result) {
            $this->controller()->session()->addMessage('success', 'Settings has been successfully saved');
        }
        return $this;
    }
}
