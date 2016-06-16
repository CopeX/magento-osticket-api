<?php

/**
 * CopeX GmbH https://copex.io
 * Created by PhpStorm.
 * User: roman
 * Date: 15.06.16
 * Time: 14:02
 */
class CopeX_OSTicketAPI_Model_Observer extends Mage_Core_Model_Observer
{

    /**
     * takes the contact form and calls the os-ticket api via curl
     * @param Varien_Event_Observer $observer
     */
    public function sendDataToOSTicket(Varien_Event_Observer $observer)
    {
        if (!Mage::getStoreConfig('copex_osticketapi/settings/enabled')) {
            return;
        }

        $post = Mage::app()->getRequest()->getPost();
        //validate request cause the observer is also called on wrong requests
        if ($this->requestHasErrors($post)) {
            return;
        }

        $config = array(
            'url' => Mage::getStoreConfig('copex_osticketapi/settings/url'),// URL to site.tld/api/tickets.json
            'key' => Mage::getStoreConfig('copex_osticketapi/settings/api_key')  // API Key goes here
        );

        $data = array(
            'name' => $post['name'],
            'email' => $post['email'],
            'subject' => Mage::getStoreConfig('copex_osticketapi/settings/subject'),
            'message' => strip_tags($post['comment']),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'topicId' => Mage::getStoreConfig('copex_osticketapi/settings/topic_id'),
            'deptId' => Mage::getStoreConfig('copex_osticketapi/settings/dept_id'),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $config['url']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_USERAGENT, 'osTicket API Client v1.8');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'X-API-Key: ' . $config['key']));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code != 201) {
            Mage::log('Unable to create ticket:<pre> ' . $result . '</pre>');
        }

        //$ticket_id = (int)$result;
        //maybe add a info for user here
        //Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your Ticket ID is %s'));
    }

    /**
     * validates the post request
     * @param $post
     * @return bool
     * @throws Exception
     * @throws Zend_Validate_Exception
     */
    public function requestHasErrors($post)
    {
        $error = false;

        if (!Zend_Validate::is(trim($post['name']), 'NotEmpty')) {
            $error = true;
        }

        if (!Zend_Validate::is(trim($post['comment']), 'NotEmpty')) {
            $error = true;
        }

        if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
            $error = true;
        }

        if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
            $error = true;
        }
        return $error;
    }

}