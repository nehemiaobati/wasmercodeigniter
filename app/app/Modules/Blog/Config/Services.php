<?php

declare(strict_types=1);

namespace App\Modules\Blog\Config;

use CodeIgniter\Config\BaseService;
use App\Modules\Blog\Libraries\BlogService;

/**
 * Blog Module Services Configuration.
 */
class Services extends BaseService
{
    /**
     * The Blog service.
     *
     * @param bool $getShared
     * @return BlogService
     */
    public static function blogService(bool $getShared = true): BlogService
    {
        if ($getShared) {
            return static::getSharedInstance('blogService');
        }

        return new BlogService();
    }
}
