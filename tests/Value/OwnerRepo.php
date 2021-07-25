<?php
declare(strict_types=1);

namespace AutoRoute\Value;

class OwnerRepo
{
    public function __construct(
        protected string $ownerName,
        protected string $repoName
    ) {
    }

    public function get() : string
    {
        return $this->ownerName . '/' . $this->repoName;
    }
}
