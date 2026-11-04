<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Le Caddy hôte ne pose aucun en-tête : sans `frame-ancestors`, les boutons du back office, des POST en un clic, sont
 * encadrables. `isSecure()` reste fiable derrière Caddy grâce à `SYMFONY_TRUSTED_PROXIES`.
 */
final readonly class SecurityHeadersListener
{
    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function addHeaders(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($event->getRequest()->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
    }
}
