<?php
namespace AppBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;

class HomeController extends Controller
{

    /**
     * @param ContentView $view
     * @return ContentView
     */
    public function fullAction(ContentView $view): ContentView
    {

        return $view;
    }
}
