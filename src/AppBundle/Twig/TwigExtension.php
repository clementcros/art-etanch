<?php

namespace AppBundle\Twig;

use AppBundle\Utils\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TwigExtension.
 */
class TwigExtension extends AbstractExtension
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * TwigExtension constructor.
     *
     * @param Helper         $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array_merge(parent::getFilters(), [
            new TwigFilter('is_published', [$this->helper, 'isPublished']),
        ]);
    }
}
