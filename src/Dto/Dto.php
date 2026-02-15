<?php

namespace App\Dto;

use flight\net\Request;

abstract class Dto
{
    /**
     * Create DTO from Flight Request object.
     * Extracts data from request, validates it, and returns DTO instance.
     *
     * @param Request $request
     * @return static
     * @throws \App\Exception\AppException if validation fails
     */
    abstract public static function createFromRequest(Request $request): self;
}
