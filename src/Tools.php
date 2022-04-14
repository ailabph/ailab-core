<?php

namespace Ailabph\AilabCore;

class Tools
{
    static public function getRootPath(){
        return dirname(\Composer\Factory::getComposerFile());
    }
}