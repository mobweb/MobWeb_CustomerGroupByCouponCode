<?php

class MobWeb_CustomerGroupByCouponCode_Model_Observer
{
	public function salesOrderSaveAfter($observer)
	{
		// Get the array with the links from the Sales Rules to the
		// customer groups from the Admin Panel
		$couponsToCustomerGroupsRaw = Mage::getStoreConfig( 'customergroupbycouponcode/customergroupbycouponcode/coupon_codes' );

		if(!$couponsToCustomerGroupsRaw) {
		    return;
		}

		// Parse the Admin Panel data into an array
		$couponsToCustomerGroups = array();

		// Explode the first level of the raw data, where each line is
		// a code:group combination
		foreach(explode("\n", $couponsToCustomerGroupsRaw) AS $couponToCustomerGroup) {
		    // Explode the second level of raw data, where on each line the
		    // coupon is followed by the group (coupon:group)
		    $couponToCustomerGroup = explode(':', $couponToCustomerGroup);
		    $couponCode = $couponToCustomerGroup[0];
		    $customerGroup = $couponToCustomerGroup[1];

		    // Save the separated values into the main array
		    $couponsToCustomerGroups[$couponCode] = $customerGroup;
		}
		
		// Get the order object
		$order = $observer->getOrder();

		// Loop through the order products and retrieve the applied
		// sales rule's IDs
		$collectedRuleIds = array();
		$itemRules = array();
		foreach($order->getAllVisibleItems() as $orderItem) {
		    if($orderItem->getAppliedRuleIds()) {
		        $itemRules[$orderItem->getId()] = explode(',', $orderItem->getAppliedRuleIds());
		        $collectedRuleIds = array_merge($collectedRuleIds, $itemRules[$orderItem->getId()]);
		    }
		}

		// Loop through the sales rule's IDs and check if any of them
		// is used to update the customer's customer group
		foreach($collectedRuleIds AS $salesRuleId) {
			if(array_key_exists($salesRuleId, $couponsToCustomerGroups)) {
				// Get the customer group ID
				$customerGroupId = $couponsToCustomerGroups[$salesRuleId];

				// Check if the customer is logged in
				if($customerId = $order->getCustomerId()) {
					// Get the current customer account
					$customer = Mage::getModel('customer/customer')->load($customerId);

					// Add the customer to the specified customer group
					$customer->setGroupId($customerGroupId)->save();
				}
			}
		}
	}
}