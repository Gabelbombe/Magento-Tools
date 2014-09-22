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
  $log = '/tmp/duplicates.txt';

  if (! file_exists($log))
  {
	  touch($log);
  }

  fclose(fopen($log,'w')); // truncate


  // get last customer by customers collection
  $collection = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToSort('entity_id','desc')
                    ->setPageSize(1);

  $email = $collection->getFirstItem()->getEmail();


  echo "Last email found was: {$email}\nStarting\n";


  $conn  = New \Connection(2000, $email);
  $conn->setTotals()->chunk()->getCustomers();
  $total = $conn->getTotal();

  while(true)
  {
	$customers = $conn->get('cu');

	  if (empty($customers)) break;

    foreach ($customers AS $cu)
    {
      $customer = Mage::getModel("customer/customer");
      $address  = Mage::getModel("customer/address");

      $webId    = Mage::app()->getWebsite()->getId();
      $store    = Mage::app()->getStore();

      $customer->setWebsiteId($webId)
               ->loadByEmail($cu->customer->email);

      // if customer does not already exists, by email
      if (! $customer->getId())
      {
        echo "\nProcessing: {$cu->customer->firstname} {$cu->customer->lastname}\n\n";

        $customer->setWebsiteId($webId)
                 ->setStore($store)
                 ->setGroupId(1)    // General
                 ->setPrefix        ($cu->customer->prefix)
                 ->setFirstname     ($cu->customer->firstname)
                 ->setMiddleName    ($cu->customer->middlename)
                 ->setLastname      ($cu->customer->lastname)
                 ->setSuffix        ($cu->customer->suffix)
                 ->setEmail         ($cu->customer->email);

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

        echo "\tBilling Address:  {$cu->billing->postal}\n";

        // Set billing address
        $regionModel = Mage::getModel('directory/region')
                           ->loadByCode(
                              $cu->billing->state,
                              $cu->billing->countryId
                           );

        $address  = Mage::getModel("customer/address");

        $address->setCustomerId         ($customer->getId())
                ->setFirstname          ($customer->getFirstname())
                ->setMiddleName         ($customer->getMiddlename())
                ->setLastname           ($customer->getLastname())
                ->setCompany            ($cu->billing->company)

                ->setRegionId           ($regionModel->getId())
                ->setCountryId          ($cu->billing->countryId)

                ->setStreet             ([
                                          $cu->billing->street1, 
                                          $cu->billing->street2, 
                                          $cu->billing->street3,
                                        ])

                ->setCity               ($cu->billing->city)
                ->setRegion             ($cu->billing->state)
                ->setPostcode           ($cu->billing->postal)

                ->setTelephone          ($cu->customer->phone)
                ->setIsDefaultBilling   ('1')
                ->setSaveInAddressBook  ('1');

        try
        {
          $address->save();
        }

        catch (Exception $e) {
          Zend_Debug::dump($e->getMessage());
        }

        echo "\tShipping Address: {$cu->shipping->postal}\n";

        // Set shipping address
        $regionModel = Mage::getModel('directory/region')
                           ->loadByCode(
                              $cu->shiping->state,
                              $cu->shipping->countryId
                           );

        $address  = Mage::getModel("customer/address");

        $address->setCustomerId         ($customer->getId())
                ->setFirstname          ($customer->getFirstname())
                ->setMiddleName         ($customer->getMiddlename())
                ->setLastname           ($customer->getLastname())
                ->setCompany            ($cu->shipping->company)

                ->setRegionId           ($regionModel->getId())
                ->setCountryId          ($cu->shipping->countryId)

                ->setStreet             ([
                                          $cu->shipping->street1, 
                                          $cu->shipping->street2, 
                                          $cu->shipping->street3,
                                        ])

                ->setCity               ($cu->shipping->city)
                ->setRegion             ($cu->shipping->state)
                ->setPostcode           ($cu->shipping->postal)

                ->setTelephone          ($cu->customer->phone)
                ->setIsDefaultShipping  ('1')
                ->setSaveInAddressBook  ('1');

        try 
        {
          $address->save();
        }

        catch (Exception $e) {
          Zend_Debug::dump($e->getMessage());
        }

        echo "\tCountry Origin:   {$cu->billing->countryName}\n\n";
        echo "\tClients Remain:   {$conn->left()}/{$total}\n";

      } else {

        // do something here for existing customers
        echo "\n---> Found an existing customer: {$cu->customer->email}\n\n";

          // log for later I guess....
		  file_put_contents($log, "{$cu->customer->email} :: " . json_encode($cu), FILE_APPEND | LOCK_EX);

      }
      $conn->decrement(); // --

	echo "\nFinished: {$cu->userid}\n\n";
    }
    $conn->getCustomers();
  }

  // don't put slashes in your names, it's stupid....
  shell_exec ('cd ' . MAGENTO . ' && php -f flushAllCaches.php');
  rename 	 ($log, MAGENTO . '/' . end(explode('/', $log)));
