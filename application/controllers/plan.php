<?php

/**
 * Description of Items
 *
 * @author Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Plan extends Admin {
	
	/**
	 * Stores the list of items for which the package can be made
	 * @readwrite
	 */
	protected $_items = array("Detectr", "FakeReferer", "Monitor", "Serp", "Webmaster");

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
			self::redirect("/admin");
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
        $items = Item::all(array(), array("*"), "created", "desc", $limit, $page);

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
            return $apiContext;
        }
	}

	protected function pay($package) {
		$payer = new \PayPal\Api\Payer();
		$payer->setPaymentMethod('paypal');

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
        ->setTotal($package->price + $package->tax)
        ->setDetails($details);

        $transaction = new \PayPal\Api\Transaction();
        $transaction->setAmount($amount)
		    ->setItemList($itemList)
		    ->setDescription($package->name)
		    ->setInvoiceNumber(uniqid());

		$baseUrl = "http://trafficmonitor.ca/";
		$redirectUrls = new \PayPal\Api\RedirectUrls();
		$redirectUrls->setReturnUrl($baseUrl."plan?success=true")
		    ->setCancelUrl($baseUrl."plan?success=false");

		$payment = new \PayPal\Api\Payment();
		$payment->setIntent("sale")
		    ->setPayer($payer)
		    ->setRedirectUrls($redirectUrls)
		    ->setTransactions(array($transaction));

		try {
		    $payment->create($this->paypal());
		} catch (Exception $e) {
			die($e);
		}

		return $approvalUrl = $payment->getApprovalLink();
    }

    public function test() {
    	$this->noview();
    	$package = Package::first(array("id = ?" => 1));
    	echo $this->pay($package);
    }

    public function success() {
    	$paymentId = RequestMethods::get("paymentId");
    	$payerId = RequestMethods::get("PayerId");

    	
    }
}