<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SearchHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use eZ\Publish\Core\Repository\Values\Content\Location;
use Symfony\Component\HttpFoundation\Response;

class LayoutController extends Controller
{

    const TITLE = [
        'title',
        'name',
        'family_kitchen_title'
    ];
    const DESCRITPION = [
        'description',
        'short_description',
        'text_content',
        'content'
    ];

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

    public function metaAction($currentLocationId): Response
    {
        $content = $this->searchHelper->loadLocationById($currentLocationId)->getContent();
        $titleMeta = null;
        $descriptionMeta = null;

        foreach ($content->getFields() as $field)
        {
            if (in_array($field->fieldDefIdentifier, self::TITLE)) {
                $titleMeta = $field->fieldDefIdentifier;
                break;
            }
        }

        foreach ($content->getFields() as $field)
        {
            if (in_array($field->fieldDefIdentifier, self::DESCRITPION)) {
                 $descriptionMeta = $field->fieldDefIdentifier;
                break;
            }
        }

        $response = new Response();
        return $this->render(
            '::metaLayout.html.twig',
            [
                'content' => $content,
                'title' => $titleMeta,
                'description' => $descriptionMeta
            ],
            $response
        );
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
