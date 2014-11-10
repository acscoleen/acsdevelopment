=== WooCommerce TradeGecko Integration ===

Integrate TradeGecko inventory and order fulfillment services with your WooCommerce store.

This plugin was created by: TradeGecko Pte Ltd

== SETUP Prerequisite ==
Before you setup the TradeGecko integration there are prerequisites that you should know and meet.
1. You need to setup your products in TradeGecko and WooCommerce.
2. Every product needs a unique SKU. That is true for all products and variations. The SKU is how TradeGecko tracks your products, so it is very important to setup a unique SKU for each one..

== Installation ==

1. Upload the 'woocommerce-tradegecko-integration' folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Configuration ==

Go to Wordpress > WooCommerce > TradeGecko:

I. General tab: under the General tab you will find settings to enable/disable each part of the TradeGecko integration.
        - Just enable every features you want to use in from the TradeGecko integration.

II. API tab: this is the tab where you setup the connection to the TradeGecko system
        1. Enter your TradeGecko API Application ID
        2. Enter your API Secret
        3. Enter the API Redirect URL ( It should be exactly the same as you entered it when you created you new API Application )
        4. Click the "Get Authorization Code" button. It will redirect you to the authorization page where you will Authorize the use of the TradeGecko API to the website. After you Authorize the application you will be returned back to the same API tab
        5. Click Save Changes.

III. Sync tab: This is where you will setup you automatic synchronization settings
        1. Enable Automatic Synchronizations
        2. Set the Synchronization Intervals. These could any number from 1 to 60.
        3. Set the Synchronization Periods. These could be minutes, hours, days.

IV. Sync Logs: this is where you will find information on each synchronization. In a simple table you will find the time when each manipulation of the sync was performed, what was the manipulation and what was the outcome.
        - Message: means that the sync was successful
        - Error: means that the sync was unsuccessful

== Options ==

I. General Tab:

1. General Settings:
        - Enable TradeGecko Integration: Enable to take advantage of the TradeGecko integration features.
        - Enable Debug:
                This option will provide you with a step by step log of all manipulations done during a synchronization.
                Please enable, if needed ONLY.
                The debug log will be inside woocommerce/logs/tradegecko.txt.

2. Inventory Settings:
        - Product Inventory Sync:
                Enable, to sync your WooCommerce products inventory with TradeGecko.
                The WooCommerce inventory for each product will be synchronized with the TradeGecko inventory.
        - Product Price Sync:
                Enable, to sync your WooCommerce products prices with TradeGecko.
                The WooCommerce products prices will be synchronized with the products prices in TradeGecko.
        - Product Title Sync:
                Enable, to sync your WooCommerce products title with TradeGecko products title.
	- Set Allow Backorders:
		Choose the way backorders are allowed for your products.
		"Allow": will allow backorders and the products will appear as in stock.
		"Allow, but notify the customer": will allow backorders, but will show a message to the customer that the product is on backorder.

3. Orders Settings:
        - Orders Sync:
                Enable, to sync your WooCommerce orders with TradeGecko.
                The WooCommerce orders will be created send to TradeGecko and orders status will be updated as it is updated in TradeGecko.
                Your Customers info will be synced together with the orders.
        - Order Number Prefix:
                Enter an prefix word that will identify the store orders from your other channel orders.
	- Sync Order Fulfillmets:
                Do you want to pull the shipping information from TG and display it to the customers?
		The fulfillment information will be displayed at the View Order page and Order Completed emails. You will also be able to edit it in WooCommerce from an order panel meta box.

II. API Tab:

API Application Id:
        Enter here the your API Application Id.

API Secret:
        Enter here the your API Secret. You will obtain that after you register your API Application.

Redirect URI:
        Enter here your API Redirect URI. This is the redirect uri you entered when you registered your API Application.

Authorization Code:
        Here you will see the Authorization Code given to you when you Authorize the TradeGecko Application.
        IMPORTANT: Do not change or edit this field.

Get Authorization Code: ( Button )
        Pressing the button will lead you to a TradeGecko page, where you will be asked to grant access and give Authorization to the application.

III. Sync Tab:

Automatic Orders Sync:
	Orders Synchronization:
		Turn on, then set your Automatic Orders Synchronization intervals
		Here you will see when is the next scheduled synchronization

	Sync Time Interval:
		Select the time interval you want the automatic order synchronization to be in. Example: 1, 5, 60.

	Sync Time Period:
		This is the time period you want the above interval to be in. Example: Minutes, Hours, Days.
		For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every "5 Days"

Automatic Inventory Sync:
	Orders Synchronization:
		Turn on, then set your Automatic Inventory Synchronization intervals
		Here you will see when is the next scheduled synchronization

	Sync Time Interval:
		Select the time interval you want the automatic inventory synchronization to be in. Example: 1, 5, 60.

	Sync Time Period:
		This is the time period you want the above interval to be in. Example: Minutes, Hours, Days.
		For example: if you selected "5" as Interval and "Days" as the Period, then your automatic sync will be every "5 Days"
Manual Sync:
	Manual Orders Sync: ( Button )
	        Pressing the button will perform a manual orders synchronization.
		IMPORTANT: Do not click the button, if you have more than 100 order to update and export. Please schedule an automatic synchronization event and wait for it to run instead.

	Manual Inventory Sync: ( Button )
	        Pressing the button will perform a manual inventory synchronization.
		IMPORTANT: Do not click the button, if you have more than 100 products to update and sync. Please schedule an automatic synchronization event and wait for it to run instead.

IV. Sync Logs Tab:

Clear Sync Logs: ( Button )
        Press the button to clear the synchronization logs.

Synchronization Logs Table:
        This is a simple table where you will find information about what was done during the synchronization, when it was done and what was the outcome.
        - Log Type: the outcome sync procedure
                Message: successful
                Error: unsuccessful
        - Log Message: informational message of the "Log Type"