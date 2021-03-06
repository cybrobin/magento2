<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Checkout\Test\TestStep;

use Magento\Checkout\Test\Page\CheckoutCart;
use Magento\Checkout\Test\Fixture\Cart;
use Magento\Mtf\Fixture\FixtureFactory;
use Magento\Customer\Test\Fixture\Address;
use Magento\Checkout\Test\Constraint\AssertEstimateShippingAndTax;
use Magento\Mtf\TestStep\TestStepInterface;

/**
 * Estimate Shipping and Tax
 */
class EstimateShippingAndTaxStep implements TestStepInterface
{
    /**
     * Page of checkout page.
     *
     * @var CheckoutCart
     */
    protected $checkoutCart;

    /**
     * Customer Address.
     *
     * @var Address
     */
    protected $address;

    /**
     * Assert that grand total is equal to expected.
     * Assert that subtotal total in the shopping cart is equals to expected total from data set.
     * Assert that tax amount is equal to expected.
     * Assert that shipping amount is equal to expected.
     *
     * @var AssertEstimateShippingAndTax
     */
    protected $assertEstimateShippingAndTax;

    /**
     * Cart data.
     *
     * @var Cart
     */
    protected $cart;

    /**
     * Fixture factory.
     *
     * @var FixtureFactory
     */
    protected $fixtureFactory;

    /**
     * Shipping method title and shipping service name.
     *
     * @var array
     */
    protected $shipping;

    /**
     * Products.
     *
     * @var array
     */
    protected $products;

    /**
     * @constructor
     * @param CheckoutCart $checkoutCart
     * @param Address $address
     * @param AssertEstimateShippingAndTax $assertEstimateShippingAndTax
     * @param Cart $cart
     * @param FixtureFactory $fixtureFactory
     * @param array $shipping
     * @param array $products
     */
    public function __construct(
        CheckoutCart $checkoutCart,
        Address $address,
        AssertEstimateShippingAndTax $assertEstimateShippingAndTax,
        Cart $cart,
        FixtureFactory $fixtureFactory,
        array $shipping,
        array $products
    ) {
        $this->checkoutCart = $checkoutCart;
        $this->address = $address;
        $this->assertEstimateShippingAndTax = $assertEstimateShippingAndTax;
        $this->cart = $cart;
        $this->fixtureFactory = $fixtureFactory;
        $this->shipping = $shipping;
        $this->products = $products;
    }

    /**
     * Estimate shipping and tax and process assertions for totals.
     *
     * @return void
     */
    public function run()
    {
        $this->checkoutCart->open();
        $this->checkoutCart->getShippingBlock()->fillEstimateShippingAndTax($this->address);
        if ($this->shipping['shipping_service'] !== '-') {
            $this->checkoutCart->getShippingBlock()->selectShippingMethod($this->shipping);
        }
        /** @var \Magento\Checkout\Test\Fixture\Cart $cart */
        if (!empty($this->cart->hasData())) {
            $cart = $this->fixtureFactory->createByCode(
                'cart',
                ['data' => array_merge($this->cart->getData(), ['items' => ['products' => $this->products]])]
            );
            $this->assertEstimateShippingAndTax->processAssert($this->checkoutCart, $cart);
        }
    }
}
