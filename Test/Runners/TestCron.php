<?php
namespace Balancepay\Balancepay\Test\Runners;
require __DIR__ . '/../../../../../bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);

$obj = $bootstrap->getObjectManager();

$state = $obj->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

$object = $obj->create(\Balancepay\Balancepay\Cron\BalanceQueue::class);
$object->execute();
