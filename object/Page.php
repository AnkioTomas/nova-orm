<?php
declare(strict_types=1);
/*
 * Copyright (c) 2023. Ankio. All Rights Reserved.
 */

/**
 * Package: nova\plugin\orm\object
 * Class Page
 * Created By ankio.
 * Date : 2022/11/16
 * Time : 18:19
 * Description :
 */

namespace nova\plugin\orm\object;

use nova\framework\text\ArgObject;

class Page extends ArgObject
{
    public int $total_count = 0;
    public int $page_size = 0;
    public int $total_page = 0;
    public int $first_page = 0;
    public int $prev_page = 0;
    public int $next_page = 0;
    public int $last_page = 0;
    public int $current_page = 0;
    public array $all_pages = [];
    public int $offset = 0;
    public int $limit = 0;
}