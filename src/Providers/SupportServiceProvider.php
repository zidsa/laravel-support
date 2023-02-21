<?php

declare(strict_types=1);

namespace Rinvex\Support\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Rinvex\Support\Validator\Validator as RinvexValidator;

class SupportServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../lang',
            'uniquewith-validator'
        );

        if (method_exists($this->app->translator, 'trans')) {
            $message = $this->app->translator->trans('uniquewith-validator::validation.unique_with');
        } else {
            $message = $this->app->translator->get('uniquewith-validator::validation.unique_with');
        }
        $this->app->validator->extend('unique_with', RinvexValidator::class . '@validateUniqueWith', $message);
        $this->app->validator->replacer('unique_with', function () {
            // Since 5.4.20, the validator is passed in as the 5th parameter.
            // In order to preserve backwards compatibility, we check if the
            // validator is passed and use the validator's translator instead
            // of getting it out of the container.
            $arguments = func_get_args();
            if (sizeof($arguments) >= 5) {
                $arguments[4] = $arguments[4]->getTranslator();
            } else {
                $arguments[4] = $this->app->translator;
            }

            return call_user_func_array([new RinvexValidator(), 'replaceUniqueWith'], $arguments);
        });

        // Add strip_tags validation rule
        Validator::extend('strip_tags', function ($attribute, $value) {
            return is_string($value) && strip_tags($value) === $value;
        }, trans('validation.invalid_strip_tags'));

        // Add time offset validation rule
        Validator::extend('timeoffset', function ($attribute, $value) {
            return array_key_exists($value, timeoffsets());
        }, trans('validation.invalid_timeoffset'));

        Collection::macro('similar', function (Collection $newCollection) {
            return $newCollection->diff($this)->isEmpty() && $this->diff($newCollection)->isEmpty();
        });
    }
}
