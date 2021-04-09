<?php

namespace Ricky\AdminCatalogActivity\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session;
use Ricky\AdminCatalogActivity\Logger\Logger;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * Admin Session
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * Logging instance
     * @var \Ricky\AdminCatalogActivity\Logger\Logger
     */
    protected $_logger;

    /**
     * Constructor
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Ricky\AdminCatalogActivity\Logger\Logger $logger
     */
    public function __construct(
        Session $authSession,
        Logger $logger
    ) {
        $this->authSession = $authSession;
        $this->_logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $user = $this->authSession->getUser();
        // //$userId = $user->getId();
        // $userName = $user->getUsername();
        // $userEmail = $user->getEmail();
        // $this->_logger->info($userName);
        // $this->_logger->info($userEmail);

        $product = $observer->getProduct();
        // echo "<pre/>";

        // $pro  = $product->getOrigData();
        // $attributes = $product->getAttributes();
        // foreach ($attributes as $a) {

        //var_dump($arr); die;

        //  print_r($product->getOrigData("quantity_and_stock_status"));
        //print_r($product->getData("quantity_and_stock_status"));
        //     die;
        // }

        // you could add more product attributes to the $compareArray
        // $compareArray = ['sku', 'price', 'special_price', 'cost', 'weight', 'special_from_date', 'special_to_date', 'status', 'visibility', 'is_salable', 'updated_at'];

        $compareArray = ['sku', 'price', 'special_price', 'cost', 'status'];
        $event = [
            "user" => $user->getUsername(),
            "user_email" => $user->getEmail(),
            "Prodcut ID" => $product->getId()
        ];
        $updatFLag = 0;
        foreach ($compareArray as $value) {
            $old = $product->getOrigData($value);
            $new = $product->getData($value);
            if ($old !== $new) {
                $updatFLag = 1;
                $event[$value] = ['old' => $old, 'new' => $new];
            }
        }

        // Stock Information 
        $oldStockData = $product->getOrigData("quantity_and_stock_status");
        //$newStockData = $product->getData("quantity_and_stock_status");
        //echo "<pre>"; print_r($oldStockData);
        $stockItem = $product->getExtensionAttributes()->getStockItem();
        //print_r($stockItem->getQty());
        //echo "<pre>"; var_dump($newStockData); die;
        //if(isset($newStockData['qty'])){

        //CHECK FOR QTY CHANGES
        if ($oldStockData['qty'] !== $stockItem->getQty()) {
            $updatFLag = 1;
            $event['qty'] = ['old' => $oldStockData['qty'], 'new' => $stockItem->getQty()];
        }

        //CHECK FOR STOCK STATUS CHANGES
        $oldStatus = $product->getOrigData("quantity_and_stock_status");
        //var_dump($oldStatus); die;
        $oldIsInStock = ($oldStatus['is_in_stock']) ? "1" : "0";

        $stauts = $product->getStockData();
        $isInStock = $stauts['is_in_stock'];
        //var_dump($stauts); die;

        $this->_logger->info("original = " . $oldIsInStock);
        $this->_logger->info(print_r($product->getOrigData("quantity_and_stock_status"), true));
        $arr = $product->getStockData();
        $this->_logger->info("simple = " . $isInStock);
        $this->_logger->info(print_r($arr['is_in_stock'], true));


        if ($oldIsInStock !== $isInStock) {
            $updatFLag = 1;
            $label = ($isInStock) ? __("In Stock") : __("Out of Stock");
            $event['stock_status'] = "Switched to " . $label;
        }

        if ($updatFLag) {
            $event["updated_at"] = $product->getData('updated_at');
            $this->_logger->info(print_r($event, true));
        }
    }
}
