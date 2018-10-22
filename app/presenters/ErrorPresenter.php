<?php

namespace App\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;

class ErrorPresenter implements Nette\Application\IPresenter
{


    /** @var ILogger */
    private $logger;

    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }

    public function run(Nette\Application\Request $request)
    {
        $exception = $request->getParameter('exception');

        if ($exception instanceof Nette\Application\BadRequestException ||
            $exception instanceof Nette\Application\ForbiddenRequestException) {
            return new Responses\JsonResponse([
                'status' => $exception->getCode(),
                'message' => $exception->getMessage()
            ]);
        }


        $this->logger->log($exception, ILogger::EXCEPTION);

        return new Responses\JsonResponse([
            'status' => Http\IResponse::S500_INTERNAL_SERVER_ERROR,
            'message' => $exception->getMessage()
        ]);
    }

}
