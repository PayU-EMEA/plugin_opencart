# PayU account plugin for OpenCart over 1.5.3

**Note: Plugin does not support OpenCart 2.0**

-------
``This plugin is released under the GPL license.``

**If you have any questions or issues, feel free to contact our [technical support][ext6].**

PayU account is a web application designed as an e-wallet for shoppers willing to open an account, 
define their payment options, see their purchase history, and manage personal profiles.

## Table of Contents

[Features](#features)<br/>
[Prerequisites][1] <br />
<!--[Installation][2]-->
 * 
[Installing Manually][2.1]

<!--* [Installing from admin page][2.2]-->

[Configuration][3]
* [Configuration Parameters][3.1]

##Features
The PayU payments OpenCart plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order (with discounts included)
* Cancelling a payment order
* Conducting a refund operation (for a whole or partial order)

## Prerequisites

**Important:** This plugin works only with checkout points of sales (POS).

The following PHP extensions are required:

* [cURL][ext2] to connect and communicate to many different types of servers with many different types of protocols.
* [hash][ext3] to process directly or incrementally the arbitrary length messages by using a variety of hashing algorithms.
* [XMLWriter][ext4] to wrap the libxml xmlWriter API.
* [XMLReader][ext5] that acts as a cursor going forward on the document stream and stopping at each node on the way.

## Installation

<!--There are two ways in which you can install the plugin:

* [manual installation][2.1] by copying and pasting folders from the repository
* [installation][2.2] from the administration page

See the sections below to find out about steps for each of the procedures.-->

### Installing Manually

To install the plugin, copy folders and activate it on the administration page:

1. Copy the folders from [the plugin repository][ext1] to your OpenCart root folder on the server.
2. Go to the OpenCart administration page [http://your-opencart-url/admin].
3. Go to **Extensions** > **Payments**.
4. In the **PayU** section click **Install**.


<!--### Installing from the PrestaShop administration page

PrestaShop allows you to install the plugin from the administration page. -->

## Configuration

1. Go to the OpenCart administration page [http://your-opencart-url/admin].
2. Go to **Extensions** > **Payments**.
3. In the **PayU** section click **Edit**.


<!--Independently from the installation method, the configuration looks the same:-->

### Configuration Parameters

The tables below present the descriptions of the configuration form parameters.

#### Main parameters

The main parameters for plugin configuration are as follows:

| Parameter | Values | Description | 
|:---------:|:------:|:-----------:|
|Status|Enabled/Disabled|Specifies whether the module is enabled.|
|Sort Order|Positive integers|The priority that the payment method gets in the payment methods list.|

#### Parameters of production environment

<!--To check the values of the parameters below, go to **Administration Panel** > **My shops** > **Your shop** > **POS** and click the name of a given POS.
-->

| Parameter | Description | 
|:---------:|:-----------:|
|POS ID|Unique ID of the POS|
|Second Key| MD5 key for securing communication|

#### Status parameters

Defines which status is assigned to an order at a particular stage of order processing.

#### Settings of external resources

You can set external resources for the following:

| Parameter |Description | 
|:---------:|:-----------:|
|Small logo|Button for accepting payments|

<!--LINKS-->

<!--topic urls:-->

[1]: https://github.com/PayU/plugin_opencart_153#prerequisites
[2]: https://github.com/PayU/plugin_opencart_153#installation
[2.1]: https://github.com/PayU/plugin_opencart_153#installing-manually
[2.2]: https://github.com/PayU/plugin_opencart_153#installing-from-admin-page
[3]: https://github.com/PayU/plugin_opencart_153#configuration
[3.1]: https://github.com/PayU/plugin_opencart_153#configuration-parameters
[3.1.1]: https://github.com/PayU/plugin_opencart_153#main-parameters
[3.1.2]: https://github.com/PayU/plugin_opencart_153#parameters-of-production-and-test-environments
[3.1.3]: https://github.com/PayU/plugin_opencart_153#settings-of-external-resources


<!--external links:-->

[ext1]: https://github.com/PayU/plugin_opencart_153
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php
[ext4]: http://php.net/manual/en/book.xmlwriter.php
[ext5]: http://php.net/manual/en/book.xmlreader.php
[ext6]: https://www.payu.pl/en/help

<!--images:-->
