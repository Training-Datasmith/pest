<?php

declare (strict_types=1);
namespace Pest\Factories\Concerns;

use Pest\Support\Higher_Order_Message_Collection;
trait Higher_Orderable
{
    /**
     * The higher order messages that are chainable.
     */
    public Higher_Order_Message_Collection $chains;
    /**
     * The higher order messages that are "factory" proxyable.
     */
    public Higher_Order_Message_Collection $factory_proxies;
    /**
     * The higher order messages that are proxyable.
     */
    public Higher_Order_Message_Collection $proxies;
    /**
     * Boot the higher order properties.
     */
    private function boot_higher_orderable(): void
    {
        $this->chains = new Higher_Order_Message_Collection();
        $this->factory_proxies = new Higher_Order_Message_Collection();
        $this->proxies = new Higher_Order_Message_Collection();
    }
}