<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;

class MockHubInterface implements HubInterface
{
    public function publish(Update $update): string
    {
        return 'mock-id';
    }

    public function getUrl(): string
    {
        return 'https://chatapp.amirabedini.net/.well-known/mercure';
    }

    public function getPublicUrl(): string
    {
        return 'https://chatapp.amirabedini.net/.well-known/mercure';
    }

    public function getProvider(): TokenProviderInterface
    {
        throw new \RuntimeException('Mock hub does not support token provider');
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }
}
