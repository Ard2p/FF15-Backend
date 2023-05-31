<?php

namespace App\Vendors\socialiteproviders\rso;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RSOExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('rso', Provider::class);
    }
}
