<?php

declare(strict_types=1);

namespace App\Exception;

/** Flux injoignable ou illisible ; le message s'affiche tel quel à l'admin. */
final class InvalidFeedException extends \RuntimeException {}
