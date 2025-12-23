<?php

namespace App\Domain;

/**
 * Domain exceptions
 */

class InvalidLicenseStateException extends \DomainException
{
}

class LicenseKeyNotFoundException extends \DomainException
{
}

class LicenseNotFoundException extends \DomainException
{
}

class ProductNotFoundException extends \DomainException
{
}

class BrandNotFoundException extends \DomainException
{
}

class DuplicateLicenseException extends \DomainException
{
}

class InvalidBrandException extends \DomainException
{
}

class UnauthorizedException extends \DomainException
{
}

class SeatLimitExceededException extends \DomainException
{
}
