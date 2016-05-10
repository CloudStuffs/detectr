<?php

/**
 * Description of Items
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Plan extends Shared\Controller {
	
	/**
	 * Stores the list of items for which the package can be made
	 * @readwrite
	 */
	protected $_items = array("Detectr", "FakeReferer", "Social", "Serp", "Webmaster");

	/**
	 * Create an Item
	 * @before _secure, _admin
	 */
	public function create() {
		$this->seo(array("title" => "Items | Create", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $errors = array();
        if (RequestMethods::post("action") == "createItem") {
        	$response = $this->_saveItem();
        	if ($response["success"]) {
        		$view->set("success", 'Item added. Go to <a href="/plan/manage">Manage Items</a>');
        	} else {
        		$errors = $response["errors"];
        	}
        }
        $view->set("errors", $errors);
        $view->set("items", $this->items);
	}

	/**
	 * Update an Item
	 * @before _secure, _admin
	 */
	public function edit($item_id) {
		$item = Item::first(array("id = ?" => $item_id));
		if (!$item) {
			$this->redirect("/admin");
		}

		$this->seo(array("title" => "Items | Edit", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $errors = array();
        if (RequestMethods::post("action") == "updateItem") {
        	$response = $this->_saveItem($item);
        	if ($response["success"]) {
        		$item = $response["item"];
        		$view->set("success", "Item Updated!!");
        	} else {
        		$errors = $response["errors"];
        	}
        }
        $view->set("errors", $errors)
        	->set("item", $item)
        	->set("items", $this->items);

	}

	/**
	 * Manage Items
	 * @before _secure, _admin
	 */
	public function manage() {
		$this->seo(array("title" => "Items | Manage", "view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $count = Item::count();
        $limit = RequestMethods::get("limit", 20);
        $page = RequestMethods::get("page", 1);
        $items = Item::all(array(), array("id", "name", "price", "tax", "period", "created", "live"), "created", "desc", $limit, $page);

        $view->set("count", $count)
        	->set("page", $page)
        	->set("limit", $limit)
        	->set("items", $items);
	}

	/**
	 * If item object is given then updates it else inserts a new item
	 * object in the database
	 * @param object $item (Optional)
	 * @return array
	 */
	protected function _saveItem($item = null) {
		if (!$item) {
			$item = new Item(array());
		}
		$item->name = RequestMethods::post("name");
		$item->description = RequestMethods::post("description");
		$item->price = RequestMethods::post("price");
		$item->tax = RequestMethods::post("tax", 0.00);
		$item->user_id = $this->user->id;
        $item->period = RequestMethods::post("period", 30);

		if ($item->validate()) {
			$item->save();
			return array("success" => true, "item" => $item);
		}
		return array("success" => false, "errors" => $item->errors);
	}

	protected function paypal() {
		$configuration = Registry::get("configuration");
        $parsed = $configuration->parse("configuration/payment");

        if (!empty($parsed->payment->paypal) && !empty($parsed->payment->paypal->clientid)) {
            $apiContext = new \PayPal\Rest\ApiContext(
				new \PayPal\Auth\OAuthTokenCredential(
					$parsed->payment->paypal->clientid, $parsed->payment->paypal->secret
				)
			);
            $apiContext->setConfig(array(
                'mode' => 'live'
            ));
            return $apiContext;
        }
	}

	protected function initializePay($package, $user) {
		$payer = new \PayPal\Api\Payer();
		$payer->setPaymentMethod('paypal');
		$total = $package->price + $package->tax;

        $item = new \PayPal\Api\Item();
        $item->setName($package->name)
        	->setCurrency('USD')
        	->setQuantity(1)
        	->setPrice($package->price);

        $itemList = new \PayPal\Api\ItemList();
        $itemList->setItems([$item]);

        $details = new \PayPal\Api\Details();
        $details->setTax($package->tax)
        	->setSubtotal($package->price);

        $amount = new \PayPal\Api\Amount();
        $amount->setCurrency("USD")
        ->setTotal($total)
        ->setDetails($details);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount)
		    ->setItemList($itemList)
		    ->setDescription($package->name)
		    ->setInvoiceNumber(uniqid());

		$baseUrl = "http://trafficmonitor.ca/";
		$redirectUrls = new \PayPal\Api\RedirectUrls();
		$redirectUrls->setReturnUrl($baseUrl."plan/success")
		    ->setCancelUrl($baseUrl."packages");

		$payment = new \PayPal\Api\Payment();
		$payment->setIntent("sale")
		    ->setPayer($payer)
		    ->setRedirectUrls($redirectUrls)
		    ->setTransactions(array($transaction));

		try {
		    $payment->create($this->paypal());
		    $transaction = new Transaction(array(
		    	"user_id" => $user->id,
                "property" => "package",
		    	"property_id" => $package->id,
		    	"payment_id" => $payment->getId(),
		    	"amount" => $total
		    ));
		    $transaction->save();
		} catch (Exception $e) {
			die($e);
		}

		return $approvalUrl = $payment->getApprovalLink();
    }

    protected function pay($package_id, $user) {
    	$package = Package::first(array("id = ?" => $package_id));
    	if ($package && (int) $package->price !== 0) {
    		$url = $this->initializePay($package, $user);
    		$this->redirect($url);
    	} else {
            $transaction = Transaction::first(["user_id = ?" => $user->id, "property = ?" => "package", "property_id = ?" => $package->id]);
            if (!$transaction) {
                $transaction = new Transaction([
                    'user_id' => $user->id,
                    'property' => 'package',
                    'property_id' => $package->id,
                    'payment_id' => 'free',
                    'amount' => $package->price,
                    'live' => 1
                ]);   
            }

            $transaction->save();
            $this->addSubscription($transaction);
        }
    }

    public function success() {
    	$paymentId = RequestMethods::get("paymentId");
    	$payerId = RequestMethods::get("PayerID");
    	
    	if (isset($paymentId)) {
    		$payment = \PayPal\Api\Payment::get($paymentId, $this->paypal());
    		$execute = new \PayPal\Api\PaymentExecution();
    		$execute->setPayerId($payerId);

    		try {
    			$result = $payment->execute($execute, $this->paypal());
    			if ($result) {
    				$transaction = Transaction::first(array("payment_id = ?" => $paymentId));
    				if ($transaction) {
    					$transaction->live = 1;
	    				$transaction->save();
	    				$this->addSubscription($transaction);
    				}
    			}
    		} catch (Exception $e) {
    			die('Error, Please contact to info@trafficmonitor.ca');
    		}
    	} else {
    		die('Error, Please contact to info@trafficmonitor.ca');
    	}
    }

    protected function addSubscription($transaction) {
        $package = Package::first(array("id = ?" => $transaction->property_id));
        $items = json_decode($package->item);
        $user = User::first(array("id = ?" => $transaction->user_id));
        $user->live = 1;
        $user->save();

        $days = (int) $package->period;
        foreach ($items as $key => $value) {
            $s = new Subscription(array(
                "user_id" => $user->id,
                "item_id" => $value,
                "period" => $days,
                "expiry" => strftime("%Y-%m-%d", strtotime("+" . ($days + 1) . " Day")),
                "is_promo" => false
            ));
            $s->save();
        }

        $this->session($user);
        $this->redirect('/member/index.html');
    }

    /**
     * @before _secure, _admin
     */
    public function transactions() {
        $this->seo(array("title" => "Transactions", "view" => $this->getLayoutView()));
        $view = $this->getActionView();
        $page = RequestMethods::get("page", $page);
        $limit = RequestMethods::get("limit", $limit);

        $transactions = Transaction::all(array(), array("user_id", "property", "property_id", "payment_id", "amount", "created"), "created", "desc", $limit, $page);

        $view->set("transactions", $transactions);
        $view->set("page", $page);
        $view->set("limit", $limit);
        $view->set("count", Transaction::count());
    }

}