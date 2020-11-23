<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a factory to create and initialize Request object for a slug request
 * and copy data from handled slug Request object to main Request object if it is needed.
 */
interface SlugRequestFactoryInterface
{
    /**
     * Creates and initializes Request object for a slug request.
     *
     * @param Request $request
     *
     * @return Request
     */
    public function createSlugRequest(Request $request): Request;

    /**
     * Copies data from handled slug Request object to main Request object.
     *
     * @param Request $request
     * @param Request $slugRequest
     */
    public function updateMainRequest(Request $request, Request $slugRequest): void;
}
