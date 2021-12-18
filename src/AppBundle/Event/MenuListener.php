<?php

namespace AppBundle\Event;

use eZ\Publish\API\Repository\Values\Content\Content;
use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuListener implements EventSubscriberInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [ConfigureMenuEvent::MAIN_MENU => 'onMainMenuBuild'];
    }

    /**
     * This method adds custom menu items to eZ Platform admin interface.
     *
     * @param ConfigureMenuEvent $event
     */
    public function onMainMenuBuild(ConfigureMenuEvent $event)
    {

        $menu = $event->getMenu();

        /* @var Content $content */
        $content = $event->getOptions()['content'];
        $menu->addChild(
            'massmail',
            [
                'label' => 'Mise a jour du sitemap',
                'uri' => $this->container->get('router')->generate(
                    'generate_sitemap',
                    []
                ),
            ]
        );
    }
}
