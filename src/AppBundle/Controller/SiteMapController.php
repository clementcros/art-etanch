<?php
namespace AppBundle\Controller;

use AppBundle\Utils\SiteMapXmlHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteMapController extends Controller
{


    /**
     * @var SiteMapXmlHelper
     */
    private $siteMapXmlHelper;

    /**
     * @param SiteMapXmlHelper $siteMapXmlHelper
     */
    public function __construct(
        SiteMapXmlHelper $siteMapXmlHelper
    )
    {
        $this->siteMapXmlHelper = $siteMapXmlHelper;
    }

    /**
     * @param ContentView $view
     * @return ContentView
     */
    public function indexAction(Request $request)
    {
        $this->siteMapXmlHelper->generateSitemap();

        return $this->redirect($request->server->get("HTTP_REFERER"));
    }

    public function viewAction()
    {
        $response = new Response();
        $response->setPublic();
        $response->setSharedMaxAge(86400);
        $response->headers->set('X-Location-Id', 2);
        $response->headers->set('Content-Type', 'application/xml');
        $response->sendHeaders();
        $siteMap = getcwd().'/sitemap.xml';
        if (!file_exists($siteMap)) {
           $this->siteMapXmlHelper->generateSitemap();
            $siteMap = getcwd().'/sitemap.xml';
        }

        return $response->setContent(file_get_contents($siteMap));
    }
}