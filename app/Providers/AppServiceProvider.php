<?php

namespace App\Providers;

use App\Domain\Scolarite\Livewire\AbsenceSaisie;
use App\Domain\Scolarite\Livewire\ClassesListe;
use App\Domain\Scolarite\Livewire\EleveForm;
use App\Domain\Scolarite\Livewire\ElevesListe;
use App\Domain\SuperAdmin\Livewire\AnalyticsDashboard;
use App\Domain\SuperAdmin\Livewire\Communication;
use App\Domain\SuperAdmin\Livewire\EcoleDetail;
use App\Domain\SuperAdmin\Livewire\EcolesListe;
use App\Domain\SuperAdmin\Livewire\Onboarding;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Livewire's auto-discovery only resolves classes under config('livewire.class_namespace')
        // ("App\Livewire"). Our components live under app/Domain/*/Livewire (§4.2) instead, so the
        // initial full-page GET renders fine (resolved directly by class via the route), but every
        // subsequent AJAX update fails to re-resolve the component by its snapshot name and Livewire
        // reports it as a stale "release token" mismatch (419 Page Expired) instead of a clear error.
        // Registering an explicit alias here makes name<->class resolution symmetric in both directions.
        Livewire::component('eleves-liste', ElevesListe::class);
        Livewire::component('eleve-form', EleveForm::class);
        Livewire::component('classes-liste', ClassesListe::class);
        Livewire::component('absence-saisie', AbsenceSaisie::class);

        Livewire::component('admin-ecoles-liste', EcolesListe::class);
        Livewire::component('admin-ecole-detail', EcoleDetail::class);
        Livewire::component('admin-onboarding', Onboarding::class);
        Livewire::component('admin-analytics-dashboard', AnalyticsDashboard::class);
        Livewire::component('admin-communication', Communication::class);

        // Livewire's own /livewire/update route only carries the base 'web' middleware
        // (cf. HandleRequests::boot()), so it never runs ResolveTenant — every AJAX
        // update (search, wire:click actions, re-renders) was executing without
        // app('currentTenantId') bound and without spatie's team context set, silently
        // hiding role-gated buttons and breaking $this->authorize() in action methods.
        // 'role' is deliberately NOT added here: this route is shared by every Livewire
        // component in the app, and role scoping already happens per-page in web.php.
        //
        // The portail Super Admin (admin.plateforme.sn) never runs ResolveTenant either
        // (no etablissement courante, §15.6) — it needs platform.team instead so that
        // spatie's team context is set to Etablissement::PLATFORM_TEAM_ID on every AJAX
        // round-trip, not just the first GET (cf. EnsurePlatformTeam). Registering the
        // admin-domain route BEFORE the generic one matters: Laravel's router tries
        // routes in registration order and the generic route below has no domain
        // constraint, so it would swallow admin-subdomain requests first if it came first.
        Livewire::setUpdateRoute(fn ($handle) => Route::domain(config('app.admin_subdomain'))
            ->post('/livewire/update', $handle)
            ->middleware(['web', 'auth', 'platform.team']));

        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware(['web', 'auth', 'resolve.tenant']));
    }
}
