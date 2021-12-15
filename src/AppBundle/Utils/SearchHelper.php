<?php

namespace AppBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Exceptions\UnauthorizedException;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;

/**
 * Class SearchHelper.
 */
class SearchHelper
{
    /**
     * @var SearchService
     */
    private $searchService;
    /**
     * @var LocationService
     */
    private $locationService;
    /**
     * @var SearchService
     */
    private $contentService;
    /**
     * @var LanguageService
     */
    private $languageService;
    /**
     * @var TranslationHelper
     */
    private $translationHelper;
    /**
     * @var Repository
     */
    protected $repository;
    /**
     * @var GlobalHelper
     */
    protected $globalHelper;
    /**
     * @var EntityManagerInterface
     */

    protected $searchEntityHelper;
    /**
     * @var $searchEngine
     */
    private $searchEngine;


    /**
     * SearchHelper constructor.
     *
     * @param SearchService $searchService
     * @param LocationService $locationService
     * @param ContentService $contentService
     * @param LanguageService $languageService
     * @param TranslationHelper $translationHelper
     * @param Repository $repository
     * @param GlobalHelper $globalHelper
     * @param EntityManagerInterface $entityManager
     * @param SearchEntityHelper $searchEntityHelper
     * @param string $searchEngine
     */
    public function __construct(
        SearchService $searchService,
        LocationService $locationService,
        ContentService $contentService,
        LanguageService $languageService,
        TranslationHelper $translationHelper,
        Repository $repository,
        GlobalHelper $globalHelper,
        EntityManagerInterface $entityManager,
        string $searchEngine
    ) {
        $this->repository = $repository;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->searchService = $searchService;
        $this->languageService = $languageService;
        $this->translationHelper = $translationHelper;
        $this->globalHelper = $globalHelper;
        $this->searchEngine = $searchEngine;
    }

    /**
     * returns the identifier of the currently used engine
     */
    public function getSearchEngineIdentifier(): string
    {
        return $this->searchEngine;
    }

    /**
     * Builds a list from $searchResult.
     * Returned array consists of a hash of objects, indexed by their ID.
     *
     * @param SearchResult $searchResult
     *
     * @return array
     */
    public function buildListFromSearchResult(SearchResult $searchResult)
    {
        $list = [];

        foreach ($searchResult->searchHits as $searchHit) {
            $list[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        return $list;
    }

    /**
     * Searches for content under $parentLocationId being of the specified
     * types sorted with $sortClauses.
     *
     * @param Location        $parentLocation
     * @param string|string[] $typeIdentifiers
     * @param array           $sortClauses
     * @param int|null        $limit
     * @param int             $offset
     * @param array           $filters
     * @param bool|null       $onlyCount
     * @param int |null       $depth
     * @param string|string[] $languages
     * @param bool|null       $forceLanguage
     * @param int|null        $visibility
     * @return array|int
     *
     * @throws InvalidArgumentException
     */
    public function locationsTree(
        Location $parentLocation,
                 $typeIdentifiers = [],
        array $sortClauses = [],
        int $limit = null,
        int $offset = 0,
        array $filters = [],
                 $onlyCount = null,
        int $depth = null,
        array $languages = [],
                 $forceLanguage = null,
                 $visibility = Criterion\Visibility::VISIBLE
    ) {
        $query = new LocationQuery();
        $languageFilter = [
            'useAlwaysAvailable' => false,
        ];
        $filters[] = new Criterion\Subtree($parentLocation->pathString);
        if ($typeIdentifiers) {
            $filters[] = new Criterion\ContentTypeIdentifier($typeIdentifiers);
        }
        if (null !== $visibility) {
            $filters[] = new Criterion\Visibility($visibility);
        }

        if ($forceLanguage) {
            $languageFilter = [
                'useAlwaysAvailable' => true,
                'languages' => [$this->getCurrentLanguageCode()],
            ];
        }

        if (!empty($languages)) {
            $languageFilter['languages'] = $languages;
        }

        if ($depth > 0) {
            $filters[] = new Criterion\Location\Depth(
                Criterion\Operator::LTE,
                $parentLocation->depth + (int) $depth
            );
        }

        $query->filter = new Criterion\LogicalAnd(
            $filters
        );
        $query->sortClauses = [
            new SortClause\Location\Depth(),
            new SortClause\Location\Priority(),
        ];

        if (!empty($sortClauses)) {
            $query->sortClauses = $sortClauses;
        }

        if (null !== $limit) {
            $query->limit = $limit;
        }

        $query->offset = $offset;
        $locations = [];
        $searchResult = $this->searchService->findLocations($query, $languageFilter);
        if (true === $onlyCount) {
            return $searchResult->totalCount;
        }
        if ($searchResult->totalCount) {
            $locations = $this->buildListFromSearchResult($searchResult);
        }

        return $locations;
    }

    /**
     * Searches for content under $parentLocationId being of the specified
     * types sorted with $sortClauses.
     *
     * @param int|null $parentLocationId
     * @param string|string[] $typeIdentifiers
     * @param array $sortClauses
     * @param int|null $limit
     * @param int $offset
     * @param array $filters
     * @param bool|null $onlyCount
     * @param bool|null $forceLanguage
     * @param array|null $sectionIds
     *
     * @return array|int|null
     *
     * @throws InvalidArgumentException
     */
    public function locationsList(
        int $parentLocationId = null,
            $typeIdentifiers = [],
        array $sortClauses = [],
        int $limit = null,
        int $offset = 0,
        array $filters = [],
        $onlyCount = null,
        $forceLanguage = null,
        array $sectionIds = []
    ) {
        $query = new LocationQuery();

        if ($parentLocationId) {
            $filters[] = new Criterion\ParentLocationId($parentLocationId);
        }
        if ($typeIdentifiers) {
            $filters[] = new Criterion\ContentTypeIdentifier($typeIdentifiers);
        }
        $filters[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);
        if ($forceLanguage) {
            $filters[] = new Criterion\LogicalOr(
                [
                    new Criterion\LanguageCode($this->getCurrentLanguageCode(), true),
                    new Criterion\CustomField(
                        'content_always_available_b',
                        Criterion\Operator::EQ, 1
                    ),
                ]
            );
        }

        if (!empty($sectionIds)) {
            $filters[] = new Criterion\SectionId($sectionIds);
        }

        $query->filter = new Criterion\LogicalAnd(
            $filters
        );
        $query->sortClauses = [new SortClause\Location\Priority()];

        if (!empty($sortClauses)) {
            $query->sortClauses = $sortClauses;
        }
        if (null !== $limit) {
            $query->limit = $limit;
        }
        $query->offset = $offset;
        $locations = [];
        $searchResult = $this->searchService->findLocations($query);

        if (true === $onlyCount) {
            return $searchResult->totalCount;
        }
        if ($searchResult->totalCount) {
            $locations = $this->buildListFromSearchResult($searchResult);
        }

        return $locations;
    }

    /**
     * Searches for content under $parentLocationId being of the specified
     * types sorted with $sortClauses.
     *
     * @param Location   $parentLocation
     * @param array|null $typeIdentifiers
     * @param array|null $sortClauses
     * @param int|null   $limit
     * @param int        $offset
     * @param array      $filters
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function locationsTreeIgnoreVisibility(
        Location $parentLocation,
        array $typeIdentifiers = null,
        array $sortClauses = array(),
        int $limit = null,
        int $offset = 0,
        array $filters = array()
    ) {
        $query = new LocationQuery();

        $filters[] = new Criterion\Subtree($parentLocation->pathString);
        if ($typeIdentifiers) {
            $filters[] = new Criterion\ContentTypeIdentifier($typeIdentifiers);
        }

        $query->filter = new Criterion\LogicalAnd(
            $filters
        );
        $query->sortClauses = [new SortClause\Location\Priority()];

        if (!empty($sortClauses)) {
            $query->sortClauses = $sortClauses;
        }
        if (null !== $limit) {
            $query->limit = $limit;
        }
        $query->offset = $offset;
        $locations = [];
        $searchResult = $this->searchService->findLocations($query);
        if ($searchResult->totalCount) {
            $locations = $this->buildListFromSearchResult($searchResult);
        }

        return $locations;
    }

    /**
     * Searches for content under $parentLocationId being of the specified
     * types sorted with $sortClauses.
     *
     * @param int        $parentLocationId
     * @param array|null $typeIdentifiers
     * @param array|null $sortClauses
     * @param int|null   $limit
     * @param int        $offset
     * @param array      $filters
     * @param bool|null  $onlyCount
     * @param bool|null $forceLanguage
     * @param array|null $sectionIds
     *
     * @return array|int|null
     *
     * @throws InvalidArgumentException
     */
    public function contentsList(
        int $parentLocationId,
        array $typeIdentifiers = null,
        array $sortClauses = array(),
        int $limit = null,
        int $offset = 0,
        array $filters = array(),
        $onlyCount = null,
        $forceLanguage = null,
        array $sectionIds = []
    ) {
        $locations = $this->locationsList(
            $parentLocationId,
            $typeIdentifiers,
            $sortClauses,
            $limit,
            $offset,
            $filters,
            $onlyCount,
            $forceLanguage,
            $sectionIds
        );

        if ($onlyCount === true) {
            return $locations;
        }

        $contents = [];

        foreach($locations as $location) {
            $contents[] = $location->getContent();
        }

        return $contents;
    }

    /**
     * Current language code.
     *
     * @return string
     */
    public function getCurrentLanguageCode()
    {
        return $this->languageService->getDefaultLanguageCode();
    }

    /**
     * @param Content $content
     * @param string  $fieldIdentifier
     * @param bool    $multipleRelations
     * @param bool    $showHiddenRelation
     * @param bool    $getLocations
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getTargetOfRelations(
        Content $content,
        string $fieldIdentifier,
        bool $multipleRelations = false,
        bool $showHiddenRelation = false,
        bool $getLocations = false
    ) {
        $result = array();
        $translationField = $this->translationHelper->getTranslatedField($content, $fieldIdentifier);
        if (empty($translationField)) {
            return [];
        }

        $relations = [];

        if ($multipleRelations) {
            $relations = $translationField->value->destinationContentIds;
        } elseif ($translationField->value->destinationContentId) {
            $relations[] = $translationField->value->destinationContentId;
        }

        if (count($relations)) {
            foreach ($relations as $relationId) {
                // Get content of relation object.
                $contentRelation = $this->contentService->loadContent($relationId);

                // Check if content is published, because if it is in the trash the link of relation isnt destroy.
                if ($contentRelation->contentInfo->published) {
                    // Get main location of relation content.
                    $mainLocationRelation = $this->locationService
                        ->loadLocation($contentRelation->contentInfo->mainLocationId);

                    // Add result if relation is visible or if is hidden but relation hidden is needed.
                    if ((false == $mainLocationRelation->hidden || true == $mainLocationRelation->hidden
                            && true == $showHiddenRelation)
                        && ($contentRelation->contentInfo->alwaysAvailable ||
                            !is_null($contentRelation->getName($this->getCurrentLanguageCode())))
                    ) {

                        if (!$getLocations) {
                            $result[] = $contentRelation;
                        } else {
                            $result[] = $mainLocationRelation;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param Content $content
     * @param string  $fieldIdentifier
     * @param bool    $multipleRelations
     * @param bool    $showHiddenRelation
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getContentOfRelations(
        Content $content,
        string $fieldIdentifier,
        bool $multipleRelations = false,
        bool $showHiddenRelation = false
    ) {
        return $this->getTargetOfRelations($content, $fieldIdentifier, $multipleRelations, $showHiddenRelation);
    }

    /**
     * @param Content   $content
     * @param string    $fieldIdentifier
     * @param bool $multipleRelations
     * @param bool $showHiddenRelation
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getLocationOfRelations(
        Content $content,
        string $fieldIdentifier,
        bool $multipleRelations = false,
        bool $showHiddenRelation = false
    ) {
        return $this->getTargetOfRelations($content, $fieldIdentifier, $multipleRelations, $showHiddenRelation, true);
    }

    /**
     * Load Location By id.
     *
     * @param int        $locationId
     * @param array|null $prioritizedLanguages
     * @param bool|null  $useAlwaysAvailable
     *
     * @return Location|null
     *
     * @see LocationService::loadLocation()
     */
    public function loadLocationById(
        int $locationId,
        array $prioritizedLanguages = null,
        bool $useAlwaysAvailable = null
    ) {
        $location = null;
        try {
            $location = $this->locationService->loadLocation($locationId, $prioritizedLanguages, $useAlwaysAvailable);
        } catch (UnauthorizedException $error) {
            $location = null;
        } catch (NotFoundException $error) {
            $location = null;
        }

        return $location;
    }

    /**
     * Load Content By id.
     *
     * @param int        $contentId
     * @param array|null $languages
     * @param int        $versionNo
     * @param bool       $useAlwaysAvailable
     *
     * @return Content|null
     */
    public function loadContentById(
        int $contentId,
        array $languages = null,
        int $versionNo = null,
        bool $useAlwaysAvailable = true
    ) {
        $content = null;
        try {
            $content = $this->contentService->loadContent($contentId, $languages, $versionNo, $useAlwaysAvailable);
        } catch (UnauthorizedException $error) {
            $content = null;
        } catch (NotFoundException $error) {
            $content = null;
        }

        return $content;
    }

    /**
     * Load Published Content By id.
     *
     * @param int $contentId
     *
     * @return Content|null
     */
    public function loadPublishedContentById(int $contentId)
    {
        $content = $this->loadContentById($contentId);

        return (null !== $content && $this->isPublished($content)) ? $content : null;
    }
    /**
     * Get only the content ids that is not already removed or Hidden.
     *
     * @param int[]|null $contentIds
     *
     * @return array
     */
    public function onlyPublished(array $contentIds = null)
    {
        $publishedContentIds = [];
        $contentIds = array_unique((array) $contentIds);
        $onlyPublished = [];
        if (!empty($contentIds)) {
            $contentInfos = $this->contentService->loadContentInfoList($contentIds);
            if ($contentInfos) {
                foreach ($contentInfos as $contentInfo) {
                    if (false === $contentInfo->isHidden && $contentInfo->isPublished() &&
                        null !== $contentInfo->mainLocationId) {
                        $publishedContentIds[] = $contentInfo->id;
                    }
                }
            }
        }
        // Remove unPublished Contents and save the order of ids .
        if (!empty($publishedContentIds)) {
            $onlyPublished = array_intersect($contentIds, $publishedContentIds);
            // Reset array index.
            $onlyPublished = array_values($onlyPublished);
        }

        return $onlyPublished;
    }

    /**
     * @param int|Content|null $content
     *
     * @return bool
     */
    public function isPublished($content = null)
    {
        if ($content instanceof Content) {
            $contentInfo = $content->contentInfo;
            $isPublished = (false === $contentInfo->isHidden && $contentInfo->isPublished()
                && null !== $contentInfo->mainLocationId);
        } else {
            $isPublished = ((int) $content and in_array($content, (array) $this->onlyPublished([(int) $content])));
        }

        return $isPublished;
    }

    /**
     * @param mixed $type
     * @param array $withThisThemesId
     * @param bool $withArticlesForward
     * @return LocationQuery
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getArticles($type = null, $withThisThemesId = [], bool $withArticlesForward = true)
    {
        $types = $type ?? $this->globalHelper->getConfigResolver()->getParameter("list_news.types", "app");

        $query = new LocationQuery();
        $criteria = [
            new Criterion\LogicalAnd([
                new Criterion\ContentTypeIdentifier($types),
                new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            ]),
        ];

        if ($withThisThemesId !== []) {
            $criteria[] = new Criterion\FieldRelation(
                'themes_relation',
                Criterion\Operator::IN,
                $withThisThemesId
            );
        }

        if (!$withArticlesForward) {
            $newsForwardIds = $this->getArticlesIdsForward($type, $withThisThemesId);
            if (count($newsForwardIds)) {
                $criteria[] = new Criterion\LogicalNot(new Criterion\ContentId($newsForwardIds));
            }
        }

        $query->query = new Criterion\LogicalAnd($criteria);

        if ($this->searchEngine === self::SEARCH_ENGINE_IDENTIFIER_SOLR) {
            $query->sortClauses = [
                new SortCustomField('sort_date_editorial_dt', Query::SORT_DESC),
            ];
        }

        return $query;

    }

    /**
     * @param array $excludeLocations
     * @param int $limit
     * @return array
     * @throws InvalidArgumentException
     */
    public function getLastArticles(array $excludeLocations = [], int $limit = 3): array
    {
        $query = new LocationQuery();
        $types = $type ?? $this->globalHelper->getConfigResolver()->getParameter("bloc_news.types", "app");
        $locations = [];

        if ($types) {
            $filters[] = new Criterion\ContentTypeIdentifier($types);
        }

        $filters[] = new Criterion\Visibility(Criterion\Visibility::VISIBLE);

        $excludeLocationIds = [];

        foreach ($excludeLocations as $locationId) {
            array_push($excludeLocationIds, $locationId->id);
        }

        if (!empty($excludeLocationIds))
        {
            $filters[] = new Criterion\LogicalNot(new Criterion\LocationId($excludeLocationIds));
        }


        $query->filter = new Criterion\LogicalAnd(
            $filters
        );

        if ($this->searchEngine === self::SEARCH_ENGINE_IDENTIFIER_SOLR) {
            $query->sortClauses = [
                new SortCustomField('sort_published_date_editorial_dt', Query::SORT_DESC),
            ];
        } else {
            $query->sortClauses = [
                new SortClause\Field('article', 'published_date_editorial', Query::SORT_DESC),
            ];
        }

        $query->limit = $limit;

        $searchResult = $this->searchService->findLocations($query);

        if ($searchResult->totalCount) {
            $locations = $this->buildListFromSearchResult($searchResult);
        }

        return $locations;
    }

    /**
     * @param null $type
     * @param array $themesId
     * @return array
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getArticlesForward($type = null, $themesId = [])
    {
        $newsForwardIds = $this->getArticlesIdsForward($type, $themesId);

        $newsForwardLocation = [];
        foreach ($newsForwardIds as $newsForwardId) {
            $contentForward = $this->repository->getContentService()->loadContentInfo($newsForwardId);
            $newsForwardLocation[] = $this->repository->getLocationService()->loadLocation($contentForward->mainLocationId);
        }

        return $newsForwardLocation;
    }

    /**
     * @param mixed $type
     * @param array $themesId
     * @return array
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getArticlesIdsForward($type = null, $themesId = [])
    {
        $rootLocation = $this->globalHelper->getRootLocation();
        $listArticlesLocation = $this->locationsList(
            $rootLocation->id,
            ['list_news'],
            [],
            1
        );

        /** @var Location $listArticlesLocation */
        $listArticlesLocation = current($listArticlesLocation);
        $listArticlesContent = $listArticlesLocation->getContent();

        $articlesForwardId = [];
        if ($listArticlesContent) {
            if ($themesId === [] && $type === null) {
                $articlesForwardId = $listArticlesContent->getFieldValue('news_forward')->destinationContentIds;
            } else {
                foreach ($listArticlesContent->getFieldValue('news_forward')->destinationContentIds as $contentId) {
                    $articleForward = $this->repository->getContentService()->loadContent($contentId);
                    $articleType = $this->repository->getContentTypeService()->loadContentType($articleForward->contentInfo->contentTypeId);
                    if ($type !== null && $articleType->identifier !== $type) {
                        continue;
                    }

                    if ($themesId === []) {
                        $articlesForwardId[] = $contentId;
                    }
                    foreach ($themesId as $themeId) {
                        if (in_array($themeId, $articleForward->getFieldValue('themes_relation')->destinationContentIds)) {
                            $articlesForwardId[] = $contentId;
                            break;
                        }
                    }
                }
            }
        }

        return $articlesForwardId;
    }

    public function getThemesForward()
    {
        $rootLocation = $this->globalHelper->getRootLocation();
        $listThemesLocation = $this->locationsList(
            $rootLocation->id,
            ['list_theme'],
            [],
            1
        );

        /** @var Location $listThemesLocation */
        $listThemesLocation = current($listThemesLocation);
        $listThemesContent = $listThemesLocation->getContent();

        $themesForward = [];

        if ($listThemesContent) {
            foreach ($listThemesContent->getFieldValue('themes_forward')->destinationContentIds as $contentId) {
                $theme = $this->repository->getContentService()->loadContent($contentId);
                $themesForward[$contentId] = $theme->getFieldValue('title');
            }
        }

        return $themesForward;
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public function getUniverses()
    {
        $rootLocation = $this->globalHelper->getRootLocation();
        $listUniverseLocation = $this->locationsList(
            $rootLocation->id,
            ['list_universe'],
            [],
            1
        );
        /** @var Location $listUniverseLocation */
        $listUniverseLocation = current($listUniverseLocation);

        return $this->locationsList(
            $listUniverseLocation->id,
            ['universe'],
            []
        );
    }

    /**
     * Get location id of the content targeted by a menu item
     * @param $menuItem
     * @return int|null
     */
    public function getMenuItemTargetLocationId($menuItem)
    {
        $internalLinkLocationId = null;

        if (!empty($menuItem->getField('internal_link'))
            && $menuItem->getFieldValue('internal_link') !== null
            && $this->isPublished($menuItem->getFieldValue('internal_link')->destinationContentId)
        ) {
            $internalLinkLocationId = $this->loadContentById($menuItem->getFieldValue('internal_link')
                ->destinationContentId)->contentInfo->mainLocationId;
        }

        return $internalLinkLocationId;
    }

    public function searchAgency($zipCode)
    {
        $zipCode = intval($zipCode);
        $findAgency = $this->entityManager->getRepository(AgenciesZipcode::class)->getAgencyByZipcode($zipCode);

        if (empty($findAgency)) {
            return null;
        }

        $agencyId = current($findAgency)->getAgencyLocationId();

        if ($agencyId === null) {
            return null;
        }

        $locationAgency = $this->loadLocationById($agencyId);

        return $locationAgency;
    }

    public function searchAgencyByAgencyId($agencyId)
    {
        if (empty($agencyId)) {
            return [];
        }

        $rootLocation = $this->globalHelper->getRootLocation();
        $listAgenciesLocation = $this->locationsList(
            $rootLocation->id,
            ['list_agencies'],
            [],
            1
        );
        /** @var Location $listAgenciesLocation */
        $listAgenciesLocation = current($listAgenciesLocation);

        $criteria[] = new Criterion\Field(
            'agency_id',
            Criterion\Operator::EQ,
            $agencyId
        );

        return $this->locationsList(
            $listAgenciesLocation->id,
            ['agency'],
            [],
            1,
            0,
            $criteria
        );
    }

    /**
     * Method called by Agencies\ajaxZipcodeAutocomplete controller.
     * Not used anymore, we keep this function for historic reason.
     *
     * @param $zipCode
     * @param $limit
     * @return array|null
     */
    public function searchZipCode($zipCode, $limit)
    {
        $zipCodeResults = $this->entityManager->getRepository(ZipcodeCity::class)->getAgencyByZipcode($zipCode, $limit);
        if ($zipCodeResults !== null) {
            $zipCodeResultFormatted = [];
            foreach ($zipCodeResults as $zipCodeResult) {
                $zipCodeResultFormatted[] = [
                    "value" => $zipCodeResult->getZipcode(),
                    "label" => $zipCodeResult->getCity() . " (" . $zipCodeResult->getZipcode() . ")"
                ];
            }
            return $zipCodeResultFormatted;
        }
        return null;
    }

    /**
     * @param $search
     * @param $limit
     * @return array
     */
    public function searchZipCodeFromSolr($search, $limit): array
    {
        $fieldToSearch = "city_s";

        // Search by zipcode
        if (is_numeric($search)) {
            $fieldToSearch = "zipcode_s";
        } else {
            // Remove emphasis
            $search = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')->transliterate($search);
        }

        $query = new Query();
        $criteria = [];
        $criteria[] = new Criterion\CustomField(
            $fieldToSearch,
            Criterion\Operator::LIKE,
            $search . "*"
        );
        $query->filter = new Criterion\LogicalAnd(
            $criteria
        );

        if (null !== $limit) {
            $query->limit = $limit;
        }

        $query->sortClauses = [
            new SortCustomField("zipcode_s", Query::SORT_ASC),
            new SortCustomField("city_s", Query::SORT_ASC),
        ];

        $searchResult = $this->searchEntityHelper->findEntity($query);
        $entities = [];

        if ($searchResult->totalCount) {
            $entities = $this->searchEntityHelper->buildListFromSearchResultEntity($searchResult);
        }

        return $entities;
    }

    /**
     * @param $search
     * @param null $limit
     * @param $parentLocation
     * @return array
     * @throws InvalidArgumentException
     */
    public function searchProfessionFromSolr($search, $limit = null, $parentLocation = null): array
    {
        $criteria = [
            new Criterion\CustomField('content_name_s', Criterion\Operator::LIKE, "*" . $search . "*"),
        ];

        return $this->contentsList(
            $parentLocation,
            ['profession'],
            [],
            $limit,
            0,
            $criteria
        );
    }

    /**
     * @return mixed|null
     */
    public function getVersion()
    {
        if (file_exists(self::ROOT_DIR . self::SONAR_PROJECT)) {
            $file = parse_ini_file(self::ROOT_DIR . self::SONAR_PROJECT);
            if (isset($file['sonar.projectVersion'])) {
                return $file['sonar.projectVersion'];
            }
        }
        return null;
    }
}
