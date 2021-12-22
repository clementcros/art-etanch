<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;

class HomeController extends Controller
{

    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        SearchHelper $searchHelper
    )
    {
        $this->searchHelper = $searchHelper;
    }

    /**
     * @param ContentView $view
     * @return ContentView
     */
    public function fullAction(ContentView $view): ContentView
    {

        $kitchens = $this->searchHelper->getTargetOfRelations(
            $view->getContent(),
            'kitchen_relation',
            true,
            false
        );

        $kitchensFamily = $this->searchHelper->getTargetOfRelations(
            $view->getContent(),
            'kitchen_family_relation',
            true,
            false
        );

        $creations = $this->searchHelper->getTargetOfRelations(
            $view->getContent(),
            'creation_relation',
            true,
            false
        );

        $view->addParameters([
            'kitchens' => $kitchens,
            'kitchensFamily' => $kitchensFamily,
            'creations' => $creations,
        ]);

        return $view;
    }
}
