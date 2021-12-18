<?php

namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\Repository\Values\Content\ContentProxy;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ExceptionController.
 */
class ExceptionController extends AbstractController
{

    /**
     * @var SearchHelper
     */
    private $searchHelper;

    public function __construct(
        SearchHelper $searchHelper
    ) {
        $this->searchHelper = $searchHelper;
    }
    /**
     * @return Response
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $code = $exception->getStatusCode();

        $homePage = $this->searchHelper->locationsList(
            1,
            ['homepage'],
            [],
            1
        );


        $homePage = current($homePage);
        /* @var ContentProxy $homePageContent */
        $homePageContent = $homePage->getContent();

        $kitchens = $this->searchHelper->getTargetOfRelations(
            $homePageContent,
            'kitchen_relation',
            true,
            false
        );


        $response =  new Response($this->renderView(
            'error/error.html.twig',
            [
                'code' => $code,
                'content' => $homePageContent,
                'kitchens' => $kitchens
            ]
        ), $code);
        return $response;
    }
}
