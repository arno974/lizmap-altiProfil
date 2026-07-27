<?php

namespace AltiProfil\Elevation;

/**
 * Thrown when the client input is invalid (non-numeric coordinates, negative
 * sampling, …). Distinct from ServiceException so the controller can
 * map it to a different message/status: bad request vs upstream failure.
 */
class InvalidRequestException extends \Exception
{
}
