# Hyvä Checkout TPay

Hyvä Magewire Checkout compatibility module for `tpaycom/magento2basic`

## Hyvä Checkout

Hyvä Magewire Checkout is flexible and highly configurable checkout replacement for Magento 2.
See more at [hyva.io](https://www.hyva.io/hyva-checkout.html)

## Tpay Payment Gateway

This module extends original Magento2 module for [Tpay Payment Gateway](https://tpay.com) to be able to use it within Hyvä Magewire Checkout.
See original module at [tpaycom/magento2basic](https://github.com/tpay-com/tpay-magento2-basic)

## Instalation via composer

```bash
composer require snowdog/module-hyva-checkout-tpay:^2.0.0
```

## Compatybility

Base [TPay Payment Gateway module](https://github.com/tpay-com/tpay-magento2-basic) have been overhauled with backwards incompatybile changes.
There are two main branches for this module supporting current and legacy version of payment module.

| Payment module         | `tpaycom/magento2basic` composer version | `snowdog/module-hyva-checkout-tpay` composer version | 
|------------------------|------------------------------------------|------------------------------------------------------|
| `Tpay_Magento2`        | `^2.0.0`                                 | `^2.0.0`                                             |
| `paycom_magento2basic` | `^1.5.2`                                 | `^1.0.0`                                             |                                