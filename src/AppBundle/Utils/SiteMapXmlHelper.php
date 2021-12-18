<?php

namespace AppBundle\Utils;

use eZ\Publish\Core\Repository\Values\Content\Location;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteMapXmlHelper
{

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var mixed
     */
    private $typesWithTrailingSlash;
    private $excludeUrls;
    private $contentTypeService;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }


    public function generateSitemap()
    {
        $time_start = microtime(true);

        $container = $this->container;
        $this->typesWithTrailingSlash = $container->getParameter('blend_sitemap.container_types');
        $this->excludeUrls = $container->getParameter('blend_sitemap.exclude_urls');
        $this->contentTypeService = $container->get('ezpublish.api.repository')->getContentTypeService();

        $contentLoaderService = $container->get('appbundle.blend_sitemap.content');
        $locations = $contentLoaderService->loadLocations(true);

        $rootUrl = $container->getParameter('blend_sitemap.main_uri');
        $sitemap = new \DOMDocument('1.0', 'utf-8');
        $sitemap->preserveWhiteSpace = false;
        $sitemap->formatOutput = true;
        $urlSet = $sitemap->createElement('urlset');
        $urlSet->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $sitemap->appendChild($urlSet);

        $delay = 1000;
        $i = 1;
        $itemsCount = count($locations);
        if ($itemsCount > 10000) {
            ini_set('memory_limit', '512M');
        }

        foreach ($locations as $key => $location) {
            // create url block
            $urlBlock = $sitemap->createElement('url');
            $urlSet->appendChild($urlBlock);

            // create loc tag
            $loc = $sitemap->createElement('loc');
            $urlBlock->appendChild($loc);

            $url = $container->get('router')->generate($location);

            if (in_array($url, $this->excludeUrls)) {
                continue;
            }

            if ($this->hasTrailingSlash($location)) {
                $url .= '/';
            }

            $locText = $sitemap->createTextNode($rootUrl.$url);
            $loc->appendChild($locText);

            // create changefreq tag
            $changefreq = $sitemap->createElement('changefreq');
            $urlBlock->appendChild($changefreq);
            $lastmodText = $sitemap->createTextNode('weekly');
            $changefreq->appendChild($lastmodText);

            // create lastmod tag
            $lastmod = $sitemap->createElement('lastmod');
            $urlBlock->appendChild($lastmod);
            $lastmodText = $sitemap->createTextNode($location->contentInfo->modificationDate->format('Y-m-d'));
            $lastmod->appendChild($lastmodText);

            //Stop execution after generate 1000 items
            if ($delay == $i && $itemsCount > 10000) {
                sleep(10);
                $delay = $delay + 1000;
            }
            ++$i;
        }



        $filePath = 'web/sitemap.xml';
        if ('cli' !== php_sapi_name()) {
            $filePath = getcwd().'/sitemap.xml';
        }

        $fp = fopen($filePath, 'w');
        fwrite($fp, $sitemap->saveXml());
        fclose($fp);
    }

    /**
     * @param Location $location
     * @return bool
     */
    private function hasTrailingSlash(Location $location)
    {
        if ($this->contentTypeService) {
            $contentType = $this->contentTypeService->loadContentType($location->contentInfo->contentTypeId);
            if (in_array($contentType->identifier, $this->typesWithTrailingSlash)) {
                return true;
            }
        }

        return false;
    }
}