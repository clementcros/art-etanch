<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\Repository\Values\Content\Location;
use Symfony\Component\HttpFoundation\Response;

class LayoutController extends Controller
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
     * @return Response
     */
    public function headerAction($currentLocationId): Response
    {

        $rootLocation = $this->getRootLocation();
        $headerLocation = $this->searchHelper->locationsList(
            $rootLocation->id,
            ['list_menu'],
            [],
            1
        );

        /** @var Location $headerLocation */
        $headerLocation = current($headerLocation);
        $headerContent = $headerLocation->getContent();

        $headerLogo = $this->searchHelper->locationsList(
            null,
            ['logo_menu'],
            [],
            1
        );


        /** @var Location $headerLocation */
        $headerLogo = current($headerLogo);
        $headerLogo = $headerLogo->getContent();

        $menuLists = $this->searchHelper->locationsList(
            $headerLocation->id,
            ['menu', 'menu_picto'],
            []
        );
        $menu = $this->getMenuLocationList($menuLists);
        $response = new Response();
        return $this->render(
            '::menuLayout.html.twig',
            [
                'menus' => $menu,
                'logo' => $headerLogo,
            ],
            $response
        );

    }

    public function footerAction($currentLocationId): Response
    {
        $rootLocation = $this->getRootLocation();
        $headerLocation = $this->searchHelper->locationsList(
            $rootLocation->id,
            ['list_footer'],
            [],
            1
        );

        /** @var Location $headerLocation */
        $headerLocation = current($headerLocation);
        $headerContent = $headerLocation->getContent();

        $headerLogo = $this->searchHelper->locationsList(
            null,
            ['logo_menu'],
            [],
            1
        );


        /** @var Location $headerLocation */
        $headerLogo = current($headerLogo);
        $logo = $headerLogo->getContent();

        $menuLists = $this->searchHelper->locationsList(
            $headerLocation->id,
            ['footer', 'menu_picto'],
            []
        );
        $menu = $this->getMenuLocationList($menuLists);
        $response = new Response();
        return $this->render(
            '::footerLayout.html.twig',
            [
                'menus' => $menu,
                'logo' => $logo,
            ],
            $response
        );
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
