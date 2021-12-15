<?php

namespace AppBundle\Utils;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;

/**
 * Class Helper.
 */
class Helper
{
    /**
     * @var GlobalHelper
     */
    public $globalHelper;

    /**
     * @var SearchHelper
     */
    public $searchHelper;


    /**
     * Helper constructor.
     * @param GlobalHelper $globalHelper
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        GlobalHelper $globalHelper,
        SearchHelper $searchHelper
    ) {
        $this->globalHelper = $globalHelper;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Returns the general helper service, exposed in Twig templates as "ezpublish" global variable.
     *
     * @return GlobalHelper
     */
    public function getGlobalHelper()
    {
        return $this->globalHelper;
    }

    /**
     * Returns the root location object for current siteaccess configuration.
     *
     * @return Location
     *
     * @see GlobalHelper::getRootLocation()
     */
    public function getRootLocation()
    {
        return $this->globalHelper->getRootLocation();
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
        return $this->searchHelper->loadLocationById($locationId, $prioritizedLanguages, $useAlwaysAvailable);
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
        return $this->searchHelper->loadContentById($contentId, $languages, $versionNo, $useAlwaysAvailable);
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
        return $this->searchHelper->loadPublishedContentById($contentId);
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
        return $this->searchHelper->onlyPublished($contentIds);
    }

    /**
     * @param int|Content|null $content
     *
     * @return bool
     */
    public function isPublished($content = null)
    {
        return $this->searchHelper->isPublished($content);
    }
}
