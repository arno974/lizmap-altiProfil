<?php

namespace AltiProfil\Elevation;

/**
 * Thrown when an elevation backend fails to produce a result: IGN returned a
 * non-200 status, a malformed body, a database error, an unknown provider, …
 */
class ServiceException extends \Exception
{
}
