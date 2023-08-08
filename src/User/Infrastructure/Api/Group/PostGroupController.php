<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Group;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Symfony\ApiController;
use App\Shared\Infrastructure\Symfony\ApiExceptionsHttpStatusCodeMapping;
use App\User\Application\Command\CreateGroupCommand;
use App\User\Domain\Repository\Exceptions\GroupAlreadyExistException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

class PostGroupController extends ApiController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $messageBus, ApiExceptionsHttpStatusCodeMapping $exceptionHandler)
    {
        $this->messageBus = $messageBus;
        parent::__construct($exceptionHandler);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $groupId = Uuid::random()->value();
        $name = (string)$request->get('name');

        if (!$name) {
            throw new InvalidArgumentException('Unexpected API error');
        }

        $this->handle(new CreateGroupCommand($groupId, $name));

        return $this->json([
            'id' => $groupId,
            'name' => $name,
        ], Response::HTTP_CREATED, ['location' => sprintf('/groups/%s', $groupId)]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [GroupAlreadyExistException::class => Response::HTTP_CONFLICT];
    }
}
