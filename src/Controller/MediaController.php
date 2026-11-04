<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Preview\LinkBannerStore;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MediaController extends AbstractController
{
    /**
     * Visuels conservés par Radar, réservés aux membres connectés (R1) quel que soit l'endroit où ils sont stockés.
     * Un chemin désigne un contenu figé (hash de l'adresse source) : le navigateur le garde un an.
     */
    #[Route(
        path: '/media/{path}',
        name: 'app_media',
        requirements: ['path' => '(previews/[a-f0-9]{64}\.webp|favicons/[a-f0-9]{64}\.png)'],
        methods: ['GET'],
    )]
    public function show(string $path, FilesystemOperator $mediaStorage): Response
    {
        $response = $this->storedFile($path, str_ends_with($path, '.webp') ? 'image/webp' : 'image/png', $mediaStorage);
        $response->setPrivate();
        $response->setMaxAge(31_536_000);
        $response->setImmutable();

        return $response;
    }

    /** Bandeau d'une annonce Chat, lu par Google sans compte. Une relance le réécrit : le contenu n'est pas figé. */
    #[Route(
        path: '/annonces/bandeaux/{name}.jpg',
        name: 'app_announcement_banner',
        requirements: ['name' => '[a-f0-9]{64}'],
        methods: ['GET'],
    )]
    public function banner(string $name, FilesystemOperator $mediaStorage): Response
    {
        $response = $this->storedFile(LinkBannerStore::path($name), 'image/jpeg', $mediaStorage);
        $response->setPublic();
        $response->setMaxAge(86_400);

        return $response;
    }

    private function storedFile(string $path, string $contentType, FilesystemOperator $mediaStorage): StreamedResponse
    {
        try {
            if (!$mediaStorage->fileExists($path)) {
                throw $this->createNotFoundException();
            }

            $stream = $mediaStorage->readStream($path);
        } catch (FilesystemException) {
            throw $this->createNotFoundException();
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
