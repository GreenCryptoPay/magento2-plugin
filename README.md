# Magento 2  Bitcoin Payments - Green Crypto Processing

Accept bitcoin payments on your website.\
Bitcoin payments go directly to your wallet.

## Description
The fastest and easiest way to start accepting Bitcoin payments in your store. As of 2023, Green Crypto Processing helps eCommerce sites increase sales by including Bitcoin as payment options for their customers.

## Accept bitcoin payments, fast & easy
- The gateway is fully automated - set it and forget it.
- The payment amount is calculated using real-time exchange rates.
- Safe and secure transactions
- Eliminate chargebacks and fraud
- World-class customer support team

## Built for bitcoin merchants
- Accept Bitcoin (BTC)
- Support for all types of Bitcoin addresses Segwit, Legacy, Compatibility  enables the lowest transaction fees possible
- Privacy friendly - Customer order information remains private to your shop and is never submitted to Green Crypto Processing

## Installation via Composer

You can install Magento 2 Green Crypto Processing plugin via [Composer](http://getcomposer.org/). Run the following command in your terminal:

1. Go to your Magento 2 root folder. Enter following commands to install plugin and wait while dependencies are updated.

   ```bash
   composer require greencryptopay/magento2-plugin
   ```

2. Enter following commands to enable plugin:

    ```bash
    php bin/magento module:enable GreenCryptoPay_Merchant --clear-static-content
    php bin/magento setup:upgrade
    php bin/magento cache:clean
    ```
   
<!-- ## Installation via Magento Marketplace -->

## Plugin Configuration

Enable and configure Crypto Processing Plugin plugin in Magento Admin under `Stores / Configuration / Sales / Payment Methods / Bitcoin via Green Crypto Processing`.
