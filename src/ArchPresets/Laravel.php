<?php

declare (strict_types=1);
namespace Pest\Arch_Presets;

use Throwable;
/**
 * @internal
 */
final class Laravel extends Abstract_Preset
{
    /**
     * Executes the arch preset.
     */
    public function execute(): void
    {
        $this->expectations[] = expect('App\Traits')->to_be_traits();
        $this->expectations[] = expect('App\Concerns')->to_be_traits();
        $this->expectations[] = expect('App')->not->to_be_enums()->ignoring('App\Enums');
        $this->expectations[] = expect('App\Enums')->to_be_enums()->ignoring('App\Enums\Concerns');
        $this->expectations[] = expect('App\Features')->to_be_classes()->ignoring('App\Features\Concerns');
        $this->expectations[] = expect('App\Features')->to_have_method('resolve')->ignoring('App\Features\Concerns');
        $this->expectations[] = expect('App\Exceptions')->classes()->to_implement('Throwable')->ignoring('App\Exceptions\Handler');
        $this->expectations[] = expect('App')->not->to_implement(Throwable::class)->ignoring('App\Exceptions');
        $this->expectations[] = expect('App\Http\Middleware')->classes()->to_have_method('handle');
        $this->expectations[] = expect('App\Models')->classes()->to_extend('Illuminate\Database\Eloquent\Model')->ignoring('App\Models\Scopes');
        $this->expectations[] = expect('App\Models')->classes()->not->to_have_suffix('Model');
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Database\Eloquent\Model')->ignoring('App\Models');
        $this->expectations[] = expect('App\Http\Requests')->classes()->to_have_suffix('Request');
        $this->expectations[] = expect('App\Http\Requests')->to_extend('Illuminate\Foundation\Http\FormRequest');
        $this->expectations[] = expect('App\Http\Requests')->to_have_method('rules');
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Foundation\Http\FormRequest')->ignoring('App\Http\Requests');
        $this->expectations[] = expect('App\Console\Commands')->classes()->to_have_suffix('Command');
        $this->expectations[] = expect('App\Console\Commands')->classes()->to_extend('Illuminate\Console\Command');
        $this->expectations[] = expect('App\Console\Commands')->classes()->to_have_method('handle');
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Console\Command')->ignoring('App\Console\Commands');
        $this->expectations[] = expect('App\Mail')->classes()->to_extend('Illuminate\Mail\Mailable');
        $this->expectations[] = expect('App\Mail')->classes()->to_implement('Illuminate\Contracts\Queue\ShouldQueue');
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Mail\Mailable')->ignoring('App\Mail');
        $this->expectations[] = expect('App\Jobs')->classes()->to_implement('Illuminate\Contracts\Queue\ShouldQueue');
        $this->expectations[] = expect('App\Jobs')->classes()->to_have_method('handle');
        $this->expectations[] = expect('App\Listeners')->to_have_method('handle');
        $this->expectations[] = expect('App\Notifications')->to_extend('Illuminate\Notifications\Notification');
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Notifications\Notification')->ignoring('App\Notifications');
        $this->expectations[] = expect('App\Providers')->to_have_suffix('ServiceProvider');
        $this->expectations[] = expect('App\Providers')->to_extend('Illuminate\Support\ServiceProvider');
        $this->expectations[] = expect('App\Providers')->not->to_be_used();
        $this->expectations[] = expect('App')->not->to_extend('Illuminate\Support\ServiceProvider')->ignoring('App\Providers');
        $this->expectations[] = expect('App')->not->to_have_suffix('ServiceProvider')->ignoring('App\Providers');
        $this->expectations[] = expect('App')->not->to_have_suffix('Controller')->ignoring('App\Http\Controllers');
        $this->expectations[] = expect('App\Http\Controllers')->classes()->to_have_suffix('Controller');
        $this->expectations[] = expect('App\Http')->to_only_be_used_in(['App\Http', 'App\Providers']);
        $this->expectations[] = expect('App\Http\Controllers')->not->to_have_public_methods_besides(['__construct', '__invoke', 'index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'middleware']);
        $this->expectations[] = expect(['dd', 'ddd', 'dump', 'env', 'exit', 'ray'])->not->to_be_used();
        $this->expectations[] = expect('App\Policies')->classes()->to_have_suffix('Policy');
        $this->expectations[] = expect('App\Attributes')->classes()->to_implement('Illuminate\Contracts\Container\ContextualAttribute')->to_have_attribute('Attribute')->to_have_method('resolve');
    }
}