<?php
namespace AppBundle\Controller;

use AppBundle\Utils\EmailHelper;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\MVC\Symfony\View\ContentView;
use Symfony\Component\HttpFoundation\Request;

class ContactController extends Controller
{


    /**
     * @var EmailHelper
     */
    private $emailHelper;

    /**
     * @param EmailHelper $emailHelper
     */
    public function __construct(
        EmailHelper $emailHelper
    )
    {
        $this->emailHelper = $emailHelper;
    }

    /**
     * @param ContentView $view
     * @return ContentView
     */
    public function fullAction(ContentView $view, Request $request): ContentView
    {
        $sended = null;
        if ($request->getMethod() == Request::METHOD_POST) {
            $sended = $this->emailHelper->sendEmail($request->request->all(), $request->server->get('HTTP_ORIGIN'));
        }

        $view->addParameters([
            'sended' => $sended
        ]);

        return $view;
    }
}