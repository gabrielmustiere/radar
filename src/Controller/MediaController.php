<?php

declare(strict_types=1);

namespace App\Controller;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Visuels conservés par Radar, réservés aux membres connectés (R1) quel que soit l'endroit où ils sont stockés.
 * Un chemin désigne un contenu figé (hash de l'adresse source) : le navigateur le garde un an.
 */
final class MediaController extends AbstractController
{
    #[Route(
        path: '/media/{path}',
        name: 'app_media',
        requirements: ['path' => '(previews/[a-f0-9]{64}\.webp|favicons/[a-f0-9]{64}\.png)'],
        methods: ['GET'],
    )]
    public function show(string $path, FilesystemOperator $mediaStorage): Response
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
        $response->headers->set('Content-Type', str_ends_with($path, '.webp') ? 'image/webp' : 'image/png');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();
        $response->setMaxAge(31_536_000);
        $response->setImmutable();

        return $response;
    }
}
