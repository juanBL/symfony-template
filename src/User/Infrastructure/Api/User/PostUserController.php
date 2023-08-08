<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\User;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Symfony\ApiController;
use App\Shared\Infrastructure\Symfony\ApiExceptionsHttpStatusCodeMapping;
use App\User\Application\Command\CreateUserCommand;
use App\User\Domain\Repository\Exceptions\UserAlreadyExistException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class PostUserController extends ApiController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus, ApiExceptionsHttpStatusCodeMapping $exceptionHandler)
    {
        $this->messageBus = $messageBus;
        parent::__construct($exceptionHandler);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $userId = Uuid::random()->value();
        $name = (string)$request->get('name');

        if (!$name) {
            throw new InvalidArgumentException('Unexpected API error');
        }

        $this->handle(new CreateUserCommand($userId, $name));

        return $this->json([
            'id' => $userId,
            'name' => $name,
        ], Response::HTTP_CREATED, ['location' => sprintf('/users/%s', $userId)]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [UserAlreadyExistException::class => Response::HTTP_CONFLICT];
    }
}
