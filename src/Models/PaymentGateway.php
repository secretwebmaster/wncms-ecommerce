<?php

namespace Secretwebmaster\WncmsEcommerce\Models;

use Wncms\Translatable\Traits\HasTranslations;
use Wncms\Models\BaseModel;

class PaymentGateway extends BaseModel
{
    use HasTranslations;

    public static $packageId = 'wncms-ecommerce';
    public static $modelKey = 'payment_gateway';

    protected $table = 'payment_gateways';
    protected $guarded = [];
    protected $translatable = ['name', 'description'];

    protected $casts = [
        'attributes' => 'array',
        'is_sandbox' => 'boolean',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-hand-holding-dollar',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getParameter(string $key, $default = null)
    {
        $attrs = $this->getAttributeValue('attributes') ?: [];
        return data_get($attrs, $key, $default);
    }

    public function getDriverAttribute($value): string
    {
        $driver = trim((string) $value);
        if ($driver !== '') {
            return $driver;
        }

        return trim((string) ($this->attributes['slug'] ?? ''));
    }

    public function processor()
    {
        $driver = ucfirst($this->driver ?: $this->slug ?: $this->type);
        foreach ($this->processorNamespaces() as $namespace) {
            $className = rtrim($namespace, '\\') . '\\' . $driver;
            if (class_exists($className)) {
                return new $className($this);
            }
        }

        return null;
    }

    protected function processorNamespaces(): array
    {
        $configured = config('wncms-ecommerce.processor_namespaces', []);
        if (!is_array($configured)) {
            $configured = [];
        }

        // App namespace comes first so project-level gateways override package defaults.
        $fallback = [
            'App\\PaymentGateways',
            'Secretwebmaster\\WncmsEcommerce\\PaymentGateways',
        ];

        return array_values(array_unique(array_filter(array_merge($configured, $fallback))));
    }

    /**
     * Dynamic notify callback URL based on current slug.
     */
    public function getNotifyUrl(): ?string
    {
        $slug = trim((string) $this->slug);
        if ($slug === '') {
            return null;
        }

        try {
            return route('api.v1.payment.notify.gateway', ['payment_gateway' => $slug]);
        } catch (\Throwable $e) {
            return rtrim(url('/v1/payment/notify'), '/') . '/' . rawurlencode($slug);
        }
    }

    /**
     * Build notify callback URL for arbitrary slug.
     */
    public static function buildNotifyUrl(?string $slug): ?string
    {
        $gateway = new static();
        $gateway->slug = trim((string) $slug);
        return $gateway->getNotifyUrl();
    }

    /**
     * Template URL used in backend form preview.
     */
    public static function buildNotifyUrlTemplate(): string
    {
        return rtrim(url('/v1/payment/notify'), '/') . '/{slug}';
    }
}
