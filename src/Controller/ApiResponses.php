<?php

namespace Ksante\SubscriptionPlugin\Controller;

class ApiResponses
{
    const UNPROVIDED_SUBSCRIPTION = "You should provide the subscription id";
    const UNPROVIDED_PROGRAM = "You should provide the program id";
    const UNPROVIDED_PRODUCT = "You should provide the product id";
    const UNPROVIDED_CUSTOMER = "You should provide the customer id";
    const UNPROVIDED_SELECTED_PRODUCTS = "You should provide the selected products ids with the quantity";
    const SUBSCRIPTION_NOT_EXIST = "The given subscription does not exist in the database";
    const CUSTOMER_NOT_EXIST = "The given customer does not exist in the database";
    const PROGRAM_NOT_EXIST = "The given program does not exist in the database";
    const PRODUCT_NOT_EXIST = "The given product does not exist in the database";
    const ORDER_CREATED = "Order successfully created";
    const SUBSCRIPTION_ORDER_UPDATED = "Subscription order successfully updated";
    const EXISTENCE_ACTIVE_SUBSCRIPTION = "The given user has already a non fulfilled subscription";
    const DISABLED_CUSTOMER = "The given user is disabled";
    const CANT_AUTO_GENERATE_ORDER = "Cannot generate new order automatically";
    const UNACTIVE_SUBSCRIPTION = "Unactive subscription";
    const NUMBER_OR_ORDERS_FULLFILED = "The subscription's number of orders id fullfiled";
    const INCORRECT_NUMBER_OF_SELECTED_PRODUCTS = "The number of selected products is incorrect";
    const UNPROVIDED_STABILIZATION_OPTIONS = "Unprovided stabilization options";
    const FULFULLED_SUBSCRIPTION_DOES_NOT_EXIST = "The customer does not have a fulfilled subscription";
    const SUBSCRIPTION_ORDER_DOES_NOT_EXIST = "The subscription order does not exist";
    const SUBSCRIPTION_ORDER_ITEM_DETAIL_DOES_NOT_EXIST = "The subscription order item detail does not exist";
    const SUBSCRIPTION_ORDER_ITEM_DOES_NOT_EXIST = "The subscription order item does not exist";
    const SUBSCRIPTION_ORDER_DOES_NOT_EXIST_ITEM = "The subscription order item does not exist";
    const PRODUCT_VARIANT_DOES_NOT_EXIST = "The product variant does not exist";
    const TAXON_DOES_NOT_EXIST = "The taxon does not exist";
    const INVALID_SELECTED_CATEGORY = "The provided category does not exist or its not associated to the program";
    const INVALID_SELECTED_PRODUCTS_QUANTITY_TO_CATEGORY = "The chosen products quantity is invalid to the category code = ";
    const UNFULFILLED_PROGRAM_CATEGORIES = "You havent' selected all the program's obligatory categories";
    const UNPROVIDED_CATEGORY_ID = "You should provide the category id along with the selected products";
    const SUBSCRIPTION_DOES_NOT_EXIST = "The provided subscription does not exist";
    const INCORRECT_STABILIZATION_OPTION = "The selected stabilization option does not match the user";
    const UNNPROVIDED_SUBSCRIPTION_ORDER_ID = "You should provide the subscription order id";
    const UNNPROVIDED_NEW_SELECTED_PRODUCTS_LIST = "You should provide the new selected products list";
    const UNNPROVIDED_NEW_SELECTED_OPTIONS_LIST = "You should provide the new selected options list";
    const AUTHENTICATION_REQUIRED = "You have to be registered user to access this section";
    const NOT_ENOUGH_NECCESSARY_PARAMETERS_TO_UPDATE_SELECTEC_PRODUCTS = "You should provide all the neccessary parameters to update the selected products list";
    const NOT_ENOUGH_NECCESSARY_PARAMETERS_TO_UPDATE_SELECTED_OPTIONS = "You should provide all the neccessary parameters to update the selected options list";
    const SUBSCRIPTION_ORDER_IS_ALREADY_VALIDATED = "Can't updated the chosen subscription order, it is already converted to a real order";
    const ERROR_EMAILING = "An error has accured in the email sending";
    const SUBSCRIPTION_DOES_NOT_BELONG_TO_CUSTOMER = "The given subscription does not belong to the customer";
    const SUBSCRIPTION_STATUS_UPDATED = "Subscription successfully updated";
    const CANT_RESUME_STOPPED_SUBSCRIPTION = "Cannot resume stopped subscription";
    const UNSELECTED_STABILIZATION_CATEGORY = "The chosen stabilization category was not selected";
    const CATEGORY_NOT_SELECTED_IN_STABILIZATION_CATEGORIES = "The selected category does not belong to the chosen stabilization options";
}
