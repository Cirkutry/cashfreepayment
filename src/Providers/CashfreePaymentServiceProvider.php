<?php

namespace Azuriom\Plugin\CashfreePayment\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Plugin\CashfreePayment\CashfreePaymentMethod;

class CashfreePaymentServiceProvider extends BasePluginServiceProvider
{
    /**
     * Bootstrap any plugin services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadRoutes();
        
        payment_manager()->registerPaymentMethod('cashfree', CashfreePaymentMethod::class);
    }
}
