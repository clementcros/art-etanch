<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Symfony\Component\HttpFoundation\Request;

class ListKitchenController extends Controller
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
    public function fullAction(ContentView $view) : ContentView
    {

        $kitchens = $this->searchHelper->locationsList(
            $view->getLocation()->id,
            ['kitchen'],
            []
        );
        $kitchens = $this->getMenuLocationList($kitchens);

        $view->addParameters([
            'kitchens' => $kitchens,
        ]);


        return $view;
    }

    /**
     * @param $list
     * @return array
     */
    private function getMenuLocationList($list) {
        $data = array();
        foreach ($list as $menuList) {
            array_push($data,$menuList->getContent());
        }
        return $data;
    }
}

