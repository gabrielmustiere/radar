<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Le `login_throttling` natif ne voit pas ces échecs : le handler rejette la clé avant que le passeport n'existe, donc
 * sans `CheckPassportEvent`. Les refus sont comptés ici, et l'IP bloquée avant même le pare-feu.
 */
final readonly class ExtensionApiThrottleListener
{
    public function __construct(
        #[Autowire(service: 'limiter.extension_api_failure')]
        private RateLimiterFactoryInterface $limiterFactory,
    ) {}

    /** Avant le pare-feu (priorité 8). */
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 16)]
    public function rejectThrottledClient(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ($this->limiterFactory->create($request->getClientIp())->consume(0)->getRemainingTokens() > 0) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'detail' => 'Trop de clés refusées depuis cette adresse : réessayez dans quelques minutes.',
        ], Response::HTTP_TOO_MANY_REQUESTS));
    }

    #[AsEventListener(dispatcher: 'security.event_dispatcher.api')]
    public function countFailure(LoginFailureEvent $event): void
    {
        $this->limiterFactory->create($event->getRequest()->getClientIp())->consume();
    }
}
