<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;

class KitchenController extends Controller
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

        $imageRelations = $this->searchHelper->locationsList(
            $view->getLocation()->id,
            ['image'],
            []
        );

        $images = $this->getContentLocationList($imageRelations);

        $kitchensRelations = $this->searchHelper->getTargetOfRelations(
            $view->getContent(),
            'kitchen_relation',
            true,
            false
        );

        $fileDownload = $this->searchHelper->locationsList(
            $view->getLocation()->id,
            ['file_download'],
            []
        );

        $fileDownload = $this->getContentLocationList($fileDownload);


        $view->addParameters([
            'images' => $images,
            'kitchensRelations' => $kitchensRelations,
            'files' => $fileDownload
        ]);

        return $view;
    }

    /**
     * @param $list
     * @return array
     */
    private function getContentLocationList($list) {
        $data = array();
        foreach ($list as $menuList) {
            array_push($data,$menuList->getContent());
        }
        return $data;
    }

}