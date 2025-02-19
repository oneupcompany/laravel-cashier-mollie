<?php

namespace Laravel\Cashier;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Coupon\AppliedCoupon;
use Laravel\Cashier\Coupon\RedeemedCoupon;
use Laravel\Cashier\Credit\Credit;
use Laravel\Cashier\Order\Order;
use Laravel\Cashier\Order\OrderItem;
use Laravel\Cashier\Refunds\Refund;
use Laravel\Cashier\Refunds\RefundItem;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;

class Cashier
{
    /**
     * The current currency.
     *
     * @var string
     */
    protected static $currency = 'eur';

    /**
     * The current currency symbol.
     *
     * @var string
     */
    protected static $currencySymbol = '€';

    /**
     * The current currency symbol.
     *
     * @var string
     */
    protected static $currencyLocale = 'de_DE';

    /**
     * The custom currency formatter.
     *
     * @var callable
     */
    protected static $formatCurrencyUsing;

    /**
     * Indicates if Cashier migrations will be run.
     *
     * @var bool
     */
    public static $runsMigrations = true;

    /**
     * Indicates if Cashier routes will be registered.
     *
     * @var bool
     */
    public static $registersRoutes = true;

    /**
     * The subscription model class name.
     *
     * @var string
     */
    public static $subscriptionModel = Subscription::class;

    /**
     * The order model class name.
     *
     * @var string
     */
    public static $orderModel = Order::class;

    /**
     * The orderItem model class name.
     *
     * @var string
     */
    public static $orderItemModel = OrderItem::class;

    /**
     * The appliedCoupon model class name.
     *
     * @var string
     */
    public static $appliedCouponModel = AppliedCoupon::class;

    /**
     * The redeemedCoupon model class name.
     *
     * @var string
     */
    public static $redeemedCouponModel = RedeemedCoupon::class;

    /**
     * The credit model class name.
     *
     * @var string
     */
    public static $creditModel = Credit::class;

    /**
     * The payment model class name.
     *
     * @var string
     */
    public static $paymentModel = Payment::class;

    /**
     * The refund model class name.
     *
     * @var string
     */
    public static $refundModel = Refund::class;

    /**
     * The refund model class name.
     *
     * @var string
     */
    public static $refundItemModel = RefundItem::class;

    /**
     * Process scheduled OrderItems
     *
     * @return \Illuminate\Support\Collection
     */
    public static function run()
    {
        $items = static::$orderItemModel::shouldProcess()->get();

        $orders = $items->chunkByOwnerAndCurrency()->map(function ($chunk) {
            return static::$orderModel::createFromItems($chunk)->processPayment();
        });

        return $orders;
    }

    /**
     * Set the default currency for this merchant.
     *
     * @param  string  $currency
     * @param  string|null  $symbol
     * @return void
     * @throws \Exception
     */
    public static function useCurrency($currency, $symbol = null)
    {
        static::$currency = $currency;

        static::useCurrencySymbol($symbol ?: static::guessCurrencySymbol($currency));
    }

    /**
     * Set the currency symbol to be used when formatting currency.
     *
     * @param  string  $symbol
     * @return void
     */
    public static function useCurrencySymbol($symbol)
    {
        static::$currencySymbol = $symbol;
    }

    /**
     * Set the currency locale to be used when formatting currency.
     *
     * @param  string  $locale
     * @return void
     */
    public static function useCurrencyLocale($locale)
    {
        static::$currencyLocale = $locale;
    }

    /**
     * Guess the currency symbol for the given currency.
     *
     * @param  string  $currency
     * @return string
     * @throws \Exception
     */
    protected static function guessCurrencySymbol($currency)
    {
        switch (strtolower($currency)) {
            case 'usd':
            case 'aud':
            case 'cad':
                return '$';
            case 'eur':
                return '€';
            case 'gbp':
                return '£';
            default:
                throw new Exception('Unable to guess symbol for currency. Please explicitly specify it.');
        }
    }

    /**
     * Get the currency currently in use.
     *
     * @return string
     */
    public static function usesCurrency()
    {
        return static::$currency;
    }

    /**
     * Get the currency symbol currently in use.
     *
     * @return string
     */
    public static function usesCurrencySymbol()
    {
        return static::$currencySymbol;
    }

    /**
     * Get the currency locale currently in use.
     *
     * @return string
     */
    public static function usesCurrencyLocale()
    {
        return static::$currencyLocale;
    }

    /**
     * Set the custom currency formatter.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function formatCurrencyUsing(callable $callback)
    {
        static::$formatCurrencyUsing = $callback;
    }

    /**
     * Format the given amount into a displayable currency.
     *
     * @param \Money\Money $money
     * @return string
     */
    public static function formatAmount(Money $money)
    {
        if (static::$formatCurrencyUsing) {
            return call_user_func(static::$formatCurrencyUsing, $money);
        }

        $numberFormatter = new \NumberFormatter(static::$currencyLocale, \NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, new ISOCurrencies);

        return $moneyFormatter->format($money);
    }

    /**
     * Set the subscription model class name.
     *
     * @param  string  $subscriptionModel
     * @return void
     */
    public static function useSubscriptionModel($subscriptionModel)
    {
        static::$subscriptionModel = $subscriptionModel;
    }

    /**
     * Set the order model class name.
     *
     * @param  ordertring  $customerModel
     * @return void
     */
    public static function useOrderModel($orderModel)
    {
        static::$orderModel = $orderModel;
    }

    /**
     * Set the orderItem model class name.
     *
     * @param  string  $orderItemModel
     * @return void
     */
    public static function useOrderItemModel($orderItemModel)
    {
        static::$orderItemModel = $orderItemModel;
    }

    /**
     * Set the appliedCoupon model class name.
     *
     * @param  string  $appliedCouponModel
     * @return void
     */
    public static function useAppliedCouponModel($appliedCouponModel)
    {
        static::$appliedCouponModel = $appliedCouponModel;
    }

    /**
     * Set the redeemedCoupon model class name.
     *
     * @param  string  $redeemedCouponModel
     * @return void
     */
    public static function useRedeemedCouponModel($redeemedCouponModel)
    {
        static::$redeemedCouponModel = $redeemedCouponModel;
    }

    /**
     * Set the credit model class name.
     *
     * @param  string  $creditModel
     * @return void
     */
    public static function useCreditModel($creditModel)
    {
        static::$creditModel = $creditModel;
    }

    /**
     * Set the payment model class name.
     *
     * @param  string  $paymentModel
     * @return void
     */
    public static function usePaymentModel($paymentModel)
    {
        static::$paymentModel = $paymentModel;
    }

    /**
     * Set the refund model class name.
     *
     * @param  string  $refundModel
     * @return void
     */
    public static function useRefundModel($refundModel)
    {
        static::$refundModel = $refundModel;
    }

    /**
     * Set the refundItem model class name.
     *
     * @param  string  $refundItemModel
     * @return void
     */
    public static function useRefundItemModel($refundItemModel)
    {
        static::$refundItemModel = $refundItemModel;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $owner
     * @return null|string
     */
    public static function getLocale(Model $owner)
    {
        if (method_exists($owner, 'getLocale')) {
            $locale = $owner->getLocale();

            if (! empty($locale)) {
                return $locale;
            }
        }

        return config('cashier.locale');
    }

    /**
     * Get the webhook relative url.
     *
     * @return string
     */
    public static function webhookUrl()
    {
        return self::pathFromUrl(config('cashier.webhook_url'));
    }

    /**
     * Get the first payment webhook relative url.
     *
     * @return string
     */
    public static function firstPaymentWebhookUrl()
    {
        return self::pathFromUrl(config('cashier.first_payment.webhook_url'));
    }

    /**
     * Get the aftercare webhook relative url.
     *
     * @return string
     */
    public static function aftercareWebhookUrl()
    {
        return self::pathFromUrl(config('cashier.aftercare_webhook_url'));
    }

    /**
     * Configure Cashier to not register its migrations.
     *
     * @return static
     */
    public static function ignoreMigrations()
    {
        static::$runsMigrations = false;

        return new static;
    }

    /**
     * Configure Cashier to not register its routes.
     *
     * @return static
     */
    public static function ignoreRoutes()
    {
        static::$registersRoutes = false;

        return new static;
    }

    /**
     * Get path from url.
     *
     * @param string $url
     * @return string
     */
    protected static function pathFromUrl($url)
    {
        $url_parts = parse_url($url);

        return preg_replace('/^\//', '', $url_parts['path']);
    }
}
