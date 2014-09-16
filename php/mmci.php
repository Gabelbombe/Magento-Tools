<?php

  /** 
   * Magento Mass Customer Import
   * CPR : ehime :: Jd Daniel
   * MOD : 2014-09-09 @ 15:22:17
   * VER : 1.0
   * 
   * DEP : Mage.php
   * Magento core action file
   *
   * DEP : pdoFinder.php
   * Requires access to a source database
   */

  ini_set('display_startup_errors',1);
  ini_set('display_errors',1);
  error_reporting(-1);

  define('MAGENTO', realpath(dirname(__FILE__)));
  require_once MAGENTO . '/../../app/Mage.php';

  umask(0);
  Mage::app();

  require_once MAGENTO . '/classes/pdoBinder.php';

  $conn = New \Connection();
  $conn = $conn->setTotals()->chunk()->getCustomers()->set('cu');

  echo "Starting\n";

  while(true)
  {
    foreach ($conn->cu AS $cu) 
    {
      $customer = Mage::getModel("customer/customer");
      $address  = Mage::getModel("customer/address");

      $webid    = Mage::app()->getWebsite()->getId();
      $store    = Mage::app()->getStore();

      $customer->setWebsiteId($webid)
               ->loadByEmail($cu->customer->email);

      // if customer does not already exists, by email
      if (! $customer->getId()) 
      {
        echo "\nAdding: {$cu->customer->firstname} {$cu->customer->lastname}\n";

        $customer->setWebsiteId($webid)
                 ->setStore($store)
                 ->setGroupId(1)    // General
                 ->setPrefix        ($cu->customer->prefix)
                 ->setFirstname     ($cu->customer->firstname)
                 ->setMiddleName    ($cu->customer->middlename)
                 ->setLastname      ($cu->customer->lastname)
                 ->setSuffix        ($cu->customer->suffix)
                 ->setEmail         ($cu->customer->email)
                 ->setCountryId     ($cu->billing->countryId);

      } else {
       // do something here for existing customers
        echo "\n---> Found an existing customer: {$cu->customer->email}\n\n";
      }

      try 
      {
        // ?? $customer->save()->setConfirmation(null)->save();
        $customer->save()->setConfirmation(null)
                 ->save();

        // save successful, send new password
        // uncomment this to send the email to the customer
        // $customer->sendPasswordReminderEmail();

      } catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }

      echo "\tBilling Address: {$cu->billing->postal}\n";

      // Set biilling address
      $regionModel = Mage::getModel('directory/region')
                         ->loadByCode(
                            $cu->state, 
                            $cu->billing->countryId
                         );

      $rid = $regionModel->getId();

      $address  = Mage::getModel("customer/address");

      $address->setCustomerId         ($customer->getId())
              ->setFirstname          ($customer->getFirstname())
              ->setMiddleName         ($customer->getMiddlename())
              ->setLastname           ($customer->getLastname())

              ->setCompany            ($cu->billing->company)
              ->setRegionId           ($rid)
              ->setStreet             ($cu->billing->street1)
/*
              ->setData               (['street' => [
                                        $cu->billing->street1, 
                                        $cu->billing->street2, 
                                        $cu->billing->street3,
                                      ]])
*/
              ->setCity               ($cu->billing->city)
              ->setRegion             ($cu->billing->state)
              ->setPostcode           ($cu->billing->postal)

              ->setTelephone          ($cu->customer->phone)
              ->setIsDefaultShipping  ('1')
              ->setIsDefaultBilling   ('1')
              ->setSaveInAddressBook  ('1');

      try {
        $address->save();
      }

      catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }

      echo "\tShipping Address: {$cu->shipping->postal}\n";

      // Set shipping address
      $regionModel = Mage::getModel('directory/region')
                         ->loadByCode(
                            $cu->state, 
                            $cu->shipping->countryId
                         );

      $rid = $regionModel->getId();

      $address  = Mage::getModel("customer/address");

      $address->setCustomerId         ($customer->getId())
              ->setFirstname          ($customer->getFirstname())
              ->setMiddleName         ($customer->getMiddlename())
              ->setLastname           ($customer->getLastname())
              ->setCompany            ($cu->shipping->company)

              ->setRegionId           ($rid)
              ->setStreet             ($cu->shipping->street1)
/*
              ->setData               (['street' => [
                                        $cu->shipping->street1, 
                                        $cu->shipping->street2, 
                                        $cu->shipping->street3,
                                      ]])
*/
              ->setCity               ($cu->shipping->city)
              ->setRegion             ($cu->shipping->state)
              ->setPostcode           ($cu->shipping->postal)

              ->setTelephone          ($cu->customer->phone)
              ->setIsDefaultShipping  ('1')
              ->setSaveInAddressBook  ('1');

      try {
        $address->save();
      }

      catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }

    $customerAddress = [];
print_r(Mage::getModel('customer/address')->load($customer->getId()));
die;

    foreach ($customer->getAddresses() AS $address)
       $customerAddress = $address->toArray();

      echo "Entered customer: ";
      print_r($customerAddress, 1);

    }

    $conn = $conn->getCustomers()->set('cu');

      if (! $conn) break;
  }

  shell_exec("cd ".MAGENTO." && php -f flushAllCaches.php");
