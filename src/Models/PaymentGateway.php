<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Wncms\Models\BaseModel;

class PaymentGateway extends BaseModel
{

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms-ecommerce';

    public static $modelKey = 'payment_gateway';

    protected $table = 'payment_gateways';

    protected $guarded = [];

    protected $casts = [
        'attributes' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-hand-holding-dollar'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * Get the display name of the payment gateway.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Methods
     * ----------------------------------------------------------------------------------------------------
     */

    /**
     * Get a specific parameter value.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * Instantiate the payment gateway processor class
     */
    public function processor()
    {
        $class = 'Secretwebmaster\\WncmsEcommerce\\PaymentGateways\\' . ucfirst($this->slug);
        if (class_exists($class)) {
            return new $class($this);
        }

        dd(
            $class,
            class_exists($class),
        );

        $class = 'App\\PaymentGateways\\' . ucfirst($this->type);
        if (class_exists($class)) {
            return new $class($this);
        }

        return null;
    }
}
