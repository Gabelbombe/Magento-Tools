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

  $customer = Mage::getModel("customer/customer");
  $address  = Mage::getModel("customer/address");

  $conn = New \Connection();
  $conn = $conn->setTotals()->chunk()->getCustomers()->set('cu');

  echo "Starting\n";

  while(true)
  {
    foreach ($conn->cu AS $cu) 
    {
      $webid    = Mage::app()->getWebsite()->getId();
      $store    = Mage::app()->getStore();

      $customer->setWebsiteId($webid)
               ->loadByEmail($cu->customer->email);

      // if customer does not already exists, by email
      if (! $customer->getId()) 
      {

        echo "\nAdding: {$$cu->customer->firstname} {$cu->customer->lastname}\n\n";

        $customer->setWebsiteId($webid)
                 ->setStore($store)
                 ->setGroupId(2)
                 ->setPrefix        ($cu->customer->prefix)
                 ->setFirstname     ($cu->customer->firstname)
                 ->setMiddleName    ($cu->customer->middlename)
                 ->setLastname      ($cu->customer->lastname)
                 ->setSuffix        ($cu->customer->suffix)
                 ->setEmail         ($cu->customer->email);

/*
        // generate a new password
        $customer->changePassword(
          $customer->generatePassword()
        );
*/
      } else {
       // do something here for existing customers
        echo "\n---> Found an existing customer: $cu->customer->email\n\n";
      }

      try 
      {
        // ?? $customer->save()->setConfirmation(null)->save();
        $customer->setConfirmation(null)
                 ->save();

        // save successful, send new password
        // uncomment this to send the email to the customer
        // $customer->sendPasswordReminderEmail();

      } catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }

      echo "Adding Billing Address\n";

      // Set biilling address
      $regionModel = Mage::getModel('directory/region')
                         ->loadByCode(
                            $cu->state, 
                            $cu->billing->countryId
                         );

      $rid = $regionModel->getId();

      $address->setCustomerId         ($customer->getId())
              ->setFirstname          ($customer->getFirstname())
              ->setMiddleName         ($customer->getMiddlename())
              ->setLastname           ($customer->getLastname())
            
              ->setRegionId           ($rid)            //sta  ->setCountryId          ($cid)te/province, only needed if the country is USA
              ->setPostcode           ($cu->billing->postal)
              ->setCity               ($cu->billing->city)
              ->setTelephone          ($cu->billing->phone)
              ->setCompany            ($cu->billing->company)

              ->setData               (['street' => [
                                        $cu->billing->street1, 
                                        $cu->billing->street2, 
                                        $cu->billing->street3,
                                      ]])

              ->setIsDefaultBilling   ('1')
//              ->setIsDefaultShipping  ('1')
              ->setSaveInAddressBook  ('1');

      try {
        $address->save();
      }

      catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }

      echo "Adding Shipping Address\n";

      // Set shipping address
      $regionModel = Mage::getModel('directory/region')
                         ->loadByCode(
                            $cu->state, 
                            $cu->shipping->countryId
                         );

      $rid = $regionModel->getId();

      $address->setCustomerId         ($customer->getId())
              ->setFirstname          ($customer->getFirstname())
              ->setMiddleName         ($customer->getMiddlename())
              ->setLastname           ($customer->getLastname())
            
              ->setRegionId           ($rid)            //sta  ->setCountryId          ($cid)te/province, only needed if the country is USA
              ->setPostcode           ($cu->shipping->postal)
              ->setCity               ($cu->shipping->city)
              ->setTelephone          ($cu->shipping->phone)
              ->setCompany            ($cu->shipping->company)

              ->setData               (['street' => [
                                        $cu->shipping->street1, 
                                        $cu->shipping->street2, 
                                        $cu->shipping->street3,
                                      ]])

//              ->setIsDefaultBilling   ('1')
              ->setIsDefaultShipping  ('1')
              ->setSaveInAddressBook  ('1');

      try {
        $address->save();
      }

      catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
      }
    }

    $conn = $conn->getCustomers()->set('cu');

      if (! $conn) break;
  }
