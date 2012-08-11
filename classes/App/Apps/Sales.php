<?php

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    namespace ADV\App\Apps;

    /**

     */
    use Orders;
    use ADV\App\Application\Application;

    class Sales extends Application
    {
        public $name = 'Sales';
        public $help_context = '&Sales';
        public function buildMenu()
        {

            $module = $this->add_module('Quotations and Orders');
            $module->addLeftFunction('', '');
            $module->addLeftFunction(_('New Sales &Quotation'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESQUOTE, SA_SALESQUOTE);
            $module->addLeftFunction(_('Search Quotatio&ns'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=' . ST_SALESQUOTE . '', SA_SALESTRANSVIEW);
            $module->addLeftFunction('', '');
            $module->addRightFunction(_('New Sales &Order'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESORDER);
            $module->addRightFunction(_('Search  S&ales Orders'), '/sales/inquiry/sales_orders_view?' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESTRANSVIEW);
            $module = $this->add_module(_('Deliveries and Invoices'));
            $module->addLeftFunction(_('New D&irect Delivery'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_CUSTDELIVERY, SA_SALESDELIVERY);
            $module->addLeftFunction(_('&Delivery Against Sales Orders'), '/sales/inquiry/sales_orders_view?OutstandingOnly=1', SA_SALESDELIVERY);
            $module->addLeftFunction(_('&Invoice Against Delivery'), '/sales/inquiry/sales_deliveries_view?OutstandingOnly=1', SA_SALESINVOICE);
            $module->addLeftFunction(_('&Template Delivery'), '/sales/inquiry/sales_orders_view?DeliveryTemplates=Yes', SA_SALESDELIVERY);
            $module->addRightFunction('', '');
            $module->addRightFunction(_('New Direct &Invoice'), '/sales/sales_order_entry?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESINVOICE, SA_SALESINVOICE);
            $module->addRightFunction(_('Search Invoices and D&eliveries'), '/sales/inquiry/customer_inquiry?', SA_SALESTRANSVIEW);
            $module->addRightFunction(_('New Invoice from &Template '), '/sales/inquiry/sales_orders_view?InvoiceTemplates=Yes', SA_SALESINVOICE);
            $module->addRightFunction(_('Manage Recurrent &Invoices'), '/sales/manage/recurrent_invoices?', SA_SRECURRENT);
            $module->addRightFunction(_('&Create and Print Recurrent Invoices'), '/sales/create_recurrent_invoices?', SA_SALESINVOICE);
            $module->addRightFunction('', '');
            $module = $this->add_module(_('Payments and Customers'));
            $module->addLeftFunction('', '');
            $module->addLeftFunction(_('New Customer &Payment'), '/sales/customer_payments?', SA_SALESPAYMNT);
          //  $module->addLeftFunction(_('New Customer &Refund'), '/sales/customer_refunds?', SA_SALESREFUND);
            $module->addLeftFunction(_('New Customer &Credit'), '/sales/credit_note_entry?NewCredit=Yes', SA_SALESCREDIT);
            $module->addLeftFunction(_('&Allocate Customer Payments or Credits'), '/sales/allocations/customer_allocation_main?', SA_SALESALLOC);
            $module->addLeftFunction(_('Search Customer A&llocations'), '/sales/inquiry/customer_allocation_inquiry?', SA_SALESALLOC);
            $module->addLeftFunction('', '');
            $module->addRightFunction(_('Add and Manage &Customers'), '/contacts/customers', SA_CUSTOMER);
            $module->addRightFunction(_('Customer and Sales &Reports'), 'reporting/reports_main?Class=0', SA_SALESTRANSVIEW);
            $module->addRightFunction(_('Email Customer Statements'), '/sales/email_statements', SA_SALESTRANSVIEW);
            $module = $this->add_module(_('Sales Setup'));
            $module->addLeftFunction(_('Credit &Statuses'), '/sales/manage/credit_status?', SA_CRSTATUS);
            $module->addLeftFunction(_('Sales T&ypes'), '/sales/manage/sales_types?', SA_SALESTYPES);
            $module->addRightFunction(_('Sales &Persons'), '/sales/manage/sales_people?', SA_SALESMAN);
            $module->addRightFunction(_('Sales &Areas'), '/sales/manage/sales_areas?', SA_SALESAREA);
            $module->addRightFunction(_('Sales &Groups'), '/sales/manage/sales_groups?', SA_SALESGROUP);
        }
    }

