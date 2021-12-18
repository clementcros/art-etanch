<?php

namespace AppBundle\Service;

use AppBundle\Utils\SearchHelper;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * This class override Blend\EzSitemapBundle\Services\ContentLoader service.
 *
 * Class ContentLoader
 * @package Smile\EimmoBundle\Service
 */
class ContentLoader
{
    use ContainerAwareTrait;

    private $searchService = null;
    private $locationService = null;
    private $sectionService;
    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * ContentLoader constructor.
     * @param $locationService
     * @param $searchService
     * @param SectionService $sectionService
     */
    public function __construct($locationService, $searchService, SectionService $sectionService, SearchHelper $searchHelper)
    {
        $this->searchService = $searchService;
        $this->locationService = $locationService;
        $this->sectionService = $sectionService;
        $this->searchHelper = $searchHelper;
    }
    
    /**
     * @param bool $mainLocationOnly
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function loadLocations($mainLocationOnly = true)
    {
        $contentTypes = $this->container->getParameter('blend_sitemap.content_types');
        $allowedSections = $this->container->getParameter('blend_sitemap.allowed_sections');
        $limit = $this->container->getParameter('blend_sitemap.query_limit');
        $searchService = $this->searchHelper;

        $allowedSectionIds = [];
        foreach ($allowedSections as $sectionIdentifier) {
            $allowedSectionIds[] = $this->sectionService->loadSectionByIdentifier($sectionIdentifier)->id;
        }

        $sortClauses = [ new SortClause\DateModified(Query::SORT_DESC) ];
        $filters[] = new Criterion\SectionId($allowedSectionIds);
        
        if ($mainLocationOnly) {
            $filters[] = new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN);
        }

        return $searchService->locationsTree(
            $this->locationService->loadLocation(2),
            $contentTypes,
            $sortClauses,
            $limit,
            0,
            $filters
        );
    }

//    /**
//     * @return array
//     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
//     */
//    public function loadTagsSlug()
//    {
//        $eZTagService = $this->container->get('eztags.api.service.tags');
//        $results = $eZTagService->loadTagChildren();
//        $urlPrefix = $this->container->getParameter('blend_sitemap.theme_url');
//        $tagsLink = array();
//
//        $tagService = $this->container->get('eimmo.helper.smile_tags');
//
//        foreach ($results as $result) {
//            $relatedContent = $eZTagService->getRelatedContent($result);
//            if (is_array($relatedContent) && count($relatedContent)) {
//                $slug = $tagService->getSlugForGivenTag($result);
//                array_push($tagsLink, $urlPrefix . $slug);
//            }
//
//            $resultsChildren = $eZTagService->loadTagChildren($result);
//            foreach ($resultsChildren as $resultChild) {
//                $relatedContent = $eZTagService->getRelatedContent($resultChild);
//                if (is_array($relatedContent) && count($relatedContent)) {
//                    $slug = $tagService->getSlugForGivenTag($resultChild);
//                    array_push($tagsLink, $urlPrefix . $slug);
//                }
//            }
//        }
//        return $tagsLink;
//    }
}
