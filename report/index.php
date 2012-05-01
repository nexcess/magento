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
 * @category   Mage
 * @package    Mage
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define('CONFIG_FILE', 'config.xml');
$baseUrl = dirname(dirname($_SERVER['PHP_SELF']));

if (isset($_GET['id'])) {
    $traceFile = '../var/'.$_GET['id'];
    $path = realpath(getcwd().'/../var/'.$_GET['id']);
    $pathArray = explode('/', $path);
    array_pop($pathArray);
    $new_path = implode('/', $pathArray);
    if ($new_path != realpath(getcwd().'/../var')) {
        $traceFile = '';
    }
    if (!is_file(getcwd().'/../var/'.$_GET['id'])) {
        $traceFile = '';
    }
} else {
    $traceFile = '';
}

if ((!$_POST && !isset($_GET['id'])) || (isset($_GET['id']) && $traceFile == '') || ($traceFile != '' && !is_file($traceFile)) || !is_file(CONFIG_FILE)) {
    header("Location: " . $baseUrl);
    die;
}

$config = new SimpleXMLElement(implode('', file(CONFIG_FILE)));
if ($config->report->email_address == '' && $config->report->action == 'email') {
    header("Location: " . $baseUrl);
    die;
}

$showErrorMsg = false;
$showSendForm = false;

$action = ($config->report->action == '')?'print':$config->report->action;
$trash = ($config->report->trash == '')?'leave':$config->report->trash;

$firstName = (isset($_POST['firstname']))? trim($_POST['firstname']) : '';
$lastName = (isset($_POST['lastname']))? trim($_POST['lastname']) : '';
$email = (isset($_POST['email']))? trim($_POST['email']) : '';
$telephone = (isset($_POST['telephone']))? trim($_POST['telephone']) : '';
$comment = (isset($_POST['comment']))? trim(strip_tags($_POST['comment'])) : '';
$errorHash = (isset($_POST['error_hash']))? $_POST['error_hash'] : '';

if (isset($_POST['submit'])) {
    if ($firstName == '' || $lastName == ''
        || $email == '' || !checkEmail($email)) {

        $showSendForm = true;
        $showErrorMsg = true;
    } else {
        $msg = "First Name: {$firstName}\n";
        $msg .= "Last Name: {$lastName}\n";
        $msg .= "E-mail Address: {$email}\n";

        if ($telephone) {
            $msg .= "Telephone: {$telephone}\n";
        }

        if ($comment) {
            $msg .= "Comment: {$comment}\n";
        }

        mail($config->report->email_address,
            $config->report->subject." [{$errorHash}]",
            $msg);
    }
} else {
    $time = @date("m/d/Y H:i:d");
    $trace = implode(file($traceFile));
    $errorHash = md5($trace.$time);

    if (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    } else {
        $url = "url not available";
    }

    if ($action == 'email') {
        $msg = "URL: {$url}\n";
        $msg .= "Time: {$time}\n";
        $msg .= "Trace: {$trace}\n";

        mail($config->report->email_address,
            $config->report->subject." [{$errorHash}]",
            $msg);

        $showSendForm = true;
    }
    if ($trash == 'delete') {
        unlink($traceFile);
    }
}

if (isset($_GET['s'])) {
    $store = $_GET['s'];
    $path = realpath(getcwd().'/skin/'.$store);
    $pathArray = explode('/', $path);
    array_pop($pathArray);
    $new_path = implode('/', $pathArray);
    if ($new_path != getcwd().'/skin') {
        $store = 'default';
    }
    if (!is_dir(getcwd().'/skin/'.$store)) {
        $store = 'default';
    }
} else {
    $store = 'default';
}

include_once ('skin/'.$store.'/index.phtml');

function checkEmail($email) {
    return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
}