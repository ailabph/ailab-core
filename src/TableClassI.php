<?php

namespace Ailabph\AilabCore;

interface TableClassI
{
    public function isNew(): bool;
    public function hasChange(string $property): bool;
    public function getOrig(string $property);
    public function loadValues(array|object $data, bool $isNew = false, array $exclude = [], bool $manualLoad = false);
    public function save(string $where = "", array $param = []);
    public function getTableName(bool $forQuery = false): string;
}