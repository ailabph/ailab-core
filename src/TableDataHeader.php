<?php

namespace Ailabph\AilabCore;

class TableDataHeader
{
    public string $table_name = "";
    public array $dataKeys = [];
    public string $dataKeysString = "";

    public array $dataKeysPrimary = [];
    public string $dataKeysPrimaryString = "";

    public array $dataKeysAutoInc = [];
    public string $dataKeysAutoIncString = "";

    public array $dataKeysUnique = [];
    public string $dataKeysUniqueString = "";

    public array $required = [];
    public string $requiredString = "";

    public array $data_properties_index = [];
    public string $data_properties_indexString = "";

    public array $data_properties = [];
    public string $data_propertiesString = "";

    public array $data_property_types = [];
    public string $data_property_typesString = "";

    /** @var TableDataProperty[]  */
    public array $properties = [];
}